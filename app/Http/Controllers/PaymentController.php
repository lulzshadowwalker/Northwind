<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGatewayService;
use App\Enums\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Services\HyperPayPaymentGatewayService;
use App\Services\TabbyPaymentGatewayService;
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
            // Detect which gateway to use based on the request
            // HyperPay sends 'resourcePath', Tabby sends 'payment_id'
            $gateway = $this->detectGateway($request);

            $payment = $gateway->callback($request);

            $language = $request->language ?? (app()->getLocale() ?? 'en');

            // Handle different payment statuses with distinct messages
            if ($payment->status === PaymentStatus::failed) {
                return redirect()
                    ->route('home.index', ['language' => $language])
                    ->with('error', __('app.payment-failed'));
            }

            if ($payment->status === PaymentStatus::cancelled) {
                return redirect()
                    ->route('home.index', ['language' => $language])
                    ->with('warning', __('app.payment-cancelled'));
            }

            // Payment successful
            return redirect()
                ->route('home.index', ['language' => $language])
                ->with('success', __('app.thank-you-for-your-order'))
                ->with('payment', $payment);
        } catch (Exception $e) {
            Log::emergency('Payment callback error: '.$e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e,
            ]);

            $language = $request->language ?? (app()->getLocale() ?? 'en');

            return redirect()
                ->route('home.index', ['language' => $language])
                ->with('error', __('app.payment-error'));
        }
    }

    /**
     * Detect which payment gateway service to use based on request parameters
     */
    protected function detectGateway(Request $request): PaymentGatewayService
    {
        // HyperPay sends 'resourcePath' parameter
        if ($request->has('resourcePath')) {
            return app(HyperPayPaymentGatewayService::class);
        }

        // Tabby sends 'payment_id' parameter
        if ($request->has('payment_id')) {
            return app(TabbyPaymentGatewayService::class);
        }

        // Default to HyperPay
        return app(HyperPayPaymentGatewayService::class);
    }

    /**
     * Show the HyperPay payment form (Copy & Pay widget)
     */
    public function showHyperPayForm(
        Request $request,
        string $language,
        Payment $payment,
    ) {
        // Verify the payment belongs to HyperPay gateway
        if ($payment->gateway !== PaymentGateway::hyperpay) {
            abort(404, 'Payment method not supported');
        }

        // Verify the payment is still pending
        if ($payment->status !== PaymentStatus::pending) {
            return redirect()
                ->route('home.index', ['language' => app()->getLocale()])
                ->with('error', __('app.payment-already-processed'));
        }

        // Get the checkout details from HyperPay service
        $hyperPayService = app(HyperPayPaymentGatewayService::class);
        $checkoutDetails = $hyperPayService->getCheckoutDetails($payment);

        // Validate that we have the necessary checkout information
        if (! $checkoutDetails['checkout_id']) {
            Log::error('HyperPay checkout details missing', [
                'payment_id' => $payment->id,
                'details' => $payment->details,
            ]);

            return redirect()
                ->route('checkout.index', ['language' => app()->getLocale()])
                ->with('error', __('app.payment-form-load-error'));
        }

        return view('payments.hyperpay-form', [
            'payment' => $payment,
            'checkoutDetails' => $checkoutDetails,
        ]);
    }
}
