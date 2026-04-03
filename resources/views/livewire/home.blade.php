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

    <div class="max-w-screen-xl px-4 py-12 mx-auto space-y-12 sm:px-6 lg:px-8">
        
        {{-- Middle Banners --}}
        @if ($this->middleBanners->count())
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($this->middleBanners as $banner)
                    <a href="{{ $banner->url ?? '#' }}" class="block">
                        <img 
                            src="{{ asset('storage/' . $banner->image_path) }}" 
                            alt="{{ $banner->title }}"
                            class="w-full h-48 object-cover rounded-lg"
                        />
                    </a>
                @endforeach
            </div>
        @endif

        @if ($this->saleCollection)
            <x-collection-sale />
        @endif

        @if ($this->randomCollection)
            <section>
                <h2 class="text-3xl font-bold">
                    {{ $this->randomCollection->translateAttribute('name') }}
                </h2>

                <div class="grid grid-cols-2 mt-8 lg:grid-cols-4 gap-x-4 gap-y-8">
                    @foreach ($this->randomCollection->products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- Bottom Banners --}}
    @if ($this->bottomBanners->count())
        <div class="max-w-screen-xl px-4 pb-12 mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach ($this->bottomBanners as $banner)
                    <a href="{{ $banner->url ?? '#' }}" class="block">
                        <img 
                            src="{{ asset('storage/' . $banner->image_path) }}" 
                            alt="{{ $banner->title }}"
                            class="w-full h-40 object-cover rounded-lg"
                        />
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
