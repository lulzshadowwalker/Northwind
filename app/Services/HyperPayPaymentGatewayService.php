<?php

namespace App\Services;

use App\Contracts\Payable;
use App\Contracts\PaymentGatewayService;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Support\PaymentMethod;
use Brick\Math\BigDecimal;
use Brick\Money\Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HyperPayPaymentGatewayService implements PaymentGatewayService
{
    protected string $baseUrl;
    protected string $entityId;
    protected string $accessToken;
    protected bool $isTest;

    public function __construct()
    {
        $this->baseUrl = rtrim(config("services.hyperpay.base_url"), "/");
        $this->entityId = config("services.hyperpay.entity_id");
        $this->accessToken = config("services.hyperpay.access_token");
        $this->isTest = config("services.hyperpay.is_test", true);
    }

    /**
     * Get the available payment methods for the given price
     *
     * For HyperPay, we return HyperPay as a payment method that supports MADA, VISA, MASTER.
     *
     * @return array<PaymentMethod>
     */
    public function paymentMethods(Money $price): array
    {
        return [PaymentMethod::hyperpay($price)];
    }

    /**
     * Start the payment process
     *
     * Step 1: Prepare the checkout by creating a server-to-server request
     */
    public function start(Payable $payable, string $paymentMethodId): array
    {
        $user = Auth::user();

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

        $currencyCode = $payable->price()->getCurrency()->getCurrencyCode();

        // Get user billing details
        $billingAddress = $this->getBillingAddress($user);
        $customerName = $this->getCustomerName($user);

        // Prepare payload for HyperPay checkout
        $payload = [
            "entityId" => $this->entityId,
            "amount" => number_format($amount->toFloat(), 2, ".", ""),
            "currency" => $currencyCode,
            "paymentType" => "DB", // Direct Debit/Charge

            // Merchant transaction ID (unique identifier)
            "merchantTransactionId" => $payable->id . "_" . time(),

            // Customer information
            "customer.email" => $user->email,
            "customer.givenName" => $customerName["givenName"],
            "customer.surname" => $customerName["surname"],

            // Billing address (required by HyperPay)
            "billing.street1" => $billingAddress["street1"],
            "billing.city" => $billingAddress["city"],
            "billing.state" => $billingAddress["state"],
            "billing.country" => $billingAddress["country"],
            "billing.postcode" => $billingAddress["postcode"],

            // Integrity flag for secure form rendering
            "integrity" => "true",
        ];

        // Add test server specific parameters
        if ($this->isTest) {
            $payload["customParameters[3DS2_enrolled]"] = "true";
            $payload["customParameters[3DS2_flow]"] = "challenge";
        }

        try {
            // Make server-to-server request to prepare checkout
            $response = Http::asForm()
                ->withHeaders([
                    "Authorization" => "Bearer " . $this->accessToken,
                ])
                ->timeout(20)
                ->post($this->baseUrl . "/v1/checkouts", $payload);

            if (!$response->successful()) {
                Log::error("HyperPay checkout preparation failed", [
                    "response" => $response->body(),
                    "status" => $response->status(),
                    "payload" => $payload,
                ]);
                throw new \Exception(
                    "Failed to prepare HyperPay checkout: " . $response->body(),
                );
            }

            $data = $response->json();

            // Validate response
            if (
                !isset($data["id"]) ||
                !isset($data["result"]["code"]) ||
                !str_starts_with($data["result"]["code"], "000.200")
            ) {
                Log::error("HyperPay checkout response invalid", [
                    "response" => $data,
                ]);
                throw new \Exception(
                    "Invalid response from HyperPay: " .
                        ($data["result"]["description"] ?? "Unknown error"),
                );
            }

            $checkoutId = $data["id"];
            $integrity = $data["integrity"] ?? null;

            // Create payment record
            $payment = $payable->payments()->create([
                "user_id" => Auth::user()->id,
                "external_reference" => $checkoutId,
                "gateway" => PaymentGateway::hyperpay,
                "amount" => $amount,
                "currency" => $currencyCode,
                "details" => [
                    "checkout_id" => $checkoutId,
                    "integrity" => $integrity,
                    "merchant_transaction_id" =>
                        $payload["merchantTransactionId"],
                    "prepare_response" => $data,
                ],
            ]);

            // Return payment record and the URL to the HyperPay payment page
            // We'll use a route that renders the Copy and Pay widget
            $paymentFormUrl = route("payments.hyperpay.form", [
                "language" => app()->getLocale(),
                "payment" => $payment->id,
            ]);

            return [$payment, $paymentFormUrl];
        } catch (\Exception $e) {
            Log::error("HyperPay checkout preparation exception", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Handle the success/failure callbacks
     *
     * Step 3: Get payment status after shopper completes payment
     */
    public function callback(Request $request): Payment
    {
        Log::info("HyperPay callback received", [
            "all_params" => $request->all(),
            "resource_path" => $request->get("resourcePath"),
            "query_params" => $request->query(),
        ]);

        $resourcePath = $request->get("resourcePath");

        if (!$resourcePath) {
            Log::error("HyperPay callback missing resourcePath", [
                "request_data" => $request->all(),
            ]);
            throw new \Exception("Missing resourcePath in HyperPay callback");
        }

        try {
            // Extract checkout ID from resourcePath
            // resourcePath format: /v1/checkouts/{checkoutId}/payment
            preg_match(
                "/\/checkouts\/([^\/]+)\/payment/",
                $resourcePath,
                $matches,
            );
            $checkoutId = $matches[1] ?? null;

            if (!$checkoutId) {
                Log::error("Could not extract checkout ID from resourcePath", [
                    "resource_path" => $resourcePath,
                ]);
                throw new \Exception("Invalid resourcePath format");
            }

            // Build the status URL
            $separator = str_contains($resourcePath, "?") ? "&" : "?";
            $statusUrl =
                $this->baseUrl .
                $resourcePath .
                $separator .
                "entityId=" .
                urlencode($this->entityId);

            // Fetch payment status from HyperPay
            $response = Http::withHeaders([
                "Authorization" => "Bearer " . $this->accessToken,
            ])
                ->timeout(20)
                ->get($statusUrl);

            if (!$response->successful()) {
                Log::error("Failed to retrieve HyperPay payment status", [
                    "resource_path" => $resourcePath,
                    "checkout_id" => $checkoutId,
                    "response" => $response->body(),
                ]);
                throw new \Exception(
                    "Failed to retrieve payment status from HyperPay",
                );
            }

            $paymentData = $response->json();
            $resultCode = $paymentData["result"]["code"] ?? null;

            // Find our payment record using the checkout ID from resourcePath
            $payment = Payment::where(
                "external_reference",
                $checkoutId,
            )->first();

            if (!$payment) {
                Log::error("Payment not found for HyperPay callback", [
                    "checkout_id" => $checkoutId,
                    "resource_path" => $resourcePath,
                ]);
                throw new \Exception("Payment record not found");
            }

            // Update payment details
            $payment->details = array_merge($payment->details ?? [], [
                "status_response" => $paymentData,
                "result_code" => $resultCode,
                "timestamp" => now()->toIso8601String(),
                "callback_resource_path" => $resourcePath,
            ]);

            // Determine payment status based on result code
            // Success codes pattern: /^(000\.000\.|000\.100\.1|000\.[36])/
            // Pending codes pattern: /^(000\.200)/
            // Review codes pattern: /^(000\.400\.0[^3]|000\.400\.100)/
            $payment->status = $this->mapHyperPayStatus($resultCode);

            $payment->save();

            // Clear customer's cart for successful payments
            // Done synchronously as fallback in case queue job fails
            if ($payment->status === PaymentStatus::paid) {
                try {
                    $customer = $payment->payable->customer ?? null;
                    if ($customer && $customer->cart) {
                        $customer->cart->cartItems()->delete();
                        Log::info("Cart cleared after successful payment", [
                            "payment_id" => $payment->id,
                            "customer_id" => $customer->id,
                            "cart_id" => $customer->cart->id,
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to clear cart after payment", [
                        "payment_id" => $payment->id,
                        "error" => $e->getMessage(),
                    ]);
                }
            }

            Log::info("HyperPay callback processed successfully", [
                "payment_id" => $payment->id,
                "checkout_id" => $checkoutId,
                "external_reference" => $payment->external_reference,
                "status" => $payment->status->value,
                "result_code" => $resultCode,
                "payment_brand" => $paymentData["paymentBrand"] ?? null,
                "amount" => $paymentData["amount"] ?? null,
                "currency" => $paymentData["currency"] ?? null,
            ]);

            return $payment;
        } catch (\Exception $e) {
            Log::error("HyperPay callback processing exception", [
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Map HyperPay result code to our payment status
     */
    protected function mapHyperPayStatus(string $resultCode): PaymentStatus
    {
        // Successful transaction patterns
        if (preg_match("/^(000\.000\.|000\.100\.1|000\.[36])/", $resultCode)) {
            return PaymentStatus::paid;
        }

        // Pending/waiting for confirmation
        if (preg_match("/^(000\.200)/", $resultCode)) {
            return PaymentStatus::pending;
        }

        // Manual review required - treat as pending
        if (preg_match("/^(000\.400\.0[^3]|000\.400\.100)/", $resultCode)) {
            return PaymentStatus::pending;
        }

        // All other codes are considered failed
        return PaymentStatus::failed;
    }

    /**
     * Get billing address for the user
     *
     * @param \App\Models\User $user
     * @return array<string, string>
     */
    protected function getBillingAddress($user): array
    {
        // TODO: Get actual billing address from user's order or profile
        // For now, return default Saudi Arabia address
        return [
            "street1" => "",
            "city" => "",
            "state" => "",
            "country" => "", // ISO Alpha-2 code
            "postcode" => "",
        ];
    }

    /**
     * Get customer name split into given name and surname
     *
     * @param \App\Models\User $user
     * @return array<string, string>
     */
    protected function getCustomerName($user): array
    {
        $fullName = $user->fullName ?? ($user->name ?? "Customer");
        $nameParts = explode(" ", $fullName, 2);

        return [
            "givenName" => $nameParts[0] ?? "Customer",
            "surname" => $nameParts[1] ?? "User",
        ];
    }

    /**
     * Get the checkout details (for rendering the payment form)
     *
     * @return array<string, mixed>
     */
    public function getCheckoutDetails(Payment $payment): array
    {
        $details = $payment->details ?? [];

        return [
            "checkout_id" => $details["checkout_id"] ?? null,
            "integrity" => $details["integrity"] ?? null,
            "base_url" => $this->baseUrl,
            "shopper_result_url" => route("payments.callback", [
                "language" => app()->getLocale(),
            ]),
        ];
    }
}
