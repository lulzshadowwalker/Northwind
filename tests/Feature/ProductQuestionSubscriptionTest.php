<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\User;
use App\Notifications\ProductQuestionAnswered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ProductQuestionSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_subscribe_to_product_question()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $question = ProductQuestion::factory()->create([
            'product_id' => $product->id,
            'answer' => null,
        ]);

        $response = $this->actingAs($user)
            ->post(route('products.questions.subscribe', [
                'language' => 'en',
                'productQuestion' => $question->id,
            ]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('product_question_subscriptions', [
            'product_question_id' => $question->id,
            'email' => $user->email,
            'user_id' => $user->id,
        ]);
    }

    public function test_guest_cannot_subscribe_to_product_question()
    {
        $product = Product::factory()->create();
        $question = ProductQuestion::factory()->create([
            'product_id' => $product->id,
            'answer' => null,
        ]);

        $response = $this->post(route('products.questions.subscribe', [
            'language' => 'en',
            'productQuestion' => $question->id,
        ]));

        $response->assertStatus(403);
    }

    public function test_asker_is_notified_when_question_is_answered()
    {
        Notification::fake();

        $product = Product::factory()->create();
        $question = ProductQuestion::factory()->create([
            'product_id' => $product->id,
            'email' => 'asker@example.com',
            'answer' => null,
        ]);

        $question->update(['answer' => 'This is the answer.']);

        Notification::assertSentTo(
            new \Illuminate\Notifications\AnonymousNotifiable,
            ProductQuestionAnswered::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === 'asker@example.com';
            }
        );
    }

    public function test_subscriber_is_notified_when_question_is_answered()
    {
        Notification::fake();

        $user = User::factory()->create(['email' => 'subscriber@example.com']);
        $product = Product::factory()->create();
        $question = ProductQuestion::factory()->create([
            'product_id' => $product->id,
            'email' => 'asker@example.com',
            'answer' => null,
        ]);

        $question->subscriptions()->create([
            'email' => $user->email,
            'user_id' => $user->id,
        ]);

        $question->update(['answer' => 'This is the answer.']);

        Notification::assertSentTo(
            new \Illuminate\Notifications\AnonymousNotifiable,
            ProductQuestionAnswered::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === 'subscriber@example.com';
            }
        );

        // Asker should also be notified
        Notification::assertSentTo(
            new \Illuminate\Notifications\AnonymousNotifiable,
            ProductQuestionAnswered::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === 'asker@example.com';
            }
        );
    }

    public function test_subscription_record_is_only_created_once_per_email()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $question = ProductQuestion::factory()->create([
            'product_id' => $product->id,
            'answer' => null,
        ]);

        // First subscription attempt
        $response1 = $this->actingAs($user)
            ->post(route('products.questions.subscribe', [
                'language' => 'en',
                'productQuestion' => $question->id,
            ]));

        // Second subscription attempt
        $response2 = $this->actingAs($user)
            ->post(route('products.questions.subscribe', [
                'language' => 'en',
                'productQuestion' => $question->id,
            ]));

        $response1->assertRedirect();
        $response1->assertSessionHas('success');

        $response2->assertRedirect();
        $response2->assertSessionHas('success');

        $this->assertDatabaseCount('product_question_subscriptions', 1);
    }
}
