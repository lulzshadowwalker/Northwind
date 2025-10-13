@props(['collections'])

<div class="bg-base-200">
    <footer class="footer sm:footer-horizontal text-base-content p-10 container mx-auto">
        <nav>
            <h6 class="footer-title">{{ __('app.collections') }}</h6>
            @foreach ($collections as $collection)
                <a href="/#{{ $collection->slug }}" class="link link-hover">{{ $collection->name }}</a>
            @endforeach
        </nav>

        <nav>
            <h6 class="footer-title">{{ __('app.legal') }}</h6>
            <a href="{{ route('terms.index', ['language' => app()->getLocale()]) }}"
                class="link link-hover">{{ __('app.terms-and-conditions') }}</a>
            <a href="{{ route('return-policy.index', ['language' => app()->getLocale()]) }}"
                class="link link-hover">{{ __('app.return-policy') }}
            </a>
        </nav>

        <nav>
            <h6 class="footer-title">{{ __('app.support') }}</h6>
            <a class="link link-hover"
                href="{{ route('contact.index', ['language' => app()->getLocale()]) }}">{{ __('app.contact') }}</a>
        </nav>

        <form id="js-newsletter-form" x-target
            action="{{ route('newsletter-subscribers.store', ['language' => app()->getLocale()]) }}" method="post"
            class="md:ms-auto" x-data="{ default: '{{ auth()->user()?->email }}', email: '' }">
            @csrf
            <h6 class="footer-title">{{ __('app.newsletter') }}</h6>
            <fieldset class="w-80">
                <div class="join">
                    <input name="email" type="email" placeholder="{{ __('app.email-placeholder') }}"
                        class="input input-bordered join-item validator" x-model="email" required />
                    <button class="btn btn-primary join-item">{{ __('app.subscribe') }}
                        <x-spinner />
                    </button>
                </div>
            </fieldset>
        </form>
    </footer>
</div>

<div class="bg-base-200 border-base-300 border-t">
    <footer class="footer text-base-content container mx-auto px-10 py-4">
        <aside class="grid-flow-col items-center gap-4">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Northwind Logo" class="h-14 min-w-14">
            <div>
                {{-- no wrap --}}
                <p class="whitespace-nowrap">
                    © {{ date('Y') }} {{ __('app.northwind') }}. {{ __('app.all-rights-reserved') }}
                </p>
                <p>
                    <a href="https://bayanata.com" target="_blank" rel="noopener noreferrer" class="link link-hover">
                        {{ __('app.powered-by') }}
                    </a>
                </p>
            </div>
        </aside>
        <nav class="md:place-self-center md:justify-self-start">
            <div class="grid grid-flow-col gap-4">
                <a>
                    <i class="fa-brands fa-youtube text-xl text-primary"></i>
                </a>
                <a>
                    <i class="fa-brands fa-facebook-f text-xl text-primary"></i>
                </a>
                <a>
                    <i class="fa-brands fa-x-twitter text-xl text-primary"></i>
                </a>
            </div>
        </nav>
    </footer>
</div>
