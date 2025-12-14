<?php

namespace App\Actions;

use App\Models\Cart;
use App\Models\CartItem;
use App\Support\CartTotal;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Money;

class CalculateCartTotal
{
    private const TAX_RATE = 0.15; // 15% VAT

    public function execute(Cart $cart): CartTotal
    {
        $currency = $cart->cartItems->first()?->product?->price?->getCurrency()->getCurrencyCode() ?? 'SAR';

        // Calculate Subtotal
        $subtotalAmount = $cart->cartItems->reduce(function (BigDecimal $carry, CartItem $item) {
            $price = $item->product->sale_price ?? $item->product->price;

            return $carry->plus(
                $price->getAmount()->multipliedBy($item->quantity)
            );
        }, BigDecimal::zero());

        $subtotal = Money::of($subtotalAmount, $currency, roundingMode: RoundingMode::HALF_UP);

        // Calculate Shipping (Fixed at 0 for now as per current implementation)
        $shipping = Money::of(0, $currency, roundingMode: RoundingMode::HALF_UP);

        // Calculate Tax (15% of Subtotal)
        $tax = $subtotal->multipliedBy(self::TAX_RATE, roundingMode: RoundingMode::HALF_UP);

        // Calculate Total
        $total = $subtotal->plus($shipping)->plus($tax);

        return new CartTotal(
            subtotal: $subtotal,
            shipping: $shipping,
            tax: $tax,
            total: $total
        );
    }
}
