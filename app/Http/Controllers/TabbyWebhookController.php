<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\TabbyPaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TabbyWebhookController extends Controller
{
    public function __construct(
        protected TabbyPaymentGatewayService $tabbyService,
    ) {}

    /**
     * Handle Tabby webhook notifications
     * Triggered when payment status changes (especially AUTHORIZED)
     */
    public function handle(Request $request)
    {
        // Log webhook received
        Log::info("Tabby webhook received", [
            "payload" => $request->all(),
            "headers" => $request->headers->all(),
        ]);

        // Verify webhook signature if header is configured
        $signatureHeader = config("services.tabby.webhook_signature_header");
        $signatureValue = config("services.tabby.webhook_signature_value");

        if ($signatureHeader && $signatureValue) {
            $receivedSignature = $request->header($signatureHeader);
            if ($receivedSignature !== $signatureValue) {
                Log::warning("Tabby webhook signature mismatch", [
                    "expected_header" => $signatureHeader,
                    "received" => $receivedSignature,
                ]);
                return response()->json(["error" => "Invalid signature"], 403);
            }
        }

        // Extract payment ID from webhook
        $paymentId = $request->input("id") ?? $request->input("payment_id");
        $status = $request->input("status");

        if (!$paymentId) {
            Log::error("Tabby webhook missing payment ID", [
                "payload" => $request->all(),
            ]);
            return response()->json(["error" => "Missing payment ID"], 400);
        }

        // Find payment record
        $payment = Payment::where("external_reference", $paymentId)->first();

        if (!$payment) {
            Log::warning("Tabby webhook payment not found", [
                "payment_id" => $paymentId,
            ]);
            // Return 200 to prevent retries for non-existent payments
            return response()->json(["message" => "Payment not found"], 200);
        }

        // Check if payment is still pending (avoid processing already completed payments)
        if (!in_array($payment->status, [PaymentStatus::pending])) {
            Log::info("Tabby webhook: payment already processed", [
                "payment_id" => $paymentId,
                "current_status" => $payment->status->value,
            ]);
            return response()->json(["message" => "Already processed"], 200);
        }

        try {
            // Handle AUTHORIZED status - capture payment
            if ($status === "AUTHORIZED") {
                Log::info("Tabby webhook: handling AUTHORIZED payment", [
                    "payment_id" => $paymentId,
                ]);

                // Verify payment status with Tabby API
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    "Authorization" =>
                        "Bearer " . $this->tabbyService->secretKey ??
                        config("services.tabby.secret_key"),
                ])->get(
                    ($this->tabbyService->baseUrl ??
                        config("services.tabby.base_url")) .
                        "/api/v2/payments/" .
                        $paymentId,
                );

                if (!$response->successful()) {
                    Log::error("Tabby webhook: failed to verify payment", [
                        "payment_id" => $paymentId,
                        "response" => $response->body(),
                    ]);
                    return response()->json(
                        ["error" => "Failed to verify payment"],
                        500,
                    );
                }

                $paymentData = $response->json();

                // Update payment record
                $payment->status = PaymentStatus::paid;
                $payment->details = $paymentData;
                $payment->save();

                // Capture payment if not already captured
                if (!$payment->captured_at) {
                    $capturePayload = [
                        "amount" => (string) $payment->amount,
                        "reference_id" => (string) $payment->payable_id,
                    ];

                    $captureResponse = \Illuminate\Support\Facades\Http::withHeaders(
                        [
                            "Authorization" =>
                                "Bearer " .
                                ($this->tabbyService->secretKey ??
                                    config("services.tabby.secret_key")),
                            "Content-Type" => "application/json",
                        ],
                    )->post(
                        ($this->tabbyService->baseUrl ??
                            config("services.tabby.base_url")) .
                            "/api/v2/payments/" .
                            $paymentId .
                            "/captures",
                        $capturePayload,
                    );

                    if ($captureResponse->successful()) {
                        $payment->captured_at = now();
                        $payment->save();

                        Log::info("Tabby webhook: payment captured", [
                            "payment_id" => $paymentId,
                        ]);

                        // Clear customer cart
                        $customer = $payment->payable->customer ?? null;
                        if ($customer && $customer->cart) {
                            $customer->cart->cartItems()->delete();
                            Log::info("Tabby webhook: cart cleared", [
                                "payment_id" => $paymentId,
                                "customer_id" => $customer->id,
                            ]);
                        }
                    } else {
                        Log::error("Tabby webhook: capture failed", [
                            "payment_id" => $paymentId,
                            "response" => $captureResponse->body(),
                        ]);
                    }
                }

                return response()->json([
                    "message" => "Payment processed successfully",
                ]);
            }

            // Handle other statuses
            Log::info("Tabby webhook: received status update", [
                "payment_id" => $paymentId,
                "status" => $status,
            ]);

            return response()->json(["message" => "Webhook received"]);
        } catch (\Exception $e) {
            Log::error("Tabby webhook processing error", [
                "payment_id" => $paymentId,
                "error" => $e->getMessage(),
                "trace" => $e->getTraceAsString(),
            ]);

            return response()->json(["error" => "Internal server error"], 500);
        }
    }
}
