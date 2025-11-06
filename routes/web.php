<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterSubscriberController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductQuestionController;
use App\Http\Controllers\ReturnPolicyController;
use App\Http\Controllers\TermsController;
use App\Http\Controllers\Web\CartItemController;
use App\Http\Middleware\LanguageMiddleware;
use Illuminate\Support\Facades\Route;

Route::get("/", fn() => redirect("/en"));

Route::prefix("{language}")
    ->middleware(LanguageMiddleware::class)
    ->group(function () {
        Route::get("/", [HomeController::class, "index"])->name("home.index");

        Route::get("/products", [ProductController::class, "index"])->name(
            "products.index",
        );

        Route::get("/collections", function () {
            throw new Exception("Collections page not implemented yet");
        })->name("collections.index");

        Route::get("/contact", [ContactController::class, "index"])->name(
            "contact.index",
        );

        Route::post("/contact", [ContactController::class, "store"])->name(
            "contact.store",
        );

        Route::get("/products/{product:slug}", [
            ProductController::class,
            "show",
        ])->name("products.show");

        Route::post("/products/{product:slug}/questions", [
            ProductQuestionController::class,
            "store",
        ])->name("products.questions.store");

        Route::get("/collections/{collection}", function ($collection) {
            throw new Exception("Collection page not implemented yet");
        })->name("collections.show");

        Route::get("/return-policy", [
            ReturnPolicyController::class,
            "index",
        ])->name("return-policy.index");

        Route::get("/terms-and-conditions", [
            TermsController::class,
            "index",
        ])->name("terms.index");

        Route::post("/favorites", [FavoriteController::class, "store"])->name(
            "favorites.store",
        );

        Route::post("/newsletter-subscribers", [
            NewsletterSubscriberController::class,
            "store",
        ])->name("newsletter-subscribers.store");

        Route::post("/cart/items/{product:slug}", [
            CartItemController::class,
            "store",
        ])->name("cart.items.add");
        Route::post("/cart/items/{cartItem}/increment", [
            CartItemController::class,
            "increment",
        ])->name("cart.items.increment");
        Route::post("/cart/items/{cartItem}/decrement", [
            CartItemController::class,
            "decrement",
        ])->name("cart.items.decrement");
        Route::delete("/cart/items/{cartItem}", [
            CartItemController::class,
            "destroy",
        ])->name("cart.items.remove");

        Route::get("/checkout", [CheckoutController::class, "index"])->name(
            "checkout.index",
        );
        Route::post("/checkout/tabby-eligibility", [
            CheckoutController::class,
            "checkTabbyEligibility",
        ])->name("checkout.tabby-eligibility");

        Route::prefix("auth")
            ->name("auth.")
            ->group(function () {
                Route::post("/logout", [AuthController::class, "logout"])->name(
                    "logout",
                );
                Route::post("/otp", [AuthController::class, "sendOtp"])->name(
                    "otp",
                );
                Route::get("/google", [
                    AuthController::class,
                    "redirectToGoogle",
                ])->name("google");
                Route::get("/google/callback", [
                    AuthController::class,
                    "handleGoogleCallback",
                ])->name("google.callback");
            });

        Route::get("/promocode", function () {
            throw new Exception("Promocode functionality not implemented yet");
        })->name("cart.apply-promo");

        Route::post("/checkout", [CheckoutController::class, "store"])->name(
            "checkout.store",
        );
    });

// Route::post('/payments/methods', [PaymentMethodController::class, 'index'])->name('payments.methods.index');
Route::post("/payments", [PaymentController::class, "store"])
    ->middleware("auth")
    ->name("payments.store");
Route::get("/payments/callback", [PaymentController::class, "callback"])->name(
    "payments.callback",
);
