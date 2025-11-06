<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Customer;
use App\Models\Faq;
use App\Models\Favorite;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\PromoCode;
use App\Models\Review;
use App\Models\SupportMessage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create an admin user
        User::factory()->create([
            "name" => "Admin User",
            "email" => "admin@example.com",
            "password" => "password",
            "is_admin" => true,
        ]);

        // Create customers
        $customers = Customer::factory(10)->create();

        // Create categories
        $categories = Category::factory(3)->create();

        // Create collections
        $collections = Collection::factory(4)->create();

        $products = Product::factory(12)->create();

        // Attach products to random collections (many-to-many)
        foreach ($products as $product) {
            $product
                ->collections()
                ->attach(
                    $collections->random(rand(1, 2))->pluck("id")->toArray(),
                );
        }

        // Create reviews for products by random customers
        foreach ($products as $product) {
            Review::factory(rand(2, 5))->create([
                "product_id" => $product->id,
                "customer_id" => $customers->random()->id,
            ]);
        }

        // Create favorites for customers
        foreach ($customers as $customer) {
            Favorite::factory(rand(2, 4))->create([
                "customer_id" => $customer->id,
                "product_id" => $products->random()->id,
            ]);
        }

        // Create product questions
        foreach ($products as $product) {
            ProductQuestion::factory(rand(1, 3))->create([
                "product_id" => $product->id,
                "customer_id" => $customers->random()->id,
            ]);
        }

        // Create promo codes
        PromoCode::factory(4)->create();

        // Create newsletter subscribers
        NewsletterSubscriber::factory(15)->create();

        // Create carts for some customers and add items
        foreach ($customers->take(5) as $customer) {
            $cart = Cart::factory()->create([
                "customer_id" => $customer->id,
            ]);
            CartItem::factory(rand(1, 3))->create([
                "cart_id" => $cart->id,
                "product_id" => Product::inRandomOrder()->first()->id,
            ]);
        }

        // Create a guest cart
        $guestCart = Cart::factory()->create([
            "customer_id" => null,
            "session_id" => Str::uuid(),
        ]);
        CartItem::factory(rand(1, 2))->create([
            "cart_id" => $guestCart->id,
            "product_id" => Product::inRandomOrder()->first()->id,
        ]);

        // Create orders for some customers with order items
        foreach ($customers->take(6) as $customer) {
            $order = Order::factory()->create([
                "customer_id" => $customer->id,
            ]);
            $orderItems = OrderItem::factory(rand(1, 3))->create([
                "order_id" => $order->id,
                "product_id" => Product::inRandomOrder()->first()->id,
            ]);
        }

        SupportMessage::factory()->count(10)->create();
        Faq::factory()->count(5)->create();
    }
}
