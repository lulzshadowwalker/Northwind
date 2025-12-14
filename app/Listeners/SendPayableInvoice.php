<?php

namespace App\Listeners;

use App\Events\PaymentPaid;
use App\Notifications\InvoicePaid;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Spatie\Browsershot\Browsershot;

class SendPayableInvoice implements ShouldQueue
{
    public function __construct()
    {
        //
    }

    public function handle(PaymentPaid $event): void
    {
        DB::transaction(function () use ($event) {
            $payment = $event->payment;

            $invoice = $payment->invoice()->create();

            $html = view('invoices.show', compact('invoice'))->render();

            if (! app()->environment('testing')) {
                Browsershot::html($html)
                    ->showBackground()
                    ->save($invoice->filepath());
            }

            // Clear the customer's cart after successful payment
            $customer = $payment->payable->customer;
            if ($customer && $customer->cart) {
                $customer->cart->cartItems()->delete();
            }

            $payment->payable->payer()->notify(new InvoicePaid($invoice));
        });
    }
}
