<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use App\Services\TabbyPaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TabbyCheckoutTest extends TestCase
{
    use RefreshDatabase;

    //  WARNING: We need to make sure it's not actually making api calls to Tabby

    // protected User $user;
    // protected Customer $customer;
    // protected Cart $cart;
    // protected Product $product;

    // protected function setUp(): void
    // {
    //     parent::setUp();

    //     // Create test user and customer
    //     $this->user = User::factory()->create([
    //         "email" => "test@example.com",
    //         "phone" => "+966500000001",
    //     ]);
    //     $this->customer = Customer::factory()->create([
    //         "user_id" => $this->user->id,
    //     ]);

    //     // Create test product
    //     $this->product = Product::factory()->create([
    //         "name" => "Test Product",
    //         "price" => 1000, // SAR
    //     ]);

    //     // Create cart with item
    //     $this->cart = Cart::factory()->create([
    //         "customer_id" => $this->customer->id,
    //     ]);
    //     $this->cart->cartItems()->create([
    //         "product_id" => $this->product->id,
    //         "quantity" => 1,
    //     ]);

    //     // Set up cart relationship
    //     $this->customer->cart = $this->cart;
    // }

    // public function test_tabby_eligibility_check_success()
    // {
    //     $this->actingAs($this->user);

    //     $response = $this->postJson(
    //         route("checkout.tabby-eligibility", ["language" => "en"]),
    //         [
    //             "amount" => 1000,
    //             "currency" => "SAR",
    //             "buyer" => [
    //                 "email" => "otp.success@tabby.ai",
    //                 "phone" => "+966500000001",
    //                 "name" => "Test Customer",
    //             ],
    //         ],
    //     );

    //     $response->assertStatus(200)->assertJson([
    //         "eligible" => true,
    //         "status" => "created",
    //     ]);
    // }

    // public function test_tabby_eligibility_check_rejection()
    // {
    //     $this->actingAs($this->user);

    //     $response = $this->postJson(
    //         route("checkout.tabby-eligibility", ["language" => "en"]),
    //         [
    //             "amount" => 1000,
    //             "currency" => "SAR",
    //             "buyer" => [
    //                 "email" => "otp.success@tabby.ai",
    //                 "phone" => "+966500000002", // Rejection phone
    //                 "name" => "Test Customer",
    //             ],
    //         ],
    //     );

    //     $response->assertStatus(200)->assertJson([
    //         "eligible" => false,
    //         "status" => "rejected",
    //     ]);
    // }

    // public function test_tabby_checkout_session_creation()
    // {
    //     $this->actingAs($this->user);

    //     // Mock HTTP client for Tabby API
    //     $this->mockHttpClient();

    //     $response = $this->post(route("checkout.store", ["language" => "en"]), [
    //         "payment_method" => "tabby",
    //     ]);

    //     $response->assertRedirect();
    //     // Should redirect to Tabby HPP URL
    // }

    // public function test_tabby_payment_callback_success()
    // {
    //     // Mock successful payment callback
    //     $paymentId = "test_payment_id_" . time();

    //     $response = $this->get(
    //         route("payments.callback", [
    //             "language" => "en",
    //             "payment_id" => $paymentId,
    //         ]),
    //     );

    //     $response->assertRedirect();
    //     // Should redirect to success page
    // }

    // public function test_tabby_payment_callback_failure()
    // {
    //     // Mock failed payment callback
    //     $response = $this->get(
    //         route("payments.callback", [
    //             "language" => "en",
    //             "payment_id" => "invalid_payment_id",
    //         ]),
    //     );

    //     $response->assertRedirect();
    //     // Should redirect to home with error
    // }

    // public function test_checkout_shows_tabby_payment_method()
    // {
    //     $this->actingAs($this->user);

    //     $response = $this->get(route("checkout.index", ["language" => "en"]));

    //     $response
    //         ->assertStatus(200)
    //         ->assertSee("Pay later with Tabby")
    //         ->assertSee("tabby-payment-method");
    // }

    // public function test_tabby_service_returns_payment_methods()
    // {
    //     $tabbyService = app(TabbyPaymentGatewayService::class);

    //     $methods = $tabbyService->paymentMethods(
    //         \Brick\Money\Money::of(1000, "SAR"),
    //     );

    //     $this->assertCount(1, $methods);
    //     $this->assertEquals("tabby", $methods[0]->id);
    //     $this->assertEquals("Pay later with Tabby", $methods[0]->name);
    // }

    // public function test_tabby_eligibility_service_method()
    // {
    //     $tabbyService = app(TabbyPaymentGatewayService::class);

    //     $result = $tabbyService->checkEligibility(
    //         \Brick\Money\Money::of(1000, "SAR"),
    //         [
    //             "email" => "otp.success@tabby.ai",
    //             "phone" => "+966500000001",
    //             "name" => "Test Customer",
    //         ],
    //     );

    //     $this->assertArrayHasKey("eligible", $result);
    //     $this->assertArrayHasKey("status", $result);
    // }

    // protected function mockHttpClient()
    // {
    //     // Mock HTTP client for testing
    //     // This would mock the actual Tabby API calls
    //     // For now, we'll skip detailed mocking
    // }
}
