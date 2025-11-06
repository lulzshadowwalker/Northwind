<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGatewayService;
use App\Enums\PaymentStatus;
use App\Http\Requests\StorePaymentRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(protected PaymentGatewayService $gateway)
    {
        //
    }

    public function store(StorePaymentRequest $request)
    {
        // TODO: Implement authorization logic if needed
        // $this->authorize('pay', $request->payable());

        [$payment, $url] = $this->gateway->start(
            $request->payable(),
            $request->paymentMethodId(),
        );

        return redirect()->away($url);
    }

    /**
     * Handle a successful/failed payment callback from the payment gateway.
     */
    public function callback(Request $request)
    {
        try {
            $payment = $this->gateway->callback($request);

            if ($payment->status === PaymentStatus::failed) {
                throw new Exception("failed to process payment");
            }

            $language = $request->language ?? (app()->getLocale() ?? "en");

            return redirect()
                ->route("home.index", ["language" => $language])
                ->with("success", __("app.thank-you-for-your-order"))
                ->with("payment", $payment);
        } catch (Exception $e) {
            Log::emergency("Payment callback error: " . $e->getMessage(), [
                "request" => $request->all(),
                "exception" => $e,
            ]);

            $language = $request->language ?? (app()->getLocale() ?? "en");

            return redirect()
                ->route("home.index", ["language" => $language])
                ->with("error", __("app.payment-error"));
        }
    }
}
