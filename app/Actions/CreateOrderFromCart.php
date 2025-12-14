<?php

namespace App\Actions;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Actions\CalculateCartTotal;
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
            $cartTotal = app(CalculateCartTotal::class)->execute($cart);
            
            $subtotal = $cartTotal->subtotal;
            $discountAmount = BigDecimal::zero(); // TODO: Calculate discount based on promo code
            
            // Note: If discount is applied, tax should be recalculated. 
            // But CalculateCartTotal doesn't support discount yet.
            // For now, we just subtract discount from total (which includes tax).
            // Ideally, we should pass discount to CalculateCartTotal.
            
            $total = $cartTotal->total->minus($discountAmount);

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
                $price = $product->sale_price ?? $product->price;
                $unitPrice = $price->getAmount();
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
