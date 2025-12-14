<?php

namespace App\Support;

use Brick\Money\Money;

class CartTotal
{
    public function __construct(
        public Money $subtotal,
        public Money $shipping,
        public Money $tax,
        public Money $total
    ) {
        //
    }
}
