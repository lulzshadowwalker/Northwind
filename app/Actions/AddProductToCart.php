<?php

namespace App\Actions;

use App\Factories\CartFactory;
use App\Models\Cart;
use App\Models\Product;

class AddProductToCart
{
    public static function make(): self
    {
        return new self;
    }

    public function execute(Product $product, int $quantity = 1, ?Cart $cart = null)
    {
        $item = ($cart ?: CartFactory::make())->cartItems()->firstOrCreate(
            ['product_id' => $product->id],
            ['quantity' => 0]
        );

        $item->increment('quantity', $quantity);
        $item->touch();
    }
}
