<section class="relative overflow-hidden bg-black rounded-3xl border border-gray-800 shadow-2xl group" aria-labelledby="sale-heading">
    <div class="max-w-screen-xl px-8 py-8 mx-auto lg:py-12 relative z-10">
        <div class="grid grid-cols-1 gap-12 lg:grid-cols-2 lg:items-center">
            
            {{-- Texto de la Colección --}}
            <div class="text-left space-y-4">
                <div>
                    <span class="inline-block px-3 py-1 mb-3 text-[10px] font-black tracking-widest text-black uppercase bg-[#71C229] rounded-full animate-pulse">
                        Ofertas Exclusivas
                    </span>
                    <h2 id="sale-heading" class="text-3xl font-black text-white sm:text-5xl tracking-tighter uppercase leading-none">
                        {{ $this->saleCollection->translateAttribute('name') }}
                    </h2>
                </div>

                @if ($this->saleCollection->translateAttribute('description'))
                    <div class="text-base font-medium text-gray-400 leading-relaxed max-w-md line-clamp-2">
                        {!! $this->saleCollection->translateAttribute('description') !!}
                    </div>
                @endif

                <div class="pt-2">
                    <a href="{{ route('collection.view', $this->saleCollection->defaultUrl->slug) }}"
                       class="inline-flex items-center gap-3 px-6 py-3 text-xs font-black text-black uppercase transition-all bg-[#71C229] rounded-xl hover:bg-white hover:scale-105 active:scale-95 shadow-[0_0_20px_rgba(113,194,41,0.2)]"
                       wire:navigate
                    >
                        Ver Colección
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>

            {{-- Mosaico de Imágenes Compacto --}}
            <div class="relative mt-4 lg:mt-0 h-[250px] sm:h-[350px]">
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div class="w-48 h-48 bg-[#71C229] rounded-full blur-[100px] opacity-10"></div>
                </div>
                
                <div class="grid grid-cols-2 gap-3 h-full relative z-10">
                    @php $imageIndex = 0; @endphp
                    @foreach ($this->saleCollectionImages as $imageGroup)
                        <div class="space-y-3 {{ $loop->last ? 'pt-6' : '' }}">
                            @foreach ($imageGroup as $image)
                                <div class="overflow-hidden rounded-xl aspect-[16/10] sm:aspect-[4/3] border border-gray-800 shadow-lg transform transition-transform duration-700 hover:scale-105">
                                    <img class="object-cover w-full h-full opacity-90 hover:opacity-100 transition-opacity"
                                         src="{{ $image->getUrl('medium') }}"
                                         alt="{{ $this->saleCollection->translateAttribute('name') }} - Producto {{ ++$imageIndex }}"
                                         title="{{ $this->saleCollection->translateAttribute('name') }}"
                                         loading="lazy" />
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    
    {{-- Decoración de Fondo --}}
    <div class="absolute top-0 right-0 p-6 opacity-5">
        <svg class="w-32 h-32 text-white" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
        </svg>
    </div>
</section>
