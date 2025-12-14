<div id="js-cart-fab" x-data="{ cartOpen: false }" @keydown.escape.window="cartOpen = false">
    <!-- Cart FAB - Vertical Side-mounted -->
    <div class="fixed top-1/2 right-0 -translate-y-1/2 z-40">
        <button @click="cartOpen = true"
            class="btn btn-primary rounded-r-none rounded-l-xl shadow-lg px-3 py-6 h-auto min-h-0 flex flex-col items-center justify-center gap-2 group hover:bg-primary-focus transition-all duration-300 relative"
            aria-label="Open cart">
            {{-- <!-- Cart count indicator -->
            @if (($cart?->cartItems->count() ?? 0) > 0)
            <div
                class="badge badge-secondary absolute -top-2 -left-4 text-2xs font-bold rotate-270 w-6 h-6 flex items-center justify-center">
                {{ $cart?->cartItems->count() ?? 0 }}
            </div>
            @endif --}}

            <!-- Shopping bag icon -->
            <i class="fa fa-bag-shopping text-lg mb-1 rotate-270"></i>

            <!-- Vertical text -->
            <div class="flex flex-col items-center gap-2">
                <span
                    class="text-base font-medium leading-none writing-mode-vertical-rl text-orientation-mixed rotate-180 min-w-fit"
                    style="writing-mode: vertical-rl; text-orientation: mixed;">
                    {{ __('app.your-bag') }}
                </span>
            </div>

            <!-- Hover indicator -->
            <i class="fa fa-chevron-left text-xs group-hover:-translate-x-1 transition-transform duration-300 mt-1"></i>
        </button>
    </div>

    <!-- Overlay -->
    <div x-show="cartOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-black/50 z-50"
        @click="cartOpen = false" style="display: none;">
    </div>

    <!-- Slide-over Cart -->
    <div id="js-cart-slideover" x-show="cartOpen" x-trap.inert.noscroll="cartOpen"
        x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="translate-x-full"
        x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform"
        x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 w-full max-w-md bg-base-100 shadow-xl z-50 flex flex-col" style="display: none;"
        aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
        <!-- Header -->
        <header class="flex items-center justify-between p-6 border-b border-base-300">
            <h2 id="slide-over-title" class="text-2xl font-light tracking-wide text-base-content">
                {{ __('app.your-bag') }}
            </h2>
            <button @click="cartOpen = false" class="btn btn-ghost btn-circle" aria-label="Close cart">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </header>

        <!-- Cart Content -->
        @if (($cart?->cartItems->count() ?? 0) === 0)
            <!-- Empty State -->
            <div class="flex-1 flex flex-col items-center justify-center p-8 text-center space-y-6">
                <!-- Empty Bag Icon -->
                <div class="relative">
                    <div class="w-24 h-24 bg-base-200 rounded-full flex items-center justify-center">
                        <i class="fa fa-bag-shopping text-4xl text-base-content/30"></i>
                    </div>
                    <!-- Decorative elements -->
                    <div class="absolute -top-2 -right-2 w-6 h-6 bg-primary/20 rounded-full animate-pulse"></div>
                    <div class="absolute -bottom-1 -left-2 w-4 h-4 bg-secondary/20 rounded-full animate-pulse delay-75">
                    </div>
                </div>

                <!-- Empty State Content -->
                <div class="space-y-3">
                    <h3 class="text-xl font-medium text-base-content">{{ __('app.empty-bag-title') }}</h3>
                    <p class="text-sm text-base-content/70 max-w-xs leading-relaxed">
                        {{ __('app.empty-bag-description') }}
                    </p>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-3 w-full">
                    <a href="{{ route('products.index', ['language' => app()->getLocale()]) }}" @click="cartOpen = false"
                        class="btn btn-primary w-full">
                        <i class="fa fa-sparkles mr-2"></i>
                        {{ __('app.start-shopping') }}
                    </a>

                    <a href="{{ route('home.index', ['language' => app()->getLocale()]) . '#collections' }}"
                        @click="cartOpen = false" class="btn btn-outline btn-sm flex-1 text-xs w-full">
                        {{ __('app.explore-collections') }}
                    </a>
                </div>

                <!-- Promotional Text -->
                <div class="mt-8 p-4 bg-gradient-to-r from-primary/5 to-secondary/5 rounded-lg w-full">
                    <div class="flex items-center justify-center space-x-2 text-sm text-base-content/60">
                        <i class="fa fa-gift"></i>
                        <span>{{ __('app.free-shipping-over', ['amount' => '200 SAR']) }}</span>
                    </div>
                </div>
            </div>
        @else
            <!-- Cart Items -->
            <div id="js-cart-items" class="flex-1 overflow-y-auto p-6 space-y-6">
                @foreach ($cart->cartItems as $item)
                    <div class="flex items-start space-x-4">
                        <img src="{{ $item->product->cover }}" alt="Perfume Bottle"
                            class="w-24 h-24 object-contain p-2 rounded-lg border border-base-300">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-base-content text-pretty">{{ $item->product->name }}
                            </h3>
                            <p class="text-sm font-light text-neutral-500 line-clamp-2">
                                {{ $item->product->description }}
                            </p>
                            <p class="text-lg font-bold text-base-content mt-2 flex items-center">
                                {{ $item->product->price->getAmount() }}
                                <x-sar />
                            </p>
                        </div>
                        <div class="flex flex-col items-end justify-between h-24">
                            <form x-target="js-cart-fab"
                                action="{{ route('cart.items.remove', ['cartItem' => $item->id, 'language' => app()->getLocale()]) }}"
                                method="post" class="mb-2">
                                @csrf
                                @method('delete')
                                <button type="submit"
                                    class="btn btn-ghost btn-xs tooltip tooltip-error tooltip-left rtl:tooltip-right"
                                    data-tip="{{ __('app.remove-item') }}" aria-label="{{ __('app.remove-item') }}">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>

                            <div class="flex items-center space-x-3">
                                <form x-target="js-cart-items js-cart-footer"
                                    action="{{ route('cart.items.decrement', ['cartItem' => $item->id, 'language' => app()->getLocale()]) }}"
                                    method="post">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-xs tooltip tooltip-left rtl:tooltip-right"
                                        data-tip="{{ __('app.decrease-quantity') }}"
                                        aria-label="{{ __('app.decrease-quantity') }}">
                                        <i class="fa fa-minus"></i>
                                    </button>
                                </form>

                                <span class="text-md font-semibold">{{ $item->quantity }}</span>

                                <form x-target="js-cart-items js-cart-footer"
                                    action="{{ route('cart.items.increment', ['cartItem' => $item->id, 'language' => app()->getLocale()]) }}"
                                    method="post">
                                    @csrf
                                    <button type="submit" class="btn btn-ghost btn-xs tooltip tooltip-left rtl:tooltip-right"
                                        data-tip="{{ __('app.increase-quantity') }}"
                                        aria-label="{{ __('app.increase-quantity') }}">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Footer -->
            <footer id="js-cart-footer" class="p-6 border-t border-base-300">
                <div class="flex justify-between items-center mb-4">
                    <span class="text-lg font-light text-base-content">{{ __('app.subtotal') }}</span>
                    <span
                        class="text-xl font-bold text-base-content inline-flex items-center">{{ $cartTotal->subtotal->getAmount() }}
                        <x-sar /></span>
                </div>

                <!-- Tabby Promo Widget -->
                <div id="TabbyPromoCart" class="mb-4"></div>

                <a href="{{ route('checkout.index', ['language' => app()->getLocale()]) }}" class="btn btn-primary w-full">
                    {{ __('app.proceed-to-checkout') }}
                </a>
                <div class="text-center mt-4">
                    <button @click="cartOpen = false"
                        class="link link-hover text-sm text-base-content/70">{{ __('app.or-continue-shopping') }}
                    </button>
                </div>
            </footer>
        @endif
    </div>
</div>

@if (($cart?->cartItems->count() ?? 0) > 0)
    @push('scripts')
        <!-- Tabby Promo Script for Cart -->
        <script src="https://checkout.tabby.ai/tabby-promo.js"></script>
        <script>
            function initTabby() {
                new TabbyPromo({
                    selector: '#TabbyPromoCart',
                    currency: 'SAR',
                    price: '{{ number_format($cartTotal->total->getAmount()->toFloat(), 2, '.', '') }}',
                    lang: '{{ app()->getLocale() }}',
                    source: 'cart',
                    publicKey: '{{ config("services.tabby.public_key") }}',
                    merchantCode: '{{ config("services.tabby.merchant_code", "NWSA") }}'
                });
            }

            initTabby();

            document.addEventListener('ajax:success', initTabby)
        </script>
    @endpush
@endif