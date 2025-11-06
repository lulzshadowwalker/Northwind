<x-layout title="{{ $product->name }} - Mystic Bloom">
    <div class="bg-base-100 text-base-content" x-data="productGallery()">
        <!-- Sale Countdown Banner -->
        <div class="bg-gradient-to-r from-primary to-secondary text-white py-4">
            <div class="container mx-auto px-4 text-center">
                <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-fire text-orange-300"></i>
                        <span class="font-medium">{{ __('app.limited-time-offer') }}</span>
                    </div>
                    <div class="flex gap-2 items-center">
                        <span class="text-sm opacity-90">{{ __('app.ends-in') }}</span>
                        <div class="flex gap-1">
                            <div class="countdown font-mono text-sm bg-white/20 rounded px-2 py-1">
                                <span style="--value:2;" aria-live="polite" aria-label="2">2</span>
                            </div>
                            <span class="text-xs self-center">d</span>
                            <div class="countdown font-mono text-sm bg-white/20 rounded px-2 py-1">
                                <span style="--value:15;" aria-live="polite" aria-label="15">15</span>
                            </div>
                            <span class="text-xs self-center">h</span>
                            <div class="countdown font-mono text-sm bg-white/20 rounded px-2 py-1">
                                <span style="--value:42;" aria-live="polite" aria-label="42">42</span>
                            </div>
                            <span class="text-xs self-center">m</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 py-8 lg:py-16">

                <!-- Image Gallery -->
                <div class="lg:col-span-7">
                    <template x-if="images.length > 0">
                        <div class="grid grid-cols-5 gap-4">
                            <!-- Thumbnail Column -->
                            <div class="col-span-1 space-y-3">
                                <template x-for="(image, index) in images" :key="index">
                                    <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden cursor-pointer border-2 transition-all duration-300"
                                        :class="activeImageIndex === index ? 'border-primary shadow-lg' :
                                            'border-gray-200 hover:border-gray-300'"
                                        @click="activeImageIndex = index" x-lightbox="image.url"
                                        x-lightbox:group="product-gallery">
                                        <img :src="image.thumb_url" :alt="`${productName} thumbnail ${index + 1}`"
                                            class="w-full h-full object-contain transition-transform duration-300 hover:scale-105 p-4">
                                    </div>
                                </template>
                            </div>

                            <!-- Main Image -->
                            <div class="col-span-4">
                                <div class="aspect-[4/5] bg-gray-50 rounded-xl overflow-hidden shadow-xl cursor-pointer group relative"
                                    @click="openLightbox(activeImageIndex)">
                                    <img :src="images[activeImageIndex].url" :alt="`${productName} main image`"
                                        class="w-full h-full object-contain transition-transform duration-700 group-hover:scale-105 p-12">
                                    <div
                                        class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-all duration-300 flex items-center justify-center">
                                        <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                            <i class="fas fa-expand text-white text-2xl"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="images.length === 0">
                        <div
                            class="aspect-[4/5] bg-gray-50 rounded-xl overflow-hidden shadow-xl flex items-center justify-center">
                            <div class="text-center text-gray-500">
                                <i class="fas fa-image text-5xl"></i>
                                <p class="mt-2">{{ __('app.no-images-available') }}</p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Product Details -->
                <div class="lg:col-span-5 flex flex-col justify-center">
                    <div class="space-y-6">
                        <!-- Header -->
                        <div>
                            <p class="text-sm font-light tracking-widest uppercase text-primary/80 mb-2">
                                {{ $product->category->name }}
                            </p>
                            <h1
                                class="text-4xl lg:text-5xl font-serif font-light text-gray-800 leading-tight _font-zain">
                                {{ $product->name }}
                            </h1>
                        </div>

                        <!-- Rating & Reviews -->
                        <!-- <div class="flex items-center gap-4">
                            <div class="rating rating-sm">
                                @for ($i = 1; $i <= 5; $i++)
<input type="radio" class="mask mask-star-2 bg-orange-400"
{{ $i <= 4 ? 'checked' : '' }} disabled />
@endfor
                        </div>
                        <span class="text-sm text-gray-600">({{ $product->reviews->count() }} reviews)</span>
                        </div> -->

                        <!-- Description -->
                        <div class="prose prose-lg max-w-none text-gray-600 font-light leading-relaxed">
                            <p>{{ $product->description }}</p>
                        </div>

                        <!-- Fragrance Notes -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="font-medium text-gray-800 mb-3">{{ __('app.fragrance-notes') }}</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                                <div>
                                    <span class="font-medium text-primary">{{ __('app.top-notes') }}</span>
                                    <p class="text-gray-600">Damask rose, White musk</p>
                                </div>
                                <div>
                                    <span class="font-medium text-primary">{{ __('app.heart-notes') }}</span>
                                    <p class="text-gray-600">Violet</p>
                                </div>
                                <div>
                                    <span class="font-medium text-primary">{{ __('app.base-notes') }}</span>
                                    <p class="text-gray-600">Cashmere woods</p>
                                </div>
                            </div>
                        </div>

                        <!-- Add to Cart -->
                        <div class="flex items-center gap-3">
                            <form x-target="js-cart-fab js-cart-slideover"
                                action="{{ route('cart.items.add', ['product' => $product->slug, 'language' => app()->getLocale()]) }}"
                                method="POST" class="flex justify-stretch w-full">
                                @csrf
                                <button
                                    class="btn btn-primary btn-lg flex-grow shadow-lg hover:shadow-xl transition-all duration-300">
                                    <i class="fas fa-shopping-bag mr-2"></i>
                                    {{ __('app.add-to-bag') }}
                                </button>
                            </form>
                            <form id="js-favorite" x-target
                                action="{{ route('favorites.store', ['language' => app()->getLocale()]) }}"
                                method="post"
                                data-tip="{{ $product->isFavorite ? __('app.remove-from-favorites') : __('app.add-to-favorites') }}">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $product->id }}">
                                <button class="btn btn-outline btn-lg btn-square"
                                    aria-label="{{ __('app.add-to-favorites') }}"
                                    title="{{ __('app.add-to-favorites') }}">
                                    <i @class(['fas fa-heart', 'text-red-400' => $product->isFavorite])></i>
                                </button>
                            </form>
                        </div>

                        <!-- Tabby Promo Snippet -->
                        <div id="TabbyPromo" class="mb-4"></div>

                        <div class="flex items-center gap-4">
                            <img src="{{ asset('assets/images/tabby.png') }}" alt="Tabby Logo" class="h-5" />
                            <img src="{{ asset('assets/images/tamara.png') }}" alt="Tamara Logo"
                                class="h-5 scale-210" />
                        </div>

                        <!-- Additional Info -->
                        <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-truck text-primary"></i>
                                <span>{{ __('app.free-shipping-over', ['amount' => '$75']) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-undo text-primary"></i>
                                <span>{{ __('app.returns-days', ['days' => 30]) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-certificate text-primary"></i>
                                <span>{{ __('app.authentic') }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-gift text-primary"></i>
                                <span>{{ __('app.gift-wrapping-available') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Inquiries Section -->
        <div class="bg-gray-50/50 border-t border-gray-200">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <div class="text-center mb-8">
                    <h2 class="text-3xl lg:text-4xl font-serif text-gray-800 mb-2">{{ __('app.product-inquiries') }}
                    </h2>
                    <p class="text-gray-600 font-light">{{ __('app.product-inquiries-lead') }}</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    @if ($product->productQuestions->count() > 0)
                        <div id="js-product-questions" class="card bg-base-100 shadow-sm border border-gray-200">
                            <div class="card-body">
                                <h3 class="text-xl font-medium mb-4 text-gray-800">{{ __('app.faqs') }}</h3>

                                <div class="space-y-2">
                                    @foreach ($product->productQuestions as $question)
                                        <div class="collapse collapse-plus bg-gray-50 border border-gray-200">
                                            <input type="radio" name="faq-accordion" @checked($loop->first) />
                                            <div class="collapse-title font-medium text-gray-800">
                                                {{ $question->question }}
                                            </div>
                                            <div class="collapse-content">
                                                <div class="flex justify-between items-start gap-4">
                                                    <p
                                                        class="text-sm text-gray-600 {{ !$question->answer ? 'italic text-gray-500' : '' }}">
                                                        {{ $question->answer ?? __('app.waiting-for-answer') }}
                                                    </p>
                                                    @if (!$question->answer)
                                                        <button
                                                            class="btn btn-sm btn-circle btn-ghost tooltip tooltip-left"
                                                            data-tip="{{ __('app.notify-when-answered') }}">
                                                            <i class="fas fa-bell text-primary"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="card bg-base-100 shadow-sm border border-gray-200">
                        <div class="card-body">
                            <h3 class="text-xl font-medium mb-4 text-gray-800">{{ __('app.ask-a-question') }}</h3>
                            <form id="js-product-question-form"
                                x-target="js-product-questions js-product-question-form"
                                action="{{ route('products.questions.store', ['product' => $product->slug, 'language' => app()->getLocale()]) }}"
                                method="POST" class="space-y-4">
                                @csrf
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-medium">{{ __('app.email') }}</span>
                                    </label>
                                    <input name="email" type="email" placeholder="your@email.com"
                                        class="input input-bordered w-full validator" required
                                        value="{{ old('email', auth()->user()->email ?? '') }}" />
                                </div>
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text font-medium">{{ __('app.question') }}</span>
                                    </label>
                                    <textarea name="question" class="textarea textarea-bordered h-24 w-full validator"
                                        placeholder="{{ __('app.what-would-you-like-to-know') }}" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-full">
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    {{ __('app.submit-question') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <section class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl lg:text-4xl font-serif text-gray-800">{{ __('app.customer-reviews') }}</h2>
                <button class="btn btn-primary btn-outline" onclick="review_modal.showModal()">
                    <i class="fas fa-star mr-2"></i>
                    {{ __('app.write-a-review') }}
                </button>
            </div>

            <div class="card bg-base-100 shadow-sm border border-gray-200">
                <div class="card-body">
                    <div class="space-y-6">
                        @forelse ($product->reviews->take(2) as $review)
                            <div class="flex gap-4 pb-6 border-b border-gray-200 last:border-b-0 last:pb-0">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-12 h-12 rounded-full bg-primary text-white flex items-center justify-center font-medium">
                                        {{ strtoupper(substr($review->customer->user->name, 0, 1)) }}
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div class="flex flex-col items-start gap-2 md:flex-row md:items-center justify-between mb-2">
                                        <div class="flex flex-col items-start md:flex-row md:items-center gap-2">
                                            <h4 class="font-medium text-gray-800">{{ $review->customer->user->name }}</h4>

                                            <span class="text-sm text-gray-500">{{ $review->created_at->diffForHumans() }}</span>
                                        </div>

                                        <div class="rating rating-sm">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <input type="radio" class="mask mask-star-2 bg-orange-400"
                                                    {{ $i <= $review->rating ? 'checked' : '' }} disabled />
                                            @endfor
                                        </div>
                                    </div>

                                    <p class="text-gray-700 leading-relaxed">{{ $review->content }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <i class="fas fa-star text-gray-300 text-4xl mb-4"></i>
                                <p class="text-gray-500">{{ __('app.no-reviews-yet') }}</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <!-- Review Modal -->
        <dialog id="review_modal" class="modal">
            <div class="modal-box">
                <form method="dialog">
                    <button class="btn btn-sm btn-circle btn-ghost absolute right-2 top-2">✕</button>
                </form>
                <h3 class="font-bold text-lg mb-4">{{ __('app.write-a-review') }}</h3>
                <form x-data="{ rating: 0 }" class="space-y-4">
                    @csrf
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium">{{ __('app.rating') }}</span>
                        </label>
                        <div class="rating rating-lg">
                            @for ($i = 1; $i <= 5; $i++)
                                <input type="radio" name="rating" class="mask mask-star-2 bg-orange-400"
                                    value="{{ $i }}" @click="rating = {{ $i }}"
                                    aria-label="{{ $i }} stars" />
                            @endfor
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text font-medium">{{ __('app.your-review') }}</span>
                        </label>
                        <textarea name="review" class="textarea textarea-bordered h-24" placeholder="{{ __('app.share-your-experience') }}"
                            required></textarea>
                    </div>
                    <div class="modal-action">
                        <button type="button" class="btn btn-ghost"
                            onclick="review_modal.close()">{{ __('app.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-star mr-2"></i>
                            {{ __('app.submit-review') }}
                        </button>
                    </div>
                </form>
            </div>
        </dialog>

        <!-- You May Also Like Section -->
        <div class="bg-white border-t border-gray-200">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
                <div class="text-center mb-8">
                    <h2 class="text-3xl lg:text-4xl font-serif text-gray-800 mb-2">
                        {{ __('app.discover-other-essences') }}</h2>
                    <p class="text-gray-600 font-light">{{ __('app.curated-selections') }}</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($relatedProducts as $relatedProduct)
                        <x-product-card :product="$relatedProduct" :cart="$cart" />
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Tabby Promo Script -->
    <script src="https://checkout.tabby.ai/tabby-promo.js"></script>
    <script>
        // Initialize Tabby Promo
        new TabbyPromo({
            selector: '#TabbyPromo',
            currency: 'SAR', // Change to AED for UAE, KWD for Kuwait
            price: '{{ number_format($product->price->getAmount()->toFloat(), 2, '.', '') }}',
            lang: '{{ app()->getLocale() === "ar" ? "ar" : "en" }}',
            source: 'product',
            publicKey: '{{ config("services.tabby.public_key") }}',
            merchantCode: '{{ config("services.tabby.merchant_code", "NWSA") }}'
        });

        function productGallery() {
            const productName = '{{ $product->name }}';
            const images = @json(
                $product->images->map(function ($media) {
                    return [
                        'url' => $media->getUrl('catalog'),
                        'thumb_url' => $media->getUrl('thumb'),
                    ];
                }));

            const defaultImages = [];

            return {
                activeImageIndex: 0,
                productName,
                images: images.length > 0 ? images : defaultImages,
                openLightbox(index) {
                    const gallery = this.images.map(i => ({
                        url: i.url
                    }));
                    const lightbox = new PhotoSwipeLightbox({
                        dataSource: gallery,
                        pswpModule: PhotoSwipe,
                        mainClass: 'pswp--custom-bg',
                        pswp__bg: {
                            opacity: 0.8
                        },
                        index: index
                    });
                    lightbox.init();
                    lightbox.loadAndOpen(index);
                }
            }
        }
    </script>
</x-layout>
