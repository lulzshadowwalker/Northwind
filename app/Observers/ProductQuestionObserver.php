<?php

namespace App\Observers;

use App\Models\ProductQuestion;
use App\Notifications\ProductQuestionAnswered;
use Illuminate\Support\Facades\Notification;

class ProductQuestionObserver
{
    /**
     * Handle the ProductQuestion "created" event.
     */
    public function created(ProductQuestion $productQuestion): void
    {
        //
    }

    /**
     * Handle the ProductQuestion "updated" event.
     */
    public function updated(ProductQuestion $productQuestion): void
    {
        if ($productQuestion->isDirty('answer') && $productQuestion->answer) {
            if ($productQuestion->email) {
                Notification::route('mail', $productQuestion->email)
                    ->notify(new ProductQuestionAnswered($productQuestion));
            }

            foreach ($productQuestion->subscriptions as $subscription) {
                if ($subscription->email !== $productQuestion->email) {
                    Notification::route('mail', $subscription->email)
                        ->notify(new ProductQuestionAnswered($productQuestion));
                }
            }
        }
    }

    /**
     * Handle the ProductQuestion "deleted" event.
     */
    public function deleted(ProductQuestion $productQuestion): void
    {
        //
    }

    /**
     * Handle the ProductQuestion "restored" event.
     */
    public function restored(ProductQuestion $productQuestion): void
    {
        //
    }

    /**
     * Handle the ProductQuestion "force deleted" event.
     */
    public function forceDeleted(ProductQuestion $productQuestion): void
    {
        //
    }
}
