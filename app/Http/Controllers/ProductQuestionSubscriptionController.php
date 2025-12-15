<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProductQuestionSubscriptionController extends Controller
{
    public function store(string $language, Request $request, \App\Models\ProductQuestion $productQuestion)
    {
        $email = auth()->user()?->email;

        if (! $email) {
            return back()
                ->with('warning', __('app.must-be-logged-in-to-subscribe'));
        }

        $productQuestion->subscriptions()->firstOrCreate([
            'email' => $email,
        ], [
            'user_id' => auth()->id(),
        ]);

        return back()->with('success', __('app.you-will-be-notified'));
    }
}
