@props(['collections'])
<header class="navbar bg-base-100 container mx-auto px-4 2xl:!max-w-395 flex justify-between">
    <!-- Mobile Menu Toggle -->
    <div class="flex-none md:hidden">
        <div class="flex items-center gap-2">
            <div class="dropdown dropdown-start">
                <div tabindex="0" role="button" class="btn btn-square btn-ghost">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        class="inline-block w-5 h-5 stroke-current">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16">
                        </path>
                    </svg>
                </div>
                <ul tabindex="0"
                    class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-56">
                    <li><a href="{{ route('home.index', ['language' => app()->getLocale()]) }}"
                            class="text-sm py-2">{{ __('app.home') }}</a>
                    </li>

                    <!-- Collections Submenu -->
                    <li>
                        <details>
                            <summary class="text-sm py-2">{{ __('app.collections') }}</summary>
                            <ul class="p-1">
                                @foreach ($collections as $key => $collection)
                                    <li><a href="/#{{ $collection->slug }}"
                                            class="text-xs py-1.5">{{ $collection->name }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    </li>

                    <li><a href="{{ route('products.index', ['language' => app()->getLocale()]) }}"
                            class="text-sm py-2">{{ __('app.products') }}</a>
                    </li>

                    <!-- Language Switcher in Mobile Menu -->
                    @php
                        $segments = request()->segments();
                        if (count($segments) === 0) {
                            $segments = [app()->getLocale()];
                        }
                        $segments[0] = 'en';
                        $enPath = implode('/', $segments);
                        $segments[0] = 'ar';
                        $arPath = implode('/', $segments);
                    @endphp
                    <li>
                        <details>
                            <summary class="text-sm py-2 flex items-center gap-2">
                                @if (app()->getLocale() === 'en')
                                    <span class="fi fi-gb"></span> English
                                @else
                                    <span class="fi fi-sa"></span> العربية
                                @endif
                            </summary>
                            <ul class="p-1">
                                <li><a href="{{ url('/' . $enPath) }}"
                                        class="text-xs py-1.5 flex items-center gap-2"><span class="fi fi-gb"></span>
                                        English</a></li>
                                <li><a href="{{ url('/' . $arPath) }}"
                                        class="text-xs py-1.5 flex items-center gap-2"><span class="fi fi-sa"></span>
                                        العربية</a></li>
                            </ul>
                        </details>
                    </li>

                    <!-- Auth Section in Mobile Menu -->
                    @if (auth()->check())
                        <div class="divider my-1"></div>
                        <li class="menu-title">
                            <div class="flex items-center gap-2 py-1">
                                <div class="avatar">
                                    <div class="w-6 rounded-full">
                                        <img alt="User Avatar" src="{{ auth()->user()->avatar }}" />
                                    </div>
                                </div>
                                <span class="text-xs font-medium">{{ auth()->user()->name ?? 'User' }}</span>
                            </div>
                        </li>
                        <li>
                            <a class="justify-between text-sm py-2">
                                {{ __('app.profile') }}
                                <span class="badge badge-xs">{{ __('app.new') }}</span>
                            </a>
                        </li>
                        <li><a class="text-sm py-2">{{ __('app.settings') }}</a></li>
                        <li>
                            <form method="post"
                                action="{{ route('auth.logout', ['language' => app()->getLocale()]) }}" class="w-full">
                                @csrf
                                <button type="submit"
                                    class="text-sm py-2 w-full text-left">{{ __('app.logout') }}</button>
                            </form>
                        </li>
                    @else
                        <div class="divider my-1"></div>
                        <li class="px-1 py-1">
                            <x-auth-button />
                        </li>
                    @endif
                </ul>
            </div>

            <a href="#collections" class="btn btn-primary btn-md px-7 shadow-lg">{{ __('app.get-started') }}</a>
        </div>
    </div>

    <!-- Logo -->
    <div class="min-w-0">
        <a href="{{ route('home.index', ['language' => app()->getLocale()]) }}"
            class="btn btn-ghost justify-start p-0 md:px-4">
            <img src="{{ asset('assets/images/logo.png') }}" alt="Northwind Logo" class="h-14">
            <span class="text-lg md:text-xl font-semibold hidden sm:inline">{{ __('app.northwind') }}</span>
        </a>
    </div>

    <!-- Desktop Menu -->
    <div class="flex-none hidden md:flex">
        <ul class="menu menu-horizontal space-x-1 items-center">
            <li><a href="{{ route('home.index', ['language' => app()->getLocale()]) }}">{{ __('app.home') }}</a></li>
            <li class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost btn-sm">
                    {{ __('app.collections') }}
                    <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                        viewBox="0 0 24 24">
                        <path d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z" />
                    </svg>
                </div>
                <ul tabindex="0"
                    class="menu menu-sm dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-52 p-2 shadow">
                    @foreach ($collections as $key => $collection)
                        <li><a href="/#{{ $collection->slug }}" class="text-sm">{{ $collection->name }}</a></li>
                    @endforeach
                </ul>
            </li>
            <li><a
                    href="{{ route('products.index', ['language' => app()->getLocale()]) }}">{{ __('app.products') }}</a>
            </li>

            <!-- Language Switcher -->
            <li class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost btn-sm text-2xl">
                    {{-- {{ strtoupper(app()->getLocale()) }} --}}
                    @php
                        $segments = request()->segments();
                        if (count($segments) === 0) {
                            $segments = [app()->getLocale()];
                        }
                        $segments[0] = 'en';
                        $enPath = implode('/', $segments);
                        $segments[0] = 'ar';
                        $arPath = implode('/', $segments);
                    @endphp
                    @if (app()->getLocale() === 'en')
                        <span class="fi fi-gb text-xl"></span>
                    @else
                        <span class="fi fi-sa text-xl"></span>
                    @endif
                    <svg class="fill-current" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                        viewBox="0 0 24 24">
                        <path d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z" />
                    </svg>
                </div>
                <ul tabindex="0" class="menu dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-32 p-2 shadow">
                    <li><a href="{{ url('/' . $enPath) }}"><span class="fi fi-gb"></span> English</a></li>
                    <li><a href="{{ url('/' . $arPath) }}"><span class="fi fi-sa"></span> العربية</a></li>
                </ul>
            </li>

            @if (auth()->check())
                <div class="dropdown dropdown-end">
                    <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar">
                        <div class="w-10 rounded-full">
                            <img alt="User Avatar" src="{{ auth()->user()->avatar }}" />
                        </div>
                    </div>
                    <ul tabindex="0"
                        class="menu menu-sm dropdown-content bg-base-100 rounded-box z-[1] mt-3 w-52 p-2 shadow">
                        <li>
                            <a class="justify-between text-sm">
                                {{ __('app.profile') }}
                                <span class="badge">{{ __('app.new') }}</span>
                            </a>
                        </li>
                        <li><a class="text-sm">{{ __('app.settings') }}</a></li>
                        <li>
                            <form method="post"
                                action="{{ route('auth.logout', ['language' => app()->getLocale()]) }}"
                                class="w-full">
                                @csrf
                                <button type="submit"
                                    class="text-sm w-full text-left">{{ __('app.logout') }}</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @else
                <x-auth-button />
            @endif
        </ul>
    </div>
</header>
