<?php

namespace App\Actions;

use App\Models\CartItem;

class DecrementCartItem
{
    public static function make(): self
    {
        return new self;
    }

    public function execute(CartItem $item, int $quantity = 1)
    {
        if ($item->quantity <= $quantity) {
            $item->delete();

            return;
        }

        $item->decrement('quantity', $quantity);
        $item->touch();
    }
}
