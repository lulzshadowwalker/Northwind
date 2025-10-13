<x-layout title="{{ __('app.all-perfumes-title') }}">
    <div class="container mx-auto px-4 my-12">
        <header class="mb-8">
            <h1 class="text-3xl lg:text-4xl font-light tracking-wide mb-2">{{ __('app.all-products') }}</h1>
            <p class="text-lg text-base-content/70">{{ __('app.discover-collection') }}</p>
        </header>

        <div class="mb-8 flex flex-col gap-4 items-start md:gap-0 md:flex-row md:justify-between md:items-center">
            <form action="{{ route('products.index', ['language' => app()->getLocale()]) }}" method="GET"
                class="flex items-center gap-2">
                <div class="join">
                    <input type="search" role="search" name="search" placeholder="{{ __('app.search-by-name') }}"
                        class="join-item input input-bordered input-primary w-full max-w-xs" value="{{ request('search') }}">

                        <button type="submit" class="join-item btn btn-primary">{{ __('app.search') }}</button>
                </div>
            </form>

            <form action="{{ route('products.index', ['language' => app()->getLocale()]) }}" method="GET">
                <div class="form-control">
                    <select name="sort" class="select select-bordered" onchange="this.form.submit()">
                        <option value="" disabled {{ !request('sort') ? 'selected' : '' }}>{{ __('app.sort-by') }}
                        </option>
                        <option value="name_asc" {{ request('sort') == 'name_asc' ? 'selected' : '' }}>
                            {{ __('app.name-asc') }}
                        </option>
                        <option value="name_desc" {{ request('sort') == 'name_desc' ? 'selected' : '' }}>
                            {{ __('app.name-desc') }}
                        </option>
                    </select>
                </div>
                <input type="hidden" name="search" value="{{ request('search') }}">
            </form>
        </div>

        @if ($products->isNotEmpty())
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-12">
                @foreach ($products as $product)
                    <x-product-card :product="$product" :cart="$cart" />
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-base-content/60">{{ __('app.no-products-found') }}</p>
            </div>
        @endif
    </div>
</x-layout>
