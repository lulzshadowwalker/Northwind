<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 500);
        $hasDiscount = fake()->boolean(30);
        $discountAmount = $hasDiscount
            ? fake()->randomFloat(2, 5, min(50, $subtotal * 0.3))
            : 0;
        $total = $subtotal - $discountAmount;
        $statuses = OrderStatus::values();
        $promoCodes = ['WELCOME10', 'SUMMER20', 'VIP15', null, null, null];

        return [
            'order_number' => 'ORD-'.strtoupper(fake()->bothify('##??##??')),
            'status' => fake()->randomElement($statuses),
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'total' => $total,
            'promo_code' => fake()->randomElement($promoCodes),
            'customer_id' => Customer::factory(),
        ];
    }
}
