<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use Brick\Money\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_display_price_returns_regular_price_when_no_sale_price_set()
    {
        $product = Product::factory()->create([
            'price' => 100,
            'sale_price' => null,
            'sale_end_date' => null,
        ]);

        $this->assertTrue($product->displayPrice->isEqualTo(Money::of(100, 'SAR')));
    }

    public function test_display_price_returns_sale_price_when_set_without_date()
    {
        $product = Product::factory()->create([
            'price' => 100,
            'sale_price' => 80,
            'sale_end_date' => null,
        ]);

        $this->assertTrue($product->displayPrice->isEqualTo(Money::of(80, 'SAR')));
    }

    public function test_display_price_returns_sale_price_when_sale_is_active()
    {
        $product = Product::factory()->create([
            'price' => 100,
            'sale_price' => 80,
            'sale_end_date' => now()->addDay(),
        ]);

        $this->assertTrue($product->displayPrice->isEqualTo(Money::of(80, 'SAR')));
    }

    public function test_display_price_returns_regular_price_when_sale_is_expired()
    {
        $product = Product::factory()->create([
            'price' => 100,
            'sale_price' => 80,
            'sale_end_date' => now()->subDay(),
        ]);

        // MoneyCast should return null for sale_price when expired
        // So displayPrice should fall back to price
        $this->assertTrue($product->displayPrice->isEqualTo(Money::of(100, 'SAR')));
    }
}
