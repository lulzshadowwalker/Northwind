@props(['product', 'collection', 'cart'])
<a href="{{ route('products.show', ['product' => $product->slug, 'language' => app()->getLocale()]) }}" class="block">
    <div class="card aspect-square">
        <div class="card-body flex items-center justify-center group relative p-4">
            <img src="{{ $product->getFirstMediaUrl('product.cover', 'thumb') }}"
                srcset="{{ $product->getFirstMedia('product.cover')?->getSrcset('thumb') }}"
                sizes="(min-width: 440px) 264px, calc(50vw - 80px)" alt="{{ $product->name }} Perfume Bottle"
                class="max-h-60 w-auto object-contain transition-transform duration-700 ease-in-out group-hover:-rotate-y-15"
                style="transform-style: preserve-3d;" />

            @php
                $id =
                    'js-product-card-cart-actions-' .
                    (isset($collection) ? 'c' . $collection->id . '-p' . $product->id : 'p-' . $product->id);
            @endphp
            <div id="{{ $id }}" class="absolute top-2 end-2 flex flex-col items-center gap-1 z-10">
                <form x-target="{{ $id }}" action="{{ route('favorites.store', ['language' => app()->getLocale()]) }}"
                    method="post"
                    class="tooltip tooltip-bottom @if (app()->getLocale() === 'ar') tooltip-right @else tooltip-left @endif"
                    data-tip="{{ $product->isFavorite ? 'Remove from Favorites' : 'Add to Favorites' }}">
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    @csrf
                    <button class="btn btn-sm btn-circle group" aria-label="Add to Favorites">
                        <i @class([
                            'fa fa-heart text-[#1a1a1a]/90',
                            'text-red-400' => $product->isFavorite,
                        ])></i>
                    </button>
                </form>

                <div class="divider !my-0 w-6"></div>

                @php $cartItem = $cart->cartItem($product); @endphp

                <form x-target="js-cart-fab js-cart-slideover {{ $id }}"
                    action="{{ $cartItem ? route('cart.items.increment', ['cartItem' => $cartItem->id, 'language' => app()->getLocale()]) : route('cart.items.add', ['product' => $product->slug, 'language' => app()->getLocale()]) }}"
                    method="post"
                    class="tooltip @if (app()->getLocale() === 'ar') tooltip-right @else tooltip-left @endif"
                    data-tip="Add to Cart">
                    @csrf
                    <button class="btn btn-sm btn-circle" aria-label="Add to Cart">
                        <i class="fa fa-plus"></i>
                    </button>
                </form>

                @if ($cartItem)
                    <div class="btn btn-sm btn-secondary btn-circle btn-disabled text-black">
                        {{ $cartItem->quantity }}
                    </div>

                    <form x-target="js-cart-fab js-cart-slideover {{ $id }}"
                        action="{{ !$cartItem->last ? route('cart.items.decrement', ['cartItem' => $cartItem->id, 'language' => app()->getLocale()]) : route('cart.items.remove', ['cartItem' => $cartItem->id, 'language' => app()->getLocale()]) }}"
                        method="post"
                        class="tooltip tooltip-bottom @if (app()->getLocale() === 'ar') tooltip-right @else tooltip-left @endif"
                        data-tip="{{ $cartItem->last ? 'Delete from Cart' : 'Decrease from Cart' }}">
                        @csrf
                        @method($cartItem->last ? 'delete' : 'post')
                        <button @class(['btn btn-sm btn-circle', 'btn-error' => true])
                            aria-label="{{ $cartItem->last ? 'Delete from Cart' : 'Decrease from Cart' }}">
                            <i @class(['fa fa-minus', 'fa fa-trash' => $cartItem->last])></i>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-4 space-y-2">
        <!-- Title - Full width for better readability -->
        <h4 data-textify class="_font-zain rtl:text-xl font-medium leading-tight line-clamp-2">
            {{ $product->name }}
        </h4>

        <!-- Description -->
        <p data-textify
            class="font-light text-neutral-500 dark:text-neutral-400 tracking-wide line-clamp-3 text-sm max-md:text-xs leading-relaxed">
            {{ $product->description }}
        </p>

        <!-- Price - Prominent display -->
        <div class="flex items-center justify-end pt-1 gap-2">
            @if($product->sale_price)
                <span class="text-sm text-neutral-400 line-through flex items-center">
                    {{ $product->price->getAmount() }}
                </span>
                <span class="text-lg font-semibold text-base-content flex items-center">
                    {{ $product->sale_price->getAmount() }} <x-sar />
                </span>
            @else
                <span class="text-lg font-semibold text-base-content flex items-center">
                    {{ $product->price->getAmount() }} <x-sar />
                </span>
            @endif
        </div>
    </div>
</a>