<?php

namespace Tests\Unit\Actions;

use App\Actions\CalculateCartTotal;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculateCartTotalTest extends TestCase
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

    public function test_it_calculates_cart_total_correctly()
    {
        // Arrange
        $cart = Cart::factory()->create();
        $product1 = Product::factory()->create(['amount' => 100, 'sale_amount' => null]); // 100 SAR
        $product2 = Product::factory()->create(['amount' => 200, 'sale_amount' => null]); // 200 SAR

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product1->id,
            'quantity' => 2, // 200 SAR
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product2->id,
            'quantity' => 1, // 200 SAR
        ]);

        // Subtotal = 400
        // Tax = 400 * 0.15 = 60
        // Total = 460

        $action = new CalculateCartTotal();

        // Act
        $cartTotal = $action->execute($cart);

        // Assert
        $this->assertEquals(400.00, $cartTotal->subtotal->getAmount()->toFloat());
        $this->assertEquals(0.00, $cartTotal->shipping->getAmount()->toFloat());
        $this->assertEquals(60.00, $cartTotal->tax->getAmount()->toFloat());
        $this->assertEquals(460.00, $cartTotal->total->getAmount()->toFloat());
    }

    public function test_it_calculates_cart_total_with_sale_prices()
    {
        // Arrange
        $cart = Cart::factory()->create();
        $product1 = Product::factory()->create([
            'amount' => 100,
            'sale_amount' => 80 // Sale price 80 SAR
        ]); 
        $product2 = Product::factory()->create(['amount' => 200]); // 200 SAR

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product1->id,
            'quantity' => 2, // 160 SAR (80 * 2)
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product2->id,
            'quantity' => 1, // 200 SAR
        ]);

        // Subtotal = 160 + 200 = 360
        // Tax = 360 * 0.15 = 54
        // Total = 360 + 54 = 414

        $action = new CalculateCartTotal();

        // Act
        $cartTotal = $action->execute($cart);

        // Assert
        $this->assertEquals(360.00, $cartTotal->subtotal->getAmount()->toFloat());
        $this->assertEquals(0.00, $cartTotal->shipping->getAmount()->toFloat());
        $this->assertEquals(54.00, $cartTotal->tax->getAmount()->toFloat());
        $this->assertEquals(414.00, $cartTotal->total->getAmount()->toFloat());
    }
    
    public function test_it_handles_empty_cart()
    {
        $cart = Cart::factory()->create();
        $action = new CalculateCartTotal();
        
        $cartTotal = $action->execute($cart);
        
        $this->assertEquals(0.00, $cartTotal->subtotal->getAmount()->toFloat());
        $this->assertEquals(0.00, $cartTotal->total->getAmount()->toFloat());
    }
}
