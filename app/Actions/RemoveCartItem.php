<?php

namespace App\Actions;

use App\Models\CartItem;

class RemoveCartItem
{
    public static function make(): self
    {
        return new self;
    }

    public function execute(CartItem $item)
    {
        $item->touch();
        $item->delete();
    }
}
