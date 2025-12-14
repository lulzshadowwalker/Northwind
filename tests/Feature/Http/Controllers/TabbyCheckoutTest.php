<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\TabbyPaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TabbyCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Customer $customer;

    protected Cart $cart;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user and customer
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'phone' => '+966500000001',
        ]);
        $this->customer = Customer::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Create test product
        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 1000, // SAR
        ]);

        // Create cart with item
        $this->cart = Cart::factory()->create([
            'customer_id' => $this->customer->id,
        ]);
        $this->cart->cartItems()->create([
            'product_id' => $this->product->id,
            'quantity' => 1,
        ]);

        // Set up cart relationship
        $this->customer->cart = $this->cart;
    }

    public function test_tabby_eligibility_check_success()
    {
        $this->actingAs($this->user);

        // Mock successful eligibility check
        Http::fake([
            'https://api.tabby.ai/api/v2/checkout' => Http::response(
                [
                    'status' => 'created',
                    'configuration' => [
                        'products' => [
                            'installments' => [
                                'rejection_reason' => null,
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $response = $this->postJson(
            route('checkout.tabby-eligibility', ['language' => 'en']),
            [
                'amount' => 1000,
                'currency' => 'SAR',
                'buyer' => [
                    'email' => 'otp.success@tabby.ai',
                    'phone' => '+966500000001',
                    'name' => 'Test Customer',
                ],
            ],
        );

        $response->assertStatus(200)->assertJson([
            'eligible' => true,
            'status' => 'created',
        ]);
    }

    public function test_tabby_eligibility_check_rejection()
    {
        $this->actingAs($this->user);

        // Mock rejection eligibility check
        Http::fake([
            'https://api.tabby.ai/api/v2/checkout' => Http::response(
                [
                    'status' => 'rejected',
                    'configuration' => [
                        'products' => [
                            'installments' => [
                                'rejection_reason' => 'order_amount_too_high',
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $response = $this->postJson(
            route('checkout.tabby-eligibility', ['language' => 'en']),
            [
                'amount' => 1000,
                'currency' => 'SAR',
                'buyer' => [
                    'email' => 'otp.success@tabby.ai',
                    'phone' => '+966500000002',
                    'name' => 'Test Customer',
                ],
            ],
        );

        $response->assertStatus(200)->assertJson([
            'eligible' => false,
            'status' => 'rejected',
            'reason' => 'order_amount_too_high',
        ]);
    }

    public function test_tabby_checkout_session_creation()
    {
        $this->actingAs($this->user);

        // Mock session creation
        Http::fake([
            'https://api.tabby.ai/api/v2/checkout' => Http::response(
                [
                    'status' => 'created',
                    'payment' => ['id' => 'test_payment_id_123'],
                    'configuration' => [
                        'available_products' => [
                            'installments' => [
                                ['web_url' => 'https://checkout.tabby.ai/test'],
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $response = $this->post(route('checkout.store', ['language' => 'en']), [
            'payment_method' => 'tabby',
        ]);

        $response->assertRedirect('https://checkout.tabby.ai/test');

        // Check that order and payment were created
        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->id,
        ]);

        $this->assertDatabaseHas('payments', [
            'gateway' => PaymentGateway::tabby,
            'external_reference' => 'test_payment_id_123',
            'status' => PaymentStatus::pending,
        ]);
    }

    public function test_tabby_payment_callback_success()
    {
        // Create test order and payment
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $payment = Payment::factory()->create([
            'payable_type' => Order::class,
            'payable_id' => $order->id,
            'external_reference' => 'test_payment_id_123',
            'gateway' => PaymentGateway::tabby,
            'status' => PaymentStatus::pending,
        ]);

        // Mock payment verification and capture
        Http::fake([
            'https://api.tabby.ai/api/v2/payments/test_payment_id_123' => Http::response(
                [
                    'status' => 'AUTHORIZED',
                    'id' => 'test_payment_id_123',
                    'amount' => '98.88',
                ],
                200,
            ),
            'https://api.tabby.ai/api/v2/payments/test_payment_id_123/captures' => Http::response(
                [
                    'id' => 'capture_123',
                    'amount' => '98.88',
                ],
                200,
            ),
        ]);

        $response = $this->get(
            route('payments.callback', [
                'language' => 'en',
                'payment_id' => 'test_payment_id_123',
            ]),
        );

        $response
            ->assertRedirect(route('home.index', ['language' => 'en']))
            ->assertSessionHas('success');

        // Check payment was updated
        $payment->refresh();
        $this->assertEquals(PaymentStatus::paid, $payment->status);

        // Check cart was cleared
        $this->customer->cart->refresh();
        $this->assertTrue($this->customer->cart->cartItems()->count() === 0);
    }

    public function test_tabby_payment_callback_failure()
    {
        // Create test order and payment
        $order = Order::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $payment = Payment::factory()->create([
            'payable_type' => Order::class,
            'payable_id' => $order->id,
            'external_reference' => 'invalid_payment_id',
            'gateway' => PaymentGateway::tabby,
            'status' => PaymentStatus::pending,
        ]);

        // Mock failed payment verification
        Http::fake([
            'https://api.tabby.ai/api/v2/payments/invalid_payment_id' => Http::response(
                [
                    'error' => 'Payment not found',
                ],
                404,
            ),
        ]);

        $response = $this->get(
            route('payments.callback', [
                'language' => 'en',
                'payment_id' => 'invalid_payment_id',
            ]),
        );

        $response
            ->assertRedirect(route('home.index', ['language' => 'en']))
            ->assertSessionHas('error');

        // Check payment status wasn't changed
        $payment->refresh();
        $this->assertEquals(PaymentStatus::pending, $payment->status);
    }

    public function test_checkout_shows_tabby_payment_method()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('checkout.index', ['language' => 'en']));

        $response
            ->assertStatus(200)
            ->assertSee('Pay later with Tabby')
            ->assertSee('tabby-payment-method');
    }

    public function test_tabby_service_returns_payment_methods()
    {
        $tabbyService = app(TabbyPaymentGatewayService::class);

        $methods = $tabbyService->paymentMethods(
            \Brick\Money\Money::of(1000, 'SAR'),
        );

        $this->assertCount(1, $methods);
        $this->assertEquals('tabby', $methods[0]->id);
        $this->assertEquals('Pay later with Tabby', $methods[0]->name);
    }

    public function test_tabby_eligibility_service_method()
    {
        // Mock eligibility check
        Http::fake([
            'https://api.tabby.ai/api/v2/checkout' => Http::response(
                [
                    'status' => 'created',
                    'configuration' => [
                        'products' => [
                            'installments' => [
                                'rejection_reason' => null,
                            ],
                        ],
                    ],
                ],
                200,
            ),
        ]);

        $tabbyService = app(TabbyPaymentGatewayService::class);

        $result = $tabbyService->checkEligibility(
            \Brick\Money\Money::of(1000, 'SAR'),
            [
                'email' => 'otp.success@tabby.ai',
                'phone' => '+966500000001',
                'name' => 'Test Customer',
            ],
        );

        $this->assertArrayHasKey('eligible', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertTrue($result['eligible']);
        $this->assertEquals('created', $result['status']);
    }
}
