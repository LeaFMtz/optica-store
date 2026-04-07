<section class="py-12" 
         x-data="{ 
            scroll: 0,
            scrollNext() {
                const el = this.$refs.carousel;
                const cardWidth = el.querySelector('article').offsetWidth + 16;
                el.scrollBy({ left: cardWidth * 2, behavior: 'smooth' });
            },
            scrollPrev() {
                const el = this.$refs.carousel;
                const cardWidth = el.querySelector('article').offsetWidth + 16;
                el.scrollBy({ left: -cardWidth * 2, behavior: 'smooth' });
            }
         }">
    
    {{-- Header del Carousel --}}
    <div class="flex items-end justify-between mb-8 px-2">
        <div class="flex items-baseline gap-4">
            <h2 class="text-3xl font-black tracking-tighter uppercase text-black">
                ¡OFERTAS!
            </h2>
            
            <a href="{{ route('collection.view', $this->saleCollection->defaultUrl->slug) }}"
               wire:navigate
               class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-[#71C229] transition-all flex items-center gap-1 group/link"
            >
                Ver todas
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 transition-transform group-hover/link:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
        
        <div class="flex gap-2">
            <button @click="scrollPrev()" 
                    class="p-2 border-2 border-black rounded-full hover:bg-black hover:text-white transition-all active:scale-90"
                    aria-label="Anterior">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button @click="scrollNext()" 
                    class="p-2 border-2 border-black rounded-full hover:bg-black hover:text-white transition-all active:scale-90"
                    aria-label="Siguiente">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>

    {{-- Contenedor del Carousel --}}
    <div class="relative group">
        <div x-ref="carousel" 
             class="flex gap-4 pb-8 overflow-x-auto snap-x snap-mandatory no-scrollbar scroll-smooth">
            @foreach ($this->saleProducts as $product)
                <div class="flex-none w-[280px] sm:w-[320px] snap-start">
                    <x-product-card :product="$product" />
                </div>
            @endforeach
        </div>
        
        {{-- Gradientes laterales para indicar scroll (opcional pero queda bien) --}}
        <div class="absolute inset-y-0 left-0 w-8 bg-gradient-to-r from-white/50 to-transparent pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity"></div>
        <div class="absolute inset-y-0 right-0 w-8 bg-gradient-to-l from-white/50 to-transparent pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity"></div>
    </div>

    <style>
        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</section>
