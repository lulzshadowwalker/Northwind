<x-layout>
    <div class="min-h-screen bg-base-100 py-8">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex items-center space-x-3">
                    <img src="{{ asset('assets/images/logo.png') }}" alt="Aura Logo" class="w-10 h-10 rounded-full">
                    <h1 class="text-2xl font-bold text-gray-900">{{ __('app.complete-your-payment') }}</h1>
                </div>
            </div>

            <!-- Payment Summary Card -->
            <div class="max-w-2xl mx-auto mb-6">
                <div class="bg-base-200 rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold mb-4">{{ __('app.order-summary') }}</h2>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">{{ __('app.total-amount') }}</span>
                        <span class="text-2xl font-bold text-gray-900">
                            {{ $payment->currency }} {{ number_format($payment->money->getAmount()->toFloat(), 2) }}
                        </span>
                    </div>
                    <div class="mt-2 text-sm text-gray-500">
                        {{ __('app.payment-id') }}: #{{ $payment->id }}
                    </div>
                </div>
            </div>

            <!-- Payment Form Container -->
            <div class="max-w-2xl mx-auto">
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold mb-2">{{ __('app.payment-details') }}</h2>
                        <p class="text-sm text-gray-600">
                            {{ __('app.payment-details-description') }}
                        </p>
                    </div>

                    <!-- HyperPay Copy & Pay Widget Script -->
                    <script
                        src="{{ $checkoutDetails['base_url'] }}/v1/paymentWidgets.js?checkoutId={{ $checkoutDetails['checkout_id'] }}"
                        @if($checkoutDetails['integrity'])
                        integrity="{{ $checkoutDetails['integrity'] }}"
                        crossorigin="anonymous"
                        @endif>
                    </script>

                    <!-- 3DS Full Redirection Configuration -->
                    <script type="text/javascript">
                        var wpwlOptions = {
                            paymentTarget: "_top",
                            @if(app()->getLocale() === 'ar')
                            locale: "{{ app()->getLocale() }}",
                            @endif
                            style: "card",
                            brandDetection: true,
                            brandDetectionType: "binlist",
                            maskCvv: true,
                            onReady: function() {
                                console.log('HyperPay payment form loaded');
                                // Ensure MADA is shown first
                                var form = document.querySelector('.wpwl-form');
                                if (form) {
                                    form.setAttribute('role', 'form');
                                    form.setAttribute('aria-label', '{{ __("app.payment-details") }}');
                                }
                            },
                            onError: function(error) {
                                console.error('HyperPay error:', error);
                                showErrorMessage('{{ __("app.payment-error-occurred") }}');
                            }
                        };
                    </script>

                    <!-- Payment Form - MADA shown first as per Saudi Payments requirements -->
                    <div class="payment-form-wrapper">
                        <div class="flex items-center mb-3 space-x-2">
                            <span class="text-sm font-semibold text-gray-700">{{ __('app.payment-card') }}</span>
                            <span class="badge badge-sm badge-info text-xs">{{ __('app.mada-visa-mastercard') }}</span>
                        </div>
                        <form
                            action="{{ $checkoutDetails['shopper_result_url'] }}"
                            class="paymentWidgets"
                            data-brands="MADA VISA MASTER"
                            aria-label="{{ __('app.payment-card') }}">
                        </form>
                    </div>

                    <!-- Loading Indicator -->
                    <div id="payment-loading" class="hidden mt-6">
                        <div class="flex items-center justify-center space-x-3">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                            <span class="text-gray-600">{{ __('app.processing-payment') }}</span>
                        </div>
                    </div>

                    <!-- Error Message Container -->
                    <div id="payment-error" class="hidden mt-6">
                        <div class="bg-error/10 border border-error rounded-lg p-4">
                            <div class="flex items-start space-x-3">
                                <svg class="w-5 h-5 text-error mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-error" id="error-message"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Test Cards Info (Only in test mode) -->
                    @if(config('services.hyperpay.is_test'))
                    <div class="mt-8 p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ __('app.test-cards') }}</h3>
                        <div class="space-y-2 text-xs text-gray-600">
                            <div>
                                <strong>Visa {{ __('app.success') }}:</strong> 4440000009900010 | {{ __('app.expiry-date') }}: 01/39 | CVV: 100
                            </div>
                            <div>
                                <strong>Mastercard {{ __('app.success') }}:</strong> 5123450000000008 | {{ __('app.expiry-date') }}: 01/39 | CVV: 100
                            </div>
                            <div>
                                <strong>MADA {{ __('app.success') }}:</strong> 4464040000000007 | {{ __('app.expiry-date') }}: 12/25 | CVV: 100
                            </div>
                            <div class="mt-2 text-yellow-700">
                                <strong>{{ __('app.important-note') }}:</strong> {{ __('app.test-cards-note') }}
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Security Notice -->
                    <div class="mt-6 flex items-center justify-center space-x-2 text-sm text-gray-500">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ __('app.payment-secure-notice') }}</span>
                    </div>
                </div>

                <!-- Cancel/Back Button -->
                <div class="mt-6 text-center">
                    <a href="{{ route('checkout.index', ['language' => app()->getLocale()]) }}"
                       class="text-gray-600 hover:text-gray-900 underline text-sm">
                        {{ __('app.back-to-checkout') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Styles for HyperPay Widget -->
    <style>
        .payment-form-wrapper {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
            background: #fafafa;
        }

        .wpwl-form {
            margin: 0 !important;
        }

        .wpwl-control {
            border: 1px solid #d1d5db !important;
            border-radius: 0.375rem !important;
            padding: 0.5rem 0.75rem !important;
            font-size: 0.875rem !important;
        }

        .wpwl-control:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
            outline: none !important;
        }

        .wpwl-button {
            background-color: #3b82f6 !important;
            border: none !important;
            border-radius: 0.375rem !important;
            padding: 0.75rem 1.5rem !important;
            font-weight: 600 !important;
            transition: all 0.2s !important;
        }

        .wpwl-button:hover {
            background-color: #2563eb !important;
        }

        .wpwl-label {
            font-size: 0.875rem !important;
            font-weight: 500 !important;
            color: #374151 !important;
            margin-bottom: 0.375rem !important;
        }

        /* RTL Support */
        [dir="rtl"] .wpwl-form {
            text-align: right;
        }
    </style>

    <!-- JavaScript for Error Handling -->
    <script>
        function showErrorMessage(message) {
            const errorContainer = document.getElementById('payment-error');
            const errorMessage = document.getElementById('error-message');
            errorMessage.textContent = message;
            errorContainer.classList.remove('hidden');

            // Auto-hide after 5 seconds
            setTimeout(() => {
                errorContainer.classList.add('hidden');
            }, 5000);
        }

        function showLoading() {
            document.getElementById('payment-loading').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('payment-loading').classList.add('hidden');
        }

        // Monitor form submission
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.paymentWidgets');
            forms.forEach(form => {
                form.addEventListener('submit', function() {
                    showLoading();
                });
            });
        });
    </script>
</x-layout>
