<?php

namespace App\Actions;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Brick\Math\BigDecimal;
use Illuminate\Support\Facades\DB;

class CreateOrderFromCart
{
    public static function make(): self
    {
        return new self();
    }

    public function execute(
        Cart $cart,
        ?string $promoCode = null,
        bool $clearCart = false,
    ): Order {
        if ($cart->isEmpty) {
            throw new \InvalidArgumentException(
                "Cannot create order from empty cart.",
            );
        }

        return DB::transaction(function () use ($cart, $promoCode, $clearCart) {
            $subtotal = $cart->total;
            $discountAmount = BigDecimal::zero(); // TODO: Calculate discount based on promo code
            $total = $subtotal->minus($discountAmount);

            $order = Order::create([
                "order_number" => $this->generateOrderNumber(),
                "status" => \App\Enums\OrderStatus::new,
                "subtotal" => $subtotal->getAmount()->toFloat(),
                "discount_amount" => $discountAmount->toFloat(),
                "total" => $total->getAmount()->toFloat(),
                "promo_code" => $promoCode,
                "customer_id" => $cart->customer_id,
            ]);

            foreach ($cart->cartItems as $cartItem) {
                $product = $cartItem->product;
                $unitPrice = $product->price->getAmount();
                $itemSubtotal = $unitPrice->multipliedBy($cartItem->quantity);
                $itemTotal = $itemSubtotal; // No item-level discounts for now

                OrderItem::create([
                    "product_name" => $product->name,
                    "quantity" => $cartItem->quantity,
                    "unit_price" => $unitPrice->toFloat(),
                    "subtotal" => $itemSubtotal->toFloat(),
                    "total" => $itemTotal->toFloat(),
                    "order_id" => $order->id,
                    "product_id" => $product->id,
                ]);
            }

            // Clear cart after successful order creation
            if ($clearCart) {
                $cart->cartItems()->delete();
            }

            return $order;
        });
    }

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = "ORD-" . strtoupper(uniqid());
        } while (Order::where("order_number", $orderNumber)->exists());

        return $orderNumber;
    }
}
