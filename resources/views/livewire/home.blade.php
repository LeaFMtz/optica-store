<div>

    {{-- Hero Banners Carousel --}}
    @if ($this->heroBanners->count())
        <div class="relative w-full h-[500px] overflow-hidden" id="hero-carousel">
            <!-- Slides -->
            <div class="relative w-full h-full">
                @foreach ($this->heroBanners as $index => $banner)
                    <div 
                        class="hero-slide absolute inset-0 w-full h-full transition-opacity duration-500"
                        data-index="{{ $index }}"
                        style="{{ $index === 0 ? 'opacity: 1;' : 'opacity: 0;' }}"
                    >
                        <a href="{{ $banner->url ?? '#' }}" class="block w-full h-full">
                            <img 
                                src="{{ asset('storage/' . $banner->image_path) }}" 
                                alt="{{ $banner->title }}"
                                class="w-full h-full object-cover"
                            />
                        </a>
                    </div>
                @endforeach
            </div>

            <!-- Previous Button -->
            @if ($this->heroBanners->count() > 1)
                <button 
                    onclick="changeSlide(-1)"
                    class="absolute left-4 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-3 rounded-full transition-colors duration-200 z-10"
                    aria-label="Banner anterior"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>

                <!-- Next Button -->
                <button 
                    onclick="changeSlide(1)"
                    class="absolute right-4 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-3 rounded-full transition-colors duration-200 z-10"
                    aria-label="Siguiente banner"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>

                <!-- Indicators -->
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-10" id="carousel-indicators">
                    @foreach ($this->heroBanners as $index => $banner)
                        <button 
                            onclick="goToSlide({{ $index }})"
                            class="indicator w-3 h-3 rounded-full transition-all duration-200 {{ $index === 0 ? 'bg-white scale-110' : 'bg-white/50 hover:bg-white/75' }}"
                            aria-label="Ir al banner {{ $index + 1 }}"
                        ></button>
                    @endforeach
                </div>
            @endif
        </div>

        <script>
        (function() {
            let currentSlide = 0;
            const slides = document.querySelectorAll('.hero-slide');
            const totalSlides = slides.length;
            let intervalId = null;

            function showSlide(index) {
                slides.forEach((slide, i) => {
                    slide.style.opacity = i === index ? '1' : '0';
                });
                
                const indicators = document.querySelectorAll('.indicator');
                indicators.forEach((ind, i) => {
                    ind.className = 'indicator w-3 h-3 rounded-full transition-all duration-200 ' + 
                        (i === index ? 'bg-white scale-110' : 'bg-white/50 hover:bg-white/75');
                });
                
                currentSlide = index;
            }

            function nextSlide() {
                showSlide((currentSlide + 1) % totalSlides);
            }

            window.changeSlide = function(direction) {
                showSlide((currentSlide + direction + totalSlides) % totalSlides);
                resetInterval();
            };

            window.goToSlide = function(index) {
                showSlide(index);
                resetInterval();
            };

            function resetInterval() {
                if (intervalId) {
                    clearInterval(intervalId);
                }
                if (totalSlides > 1) {
                    intervalId = setInterval(nextSlide, 5000);
                }
            }

            // Auto-play
            if (totalSlides > 1) {
                intervalId = setInterval(nextSlide, 5000);
            }
        })();
        </script>
    @endif

    <div class="max-w-screen-xl px-4 py-12 mx-auto space-y-16 sm:px-6 lg:px-8">
        
        {{-- Middle Banners - Dynamic Bento Grid --}}
        @if ($this->middleBanners->count())
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach ($this->middleBanners as $index => $banner)
                    @php
                        $count = $this->middleBanners->count();
                        $colSpan = 'md:col-span-1';
                        $aspect = 'aspect-[4/3]'; // Aspecto base

                        if ($count === 1) {
                            $colSpan = 'md:col-span-3';
                            $aspect = 'aspect-[3/1] lg:aspect-[4/1]'; // Panorámico
                        } elseif ($count === 2) {
                            if ($index === 0) {
                                $colSpan = 'md:col-span-2';
                                $aspect = 'aspect-[2/1] lg:aspect-[2.5/1]'; // El grande
                            } else {
                                $colSpan = 'md:col-span-1';
                                $aspect = 'aspect-[4/3] lg:aspect-[5/4]'; // El de apoyo
                            }
                        }
                    @endphp

                    <a href="{{ $banner->url ?? '#' }}" 
                       class="block group relative rounded-2xl overflow-hidden shadow-sm hover:shadow-2xl hover:-translate-y-1 transition-all duration-500 bg-gray-50 border border-gray-100 {{ $colSpan }} {{ $aspect }}">
                        <img 
                            src="{{ asset('storage/' . $banner->image_path) }}" 
                            alt="{{ $banner->title }}"
                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                        />
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors duration-500"></div>
                    </a>
                @endforeach
            </div>
        @endif

        {{-- Offers Carousel Section --}}
        @if ($this->saleCollection)
            <div class="border-t border-gray-50">
                <x-collection-sale />
            </div>
        @endif

        {{-- Random Collection Section --}}
        @if ($this->randomCollection)
            <section class="pt-8 border-t border-gray-50">
                <div class="flex items-end justify-between mb-10">
                    <h2 class="text-3xl font-black tracking-tighter uppercase text-black italic">
                        {{ $this->randomCollection->translateAttribute('name') }}
                        <span class="block text-[10px] font-black text-[#71C229] uppercase tracking-[0.3em] mt-2 italic not-italic">Selección exclusiva para vos</span>
                    </h2>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($this->randomCollection->products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Bottom Banners - Dynamic Bento Grid --}}
        @if ($this->bottomBanners->count())
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-12 border-t border-gray-50">
                @foreach ($this->bottomBanners as $index => $banner)
                    @php
                        $count = $this->bottomBanners->count();
                        $colSpan = 'md:col-span-1';
                        $aspect = 'aspect-[4/3]'; // Aspecto base

                        if ($count === 1) {
                            $colSpan = 'md:col-span-3';
                            $aspect = 'aspect-[3/1] lg:aspect-[4/1]'; // Panorámico
                        } elseif ($count === 2) {
                            // Si son 2, partimos al medio pero más apaisados
                            $colSpan = 'md:col-span-1.5'; // No existe, usamos md:grid-cols-2 para este caso especial
                            $aspect = 'aspect-[2/1] lg:aspect-[2.5/1]';
                        } elseif ($count >= 3) {
                            if ($index === 0) {
                                $colSpan = 'md:col-span-2'; // El destacado
                                $aspect = 'aspect-[2/1] lg:aspect-[2.5/1]'; 
                            } else {
                                $colSpan = 'md:col-span-1'; // Los secundarios
                                $aspect = 'aspect-[4/3] lg:aspect-[5/4]';
                            }
                        }
                    @endphp

                    {{-- Ajuste de grid dinámico para 2 banners exactos --}}
                    @if($count === 2 && $index === 0)
                        </div><div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-12 border-t border-gray-50">
                    @endif

                    <a href="{{ $banner->url ?? '#' }}" 
                       class="block group relative rounded-2xl overflow-hidden shadow-sm hover:shadow-2xl hover:-translate-y-1 transition-all duration-500 bg-gray-50 border border-gray-100 {{ $count !== 2 ? $colSpan : '' }} {{ $aspect }}">
                        <img 
                            src="{{ asset('storage/' . $banner->image_path) }}" 
                            alt="{{ $banner->title }}"
                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                        />
                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors duration-500"></div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</div>
