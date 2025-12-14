<?php

namespace Tests\Feature;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\TabbyPaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TabbyCaptureFixTest extends TestCase
{
    use RefreshDatabase;

    protected TabbyPaymentGatewayService $tabbyService;

    protected User $user;

    protected Order $order;

    protected Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tabbyService = app(TabbyPaymentGatewayService::class);

        $this->user = User::factory()->create();
        $customer = \App\Models\Customer::factory()->create(['user_id' => $this->user->id]);

        // Create a dummy order
        $this->order = Order::factory()->create([
            'customer_id' => $customer->id,
            'total' => 573.85, // The "wrong" local amount from the email example
        ]);

        // Create a payment record linked to this order
        $this->payment = Payment::create([
            'payable_type' => Order::class,
            'payable_id' => $this->order->id,
            'user_id' => $this->user->id,
            'gateway' => PaymentGateway::tabby,
            'external_reference' => 'payment_id_123',
            'amount' => 573.85, // Local amount mismatching Tabby
            'currency' => 'SAR',
            'status' => PaymentStatus::pending,
        ]);
    }

    /** @test */
    public function it_uses_tabby_authorized_amount_for_capture_instead_of_local_amount()
    {
        // Mock Tabby API responses
        Http::fake([
            // 1. Mock the GET payment request to return a DIFFERENT amount (499.00)
            '*/api/v2/payments/payment_id_123' => Http::response([
                'id' => 'payment_id_123',
                'status' => 'AUTHORIZED',
                'amount' => '499.00', // The authoritative amount from Tabby
                'currency' => 'SAR',
                'order' => ['reference_id' => (string) $this->order->id],
                'buyer' => ['name' => 'Test User'],
                'buyer_history' => [],
                'order_history' => [],
            ], 200),

            // 2. Mock the Capture request
            '*/api/v2/payments/payment_id_123/captures' => Http::response([
                'id' => 'capture_123',
                'amount' => '499.00',
                'status' => 'CLOSED',
            ], 200),
        ]);

        // Run the capture
        $this->tabbyService->capture($this->payment);

        // Assertions

        // 1. Verify the Capture Request was sent with the CORRECT amount (499.00) not (573.85)
        Http::assertSent(function ($request) {
            return $request->url() == config('services.tabby.base_url', 'https://api.tabby.ai').'/api/v2/payments/payment_id_123/captures' &&
                   $request['amount'] === '499.00' && // MUST match Tabby's amount
                   is_string($request['reference_id']); // QA Requirement: Must be string
        });

        // 2. Verify local payment record was updated
        $this->payment->refresh();

        $this->assertEquals('499.00', $this->payment->amount);
        $this->assertNotNull($this->payment->captured_at);
    }

    /** @test */
    public function webhook_triggers_capture_with_correct_logic()
    {
        // Set config for signature verification
        config(['services.tabby.webhook_signature_header' => 'X-Tabby-Signature']);
        config(['services.tabby.webhook_signature_value' => 'secret-signature']);

        // Mock Tabby API responses
        Http::fake([
            '*/api/v2/payments/payment_id_123' => Http::response([
                'id' => 'payment_id_123',
                'status' => 'AUTHORIZED',
                'amount' => '499.00',
                'currency' => 'SAR',
                'order' => ['reference_id' => (string) $this->order->id],
                'buyer' => ['name' => 'Test User'],
                'buyer_history' => [],
                'order_history' => [],
            ], 200),

            '*/api/v2/payments/payment_id_123/captures' => Http::response([
                'id' => 'capture_123',
                'amount' => '499.00',
                'status' => 'CLOSED',
            ], 200),
        ]);

        // Simulate Webhook Request with Signature
        $response = $this->postJson('/api/webhooks/tabby', [
            'id' => 'payment_id_123',
            'status' => 'authorized', // Lowercase as per documentation/email
            'amount' => '499.00',
        ], [
            'X-Tabby-Signature' => 'secret-signature',
        ]);

        $response->assertStatus(200);

        // Verify capture was called via the service
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/captures') &&
                   $request['amount'] === '499.00';
        });

        $this->payment->refresh();
        $this->assertEquals(PaymentStatus::paid, $this->payment->status);
        $this->assertNotNull($this->payment->captured_at);
    }
}
