<?php

namespace Tests\Unit\Actions;

use App\Actions\CreateOrderFromCart;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOrderFromCartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Disable Scout search syncing
        if (method_exists(Product::class, 'disableSearchSyncing')) {
            Product::disableSearchSyncing();
        }
    }

    public function test_it_creates_order_with_correct_prices_including_sale_prices()
    {
        // Arrange
        $cart = Cart::factory()->create();

        // Product 1: Regular price 100
        $product1 = Product::factory()->create(['amount' => 100, 'sale_amount' => null]);

        // Product 2: Regular price 200, Sale price 150
        $product2 = Product::factory()->create(['amount' => 200, 'sale_amount' => 150]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product1->id,
            'quantity' => 2, // 2 * 100 = 200
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product2->id,
            'quantity' => 1, // 1 * 150 = 150
        ]);

        // Expected Subtotal = 200 + 150 = 350
        // Expected Tax = 350 * 0.15 = 52.5
        // Expected Total = 350 + 52.5 = 402.5

        $action = CreateOrderFromCart::make();

        // Act
        $order = $action->execute($cart);

        // Assert
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'subtotal' => 350.00,
            'total' => 402.50,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'unit_price' => 100.00,
            'subtotal' => 200.00,
            'total' => 200.00,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'unit_price' => 150.00,
            'subtotal' => 150.00,
            'total' => 150.00,
        ]);
    }
}
