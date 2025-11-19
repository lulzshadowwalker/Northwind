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

        // Use Order's total which already includes VAT calculation
        // The Order model's price() method adds 15% VAT to subtotal
        if ($payable instanceof \App\Models\Order) {
            // Use the stored total field which includes VAT
            $amount = BigDecimal::of($payable->total);
        } else {
            // Fallback: use price() method which includes VAT
            $amount = BigDecimal::of($payable->price()->getAmount());
        }

        // Load order items with product and category relationships if this is an Order
        if ($payable instanceof \App\Models\Order) {
            $payable->load("orderItems.product.category");
        }

        // Prepare order items for Tabby
        $orderItems = Arr::map($payable->items(), function ($item, $index) use (
            $payable,
        ) {
            $category = "General"; // Default category

            // Try to get actual category from product if this is an Order
            if ($payable instanceof \App\Models\Order) {
                $orderItem = $payable->orderItems[$index] ?? null;
                if (
                    $orderItem &&
                    $orderItem->product &&
                    $orderItem->product->category
                ) {
                    $category =
                        $orderItem->product->category->getTranslation(
                            "name",
                            "en",
                        ) ?? $category;
                }
            }

            return [
                "title" => $item->name(),
                "quantity" => $item->quantity(),
                "unit_price" => (string) $item->price()->getAmount(),
                "category" => $category,
            ];
        });

        $dob = $user->date_of_birth?->format("Y-m-d");

        // Build shipping address from order if available
        $shippingAddress = null;
        if ($payable instanceof \App\Models\Order && $payable->shipping_city) {
            $shippingAddress = [
                "city" => $payable->shipping_city,
                "address" => $payable->shipping_address ?? "",
                "zip" => $payable->shipping_zip ?? "",
            ];
        }

        // Prepare payload for Tabby session creation
        $payload = [
            "payment" => [
                "amount" => $amount->toScale(
                    2,
                    \Brick\Math\RoundingMode::HALF_UP,
                ),
                "currency" => $payable
                    ->price()
                    ->getCurrency()
                    ->getCurrencyCode(),
                "buyer" => [
                    "phone" => $phone,
                    "email" => $user->email,
                    "name" => $user->name,
                ],
                "order" => [
                    "reference_id" =>
                        $payable instanceof \App\Models\Order
                            ? (string) $payable->id
                            : uniqid("order_"),
                    "items" => $orderItems,
                ],
                "buyer_history" => [
                    "registered_since" => $user->created_at->toIso8601String(),
                    "loyalty_level" => $user->customer
                        ? $user->customer->orders()->count()
                        : 0,
                ],
                "order_history" => $this->getOrderHistory(
                    $user,
                    $payable instanceof \App\Models\Order ? $payable->id : null,
                ),
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

        // Add optional fields only if they have values
        if ($dob) {
            $payload["payment"]["buyer"]["dob"] = $dob;
        }

        if ($shippingAddress) {
            $payload["payment"]["shipping_address"] = $shippingAddress;
        }

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

        // Validate response structure
        if (!is_array($data)) {
            Log::error("Tabby API returned non-array response", [
                "response_type" => gettype($data),
                "response_body" => $response->body(),
            ]);
            throw new \Exception("Invalid response from Tabby API");
        }

        if ($data["status"] !== "created") {
            Log::error("Tabby session not created", ["response" => $data]);
            throw new \Exception("Customer not eligible for Tabby payment");
        }

        $paymentId = $data["payment"]["id"];

        // Check for installments in the correct location
        // Tabby API returns installments under configuration.products.installments
        $installments =
            $data["configuration"]["products"]["installments"] ?? null;

        // If installments exist and are available, get the web_url
        if (
            $installments &&
            isset($installments["is_available"]) &&
            $installments["is_available"]
        ) {
            // For successful sessions, installments might be in available_products
            $availableInstallments =
                $data["configuration"]["available_products"]["installments"] ??
                [];
            if (
                !empty($availableInstallments) &&
                isset($availableInstallments[0]["web_url"])
            ) {
                $webUrl = $availableInstallments[0]["web_url"];
            } else {
                Log::error(
                    "Tabby session created but no web_url in available products",
                    [
                        "response" => $data,
                        "payment_id" => $paymentId,
                    ],
                );
                throw new \Exception("Payment URL not available from Tabby");
            }
        } else {
            // If installments are not available, throw appropriate error
            $rejectionReason = $installments["rejection_reason"] ?? "unknown";
            Log::error("Tabby installments not available", [
                "response" => $data,
                "payment_id" => $paymentId,
                "rejection_reason" => $rejectionReason,
            ]);
            throw new \Exception(
                "Installments not available: " . $rejectionReason,
            );
        }

        // Create payment record
        try {
            // Payments relationship should be available on Payable implementations
            if (!$payable instanceof \App\Models\Order) {
                throw new \Exception(
                    "Tabby payment only supports Order payables",
                );
            }

            $payment = $payable->payments()->create([
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

            return $this->validateReturnArray(
                [$payment, $webUrl],
                "start_method",
            );
        } catch (\Exception $e) {
            Log::error("Failed to create Tabby payment record", [
                "error" => $e->getMessage(),
                "payable_type" => get_class($payable),
                "payable_id" =>
                    $payable instanceof \App\Models\Order
                        ? $payable->id
                        : "unknown",
                "payment_id" => $paymentId,
            ]);
            throw $e;
        }
    }

    /**
     * Validate and ensure proper return format
     */
    private function validateReturnArray($result, $context = "unknown"): array
    {
        if (!is_array($result)) {
            Log::error("Tabby service returned non-array", [
                "context" => $context,
                "result_type" => gettype($result),
                "result_value" => $result,
            ]);
            throw new \Exception(
                "Tabby service returned invalid response type",
            );
        }

        if (count($result) !== 2) {
            Log::error("Tabby service returned array with wrong count", [
                "context" => $context,
                "expected_count" => 2,
                "actual_count" => count($result),
                "result" => $result,
            ]);
            throw new \Exception(
                "Tabby service returned array with wrong element count",
            );
        }

        if (!isset($result[0]) || !isset($result[1])) {
            Log::error("Tabby service returned array with missing keys", [
                "context" => $context,
                "has_key_0" => isset($result[0]),
                "has_key_1" => isset($result[1]),
                "result" => $result,
            ]);
            throw new \Exception(
                "Tabby service returned array with missing required elements",
            );
        }

        return $result;
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
                $payment->status = PaymentStatus::cancelled;
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
                "reference_id" => (string) $payment->payable->id,
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

        // Calculate amount including 15% VAT for eligibility check
        $totalWithTax = $amount
            ->getAmount()
            ->multipliedBy(BigDecimal::of("1.15"));

        $payload = [
            "payment" => [
                "amount" => $totalWithTax->toScale(
                    2,
                    \Brick\Math\RoundingMode::HALF_UP,
                ),
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

    /**
     * Get customer order history for Tabby
     * Returns 5-10 previous orders (current order excluded)
     *
     * @param \App\Models\User $user
     * @param int|null $currentOrderId Order ID to exclude from history
     * @return array
     */
    protected function getOrderHistory($user, $currentOrderId = null): array
    {
        if (!$user->customer) {
            return [];
        }

        // Get up to 10 previous orders, excluding current order
        $query = $user->customer
            ->orders()
            ->with("orderItems.product.category") // Eager load relationships
            ->whereNotNull("created_at")
            ->orderBy("created_at", "desc")
            ->limit(10);

        // Exclude current order if provided
        if ($currentOrderId) {
            $query->where("id", "!=", $currentOrderId);
        }

        $orders = $query->get();

        return $orders
            ->map(function ($order) use ($user) {
                // Calculate order total using unit_price (decimal) not price (Money cast)
                $total = $order->orderItems->reduce(
                    fn($carry, $item) => $carry +
                        $item->unit_price * $item->quantity,
                    0,
                );

                $orderData = [
                    "purchased_at" => $order->created_at->toIso8601String(),
                    "amount" => number_format($total, 2, ".", ""),
                    "status" => $order->status?->value ?? "unknown",
                    "buyer" => [
                        "phone" => $user->phone ?? "",
                        "email" => $user->email,
                        "name" => $user->name,
                    ],
                    "items" => $order->orderItems
                        ->map(
                            fn($item) => [
                                "title" => $item->product->name ?? "Product",
                                "quantity" => $item->quantity,
                                "unit_price" => number_format(
                                    $item->unit_price,
                                    2,
                                    ".",
                                    "",
                                ),
                                "category" =>
                                    $item->product && $item->product->category
                                        ? $item->product->category->getTranslation(
                                            "name",
                                            "en",
                                        )
                                        : "General",
                            ],
                        )
                        ->toArray(),
                ];

                // Only include shipping address if it exists in the order
                if ($order->shipping_city) {
                    $orderData["shipping_address"] = [
                        "city" => $order->shipping_city,
                        "address" => $order->shipping_address ?? "",
                        "zip" => $order->shipping_zip ?? "",
                    ];
                }

                return $orderData;
            })
            ->toArray();
    }
}
