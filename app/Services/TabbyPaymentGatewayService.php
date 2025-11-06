<?php

namespace App\Services;

use App\Contracts\Payable;
use App\Contracts\PaymentGatewayService;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Support\PaymentMethod;
use Brick\Math\BigDecimal;
use Brick\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class TabbyPaymentGatewayService implements PaymentGatewayService
{
    protected string $baseUrl;
    protected string $secretKey;
    protected string $publicKey;
    protected string $merchantCode;

    public function __construct()
    {
        $this->baseUrl = config(
            "services.tabby.base_url",
            "https://api.tabby.ai",
        );
        $this->secretKey = config("services.tabby.secret_key");
        $this->publicKey = config("services.tabby.public_key");
        $this->merchantCode = config("services.tabby.merchant_code", "NWSA");
    }

    /**
     * Get the available payment methods for the given price
     *
     * For Tabby, we return Tabby as a payment method.
     * Eligibility will be checked separately in the checkout.
     *
     * @return array<PaymentMethod>
     */
    public function paymentMethods(Money $price): array
    {
        return [PaymentMethod::tabby($price)];
    }

    /**
     * Start the payment process
     */
    public function start(Payable $payable, string $paymentMethodId): array
    {
        $user = Auth::user();

        // Get buyer phone
        $phone = null;
        if ($user->phone) {
            // Parse phone number if it's a string
            if (is_string($user->phone)) {
                $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
                try {
                    $phoneNumber = $phoneUtil->parse($user->phone, "SA"); // Default to Saudi Arabia
                    $phone = $phoneUtil->format(
                        $phoneNumber,
                        \libphonenumber\PhoneNumberFormat::E164,
                    );
                } catch (\libphonenumber\NumberParseException $e) {
                    // If parsing fails, use as-is
                    $phone = $user->phone;
                }
            } else {
                // Assume it's already a PhoneNumber object
                $phone = $user->phone->formatE164();
            }
        }

        // TODO: We need to have an action class that calculates the price total
        // Calculate total amount
        $amount = collect($payable->items())->reduce(
            fn(BigDecimal $carry, $item) => $carry->plus(
                $item
                    ->price()
                    ->getAmount()
                    ->multipliedBy(BigDecimal::of($item->quantity())),
            ),
            BigDecimal::zero(),
        );

        // Prepare order items
        // Prepare order items for Tabby
        $orderItems = Arr::map(
            $payable->items(),
            fn($item) => [
                "title" => $item->name(),
                "quantity" => $item->quantity(),
                "unit_price" => (string) $item->price()->getAmount(),
                "category" => "General",
            ],
        );

        // Prepare payload for Tabby session creation
        $payload = [
            "payment" => [
                "amount" => (string) $amount,
                "currency" => $payable
                    ->price()
                    ->getCurrency()
                    ->getCurrencyCode(),
                "buyer" => [
                    "phone" => $phone,
                    "email" => $user->email,
                    "name" => $user->fullName,
                    "dob" => "1990-01-01", // Default DOB, can be updated
                ],
                "shipping_address" => [
                    "city" => "Riyadh", // Default, can be updated from order
                    "address" => "Sample Address",
                    "zip" => "12345",
                ],
                "order" => [
                    "reference_id" => $payable->id,
                    "items" => $orderItems,
                ],
                "buyer_history" => [
                    "registered_since" => "2020-01-01T00:00:00Z",
                    "loyalty_level" => 0,
                ],
            ],
            "lang" => app()->getLocale() === "ar" ? "ar" : "en",
            "merchant_code" => $this->merchantCode,
            "merchant_urls" => [
                "success" => route("payments.callback", [
                    "language" => app()->getLocale(),
                ]),
                "cancel" => route("payments.callback", [
                    "language" => app()->getLocale(),
                ]),
                "failure" => route("payments.callback", [
                    "language" => app()->getLocale(),
                ]),
            ],
        ];

        // Make API call to create session
        $response = Http::withHeaders([
            "Authorization" => "Bearer " . $this->secretKey,
            "Content-Type" => "application/json",
        ])->post($this->baseUrl . "/api/v2/checkout", $payload);

        if (!$response->successful()) {
            Log::error("Tabby session creation failed", [
                "response" => $response->body(),
                "payload" => $payload,
            ]);
            throw new \Exception("Failed to create Tabby payment session");
        }

        $data = $response->json();

        if ($data["status"] !== "created") {
            Log::error("Tabby session not created", ["response" => $data]);
            throw new \Exception("Customer not eligible for Tabby payment");
        }

        $paymentId = $data["payment"]["id"];
        $webUrl =
            $data["configuration"]["available_products"]["installments"][0][
                "web_url"
            ];

        // Create payment record
        $payment = Order::first()
            ->payments()
            ->create([
                "user_id" => Auth::user()->id,
                "external_reference" => $paymentId,
                "gateway" => PaymentGateway::tabby,
                "amount" => $payable->price()->getAmount(),
                "currency" => $payable
                    ->price()
                    ->getCurrency()
                    ->getCurrencyCode(),
                "details" => $data,
            ]);

        return [$payment, $webUrl];
    }

    /**
     * Handle the success/failure callbacks
     */
    public function callback(Request $request): Payment
    {
        Log::info("Tabby callback received", $request->all());

        $paymentId = $request->get("payment_id");

        if (!$paymentId) {
            throw new \Exception("Payment ID not provided in callback");
        }

        // Retrieve payment status from Tabby
        $response = Http::withHeaders([
            "Authorization" => "Bearer " . $this->secretKey,
        ])->get($this->baseUrl . "/api/v2/payments/" . $paymentId);

        if (!$response->successful()) {
            Log::error("Failed to retrieve Tabby payment status", [
                "payment_id" => $paymentId,
                "response" => $response->body(),
            ]);
            throw new \Exception("Failed to verify payment status");
        }

        $paymentData = $response->json();

        // Find our payment record
        $payment = Payment::where("external_reference", $paymentId)->first();

        if (!$payment) {
            Log::error("Payment not found", ["payment_id" => $paymentId]);
            throw new \Exception("Payment record not found");
        }

        $payment->details = $paymentData;

        // Map Tabby status to our status
        switch ($paymentData["status"]) {
            case "AUTHORIZED":
                $payment->status = PaymentStatus::paid;
                break;
            case "REJECTED":
                $payment->status = PaymentStatus::failed;
                break;
            case "EXPIRED":
                $payment->status = PaymentStatus::failed;
                break;
            case "CLOSED":
                $payment->status = PaymentStatus::paid;
                break;
            default:
                $payment->status = PaymentStatus::pending;
        }

        $payment->save();

        // If payment is authorized, capture it
        if ($paymentData["status"] === "AUTHORIZED" && !$payment->captured_at) {
            $this->capturePayment($payment);
        }

        // Clear customer's cart for successful payments
        if (in_array($paymentData["status"], ["AUTHORIZED", "CLOSED"])) {
            $customer = $payment->payable->customer;
            if ($customer && $customer->cart) {
                $customer->cart->cartItems()->delete();
            }
        }

        Log::info("Tabby callback processed", [
            "payment_id" => $payment->id,
            "external_reference" => $paymentId,
            "status" => $paymentData["status"],
        ]);

        return $payment;
    }

    /**
     * Capture an authorized payment
     */
    protected function capturePayment(Payment $payment): void
    {
        try {
            $payload = [
                "amount" => (string) $payment->amount,
                "reference_id" => $payment->payable->id,
            ];

            $response = Http::withHeaders([
                "Authorization" => "Bearer " . $this->secretKey,
                "Content-Type" => "application/json",
            ])->post(
                $this->baseUrl .
                    "/api/v2/payments/" .
                    $payment->external_reference .
                    "/captures",
                $payload,
            );

            if ($response->successful()) {
                $payment->captured_at = now();
                $payment->save();
                Log::info("Payment captured successfully", [
                    "payment_id" => $payment->id,
                ]);
            } else {
                Log::error("Payment capture failed", [
                    "payment_id" => $payment->id,
                    "response" => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Payment capture exception", [
                "payment_id" => $payment->id,
                "error" => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check customer eligibility for Tabby (background pre-scoring)
     */
    public function checkEligibility(Money $amount, array $buyer): array
    {
        // Parse phone number if provided
        $phone = $buyer["phone"] ?? null;
        if ($phone && is_string($phone)) {
            $phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
            try {
                $phoneNumber = $phoneUtil->parse($phone, "SA"); // Default to Saudi Arabia
                $phone = $phoneUtil->format(
                    $phoneNumber,
                    \libphonenumber\PhoneNumberFormat::E164,
                );
            } catch (\libphonenumber\NumberParseException $e) {
                // If parsing fails, use as-is
                $phone = $phone;
            }
        }

        $payload = [
            "payment" => [
                "amount" => (string) $amount->getAmount(),
                "currency" => $amount->getCurrency()->getCurrencyCode(),
                "buyer" => [
                    "phone" => $phone,
                    "email" => $buyer["email"] ?? null,
                    "name" => $buyer["name"] ?? "Customer",
                ],
            ],
            "lang" => app()->getLocale() === "ar" ? "ar" : "en",
            "merchant_code" => $this->merchantCode,
        ];

        $response = Http::withHeaders([
            "Authorization" => "Bearer " . $this->secretKey,
            "Content-Type" => "application/json",
        ])->post($this->baseUrl . "/api/v2/checkout", $payload);

        if (!$response->successful()) {
            Log::error("Tabby eligibility check failed", [
                "response" => $response->body(),
                "payload" => $payload,
            ]);
            return ["eligible" => false, "reason" => "api_error"];
        }

        $data = $response->json();

        return [
            "eligible" => $data["status"] === "created",
            "status" => $data["status"],
            "reason" =>
                $data["configuration"]["products"]["installments"][
                    "rejection_reason"
                ] ?? null,
        ];
    }
}
