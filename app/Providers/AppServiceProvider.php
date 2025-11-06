<?php

namespace App\Providers;

use App\Contracts\PaymentGatewayService;
use App\Services\TabbyPaymentGatewayService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Google\Provider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            PaymentGatewayService::class,
            TabbyPaymentGatewayService::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite("google", Provider::class);
        });

        Gate::define("viewPulse", function ($user) {
            return true;
        });

        // if (!app()->environment("testing")) {
        //     auth()->login(\App\Models\Customer::first()->user);
        // }
    }
}
