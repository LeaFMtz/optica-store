<div class="contents">
    <div class="bg-[#71C229] text-white text-xs py-1.5 px-6 flex justify-end items-center space-x-2 w-full relative z-[60]">
        @auth
            <span class="flex items-center gap-1">
                Hola, {{ auth()->user()->name }}
            </span>
            <span class="text-white/50 mx-1">|</span>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="hover:underline">Cerrar sesión</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="hover:underline flex items-center gap-1" wire:navigate>
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Iniciar sesión
            </a>
            <span class="text-white/50 mx-1">|</span>
            <a href="{{ route('register') }}" class="hover:underline" wire:navigate>Crear cuenta</a>
        @endauth
    </div>

    <header class="w-full bg-black text-white sticky top-0 z-50">
        <div class="px-4 py-4 mx-auto max-w-screen-2xl sm:px-6 lg:px-8 w-full">
            <div class="flex items-center justify-between">

            <div class="hidden lg:flex flex-1">
                <x-header.search class="max-w-xs w-full" />
            </div>

            <div class="flex-1 flex justify-start lg:justify-center">
                <a class="block hover:opacity-80 transition"
                    href="{{ url('/') }}" wire:navigate>
                    <span class="sr-only">Home</span>
                    <img src="{{ asset('images/logo.webp') }}" alt="Óptica Guzmán" class="h-10 w-auto lg:h-16 mx-auto">
                </a>
            </div>

            <div class="flex items-center justify-end flex-1 gap-4">

                @livewire('components.cart')

                <div x-data="{ mobileMenu: false }" class="lg:hidden">
                    <button x-on:click="mobileMenu = !mobileMenu"
                        class="flex flex-shrink-0 items-center justify-center w-10 h-10 rounded text-white hover:text-[#71C229]">
                        <span class="sr-only">Toggle Menu</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div x-cloak x-transition x-show="mobileMenu"
                        class="absolute right-0 top-[60px] z-50 w-screen p-4 sm:max-w-xs">
                        <ul x-on:click.away="mobileMenu = false"
                            class="p-6 space-y-4 bg-[#111] border border-gray-800 shadow-xl rounded-xl text-white">
                            <li><a href="{{ url('/') }}" wire:navigate
                                    class="text-sm font-medium hover:text-[#71C229] transition">Inicio</a></li>
                            @auth
                                <li><span class="text-sm font-medium text-gray-400">Hola, {{ auth()->user()->name }}</span></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="text-sm font-medium hover:text-[#71C229] transition">Cerrar sesión</button>
                                    </form>
                                </li>
                            @else
                                <li><a href="{{ route('login') }}" wire:navigate
                                        class="text-sm font-medium hover:text-[#71C229] transition">Iniciar sesión</a></li>
                                <li><a href="{{ route('register') }}" wire:navigate
                                        class="text-sm font-medium hover:text-[#71C229] transition">Crear cuenta</a></li>
                            @endauth
                            <hr class="border-gray-800">
                            @foreach ($this->collections as $collection)
                            <li>
                                <a class="text-sm font-medium hover:text-[#71C229] transition"
                                    href="{{ route('collection.view', $collection->defaultUrl->slug) }}" wire:navigate>
                                    {{ $collection->translateAttribute('name') }}
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>



        <nav class="mt-4 hidden lg:flex justify-center gap-6 text-xs font-bold text-white/80 border-t border-gray-800 pt-4 pb-2 relative"
            x-data="{ showProducts: false }">
            <a href="{{ url('/') }}" wire:navigate class="hover:text-[#71C229] transition py-2">Inicio</a>

            <div @mouseenter="showProducts = true" @mouseleave="showProducts = false">
                <a href="#" class="hover:text-[#71C229] transition py-2 inline-block">Productos</a>

                <div x-show="showProducts" x-transition x-cloak
                    class="absolute top-[48px] left-0 w-full bg-black border-t-2 border-[#71C229] p-8 shadow-2xl z-50">
                    <div class="max-w-screen-2xl mx-auto flex flex-wrap gap-x-12 gap-y-6">
                        @foreach ($this->collections as $collection)
                        <a href="{{ route('collection.view', $collection->defaultUrl->slug) }}" wire:navigate
                            class="text-white hover:text-[#71C229] text-sm font-medium transition min-w-[150px]">
                            {{ $collection->translateAttribute('name') }}
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <a href="{{ route('contact.view') }}" wire:navigate class="hover:text-[#71C229] transition py-2">Contacto</a>
            <a href="{{ route('refund-policy.view') }}" wire:navigate class="hover:text-[#71C229] transition py-2">Política de Devolución</a>
            <a href="{{ route('faq.view') }}" wire:navigate class="hover:text-[#71C229] transition py-2">Preguntas Frecuentes</a>
        </nav>
        </div>
    </header>
</div>
