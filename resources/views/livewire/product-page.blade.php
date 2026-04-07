<section class="bg-white py-12 lg:py-24" itemscope itemtype="http://schema.org/Product">
    {{-- SEO Meta Data --}}
    <meta itemprop="sku" content="{{ $this->variant->sku }}">
    <meta itemprop="url" content="{{ request()->url() }}">

    <div class="max-w-screen-xl px-4 mx-auto sm:px-6 lg:px-8">
        {{-- Breadcrumbs Minimalistas --}}
        <nav class="text-[9px] font-black uppercase tracking-[0.2em] flex items-center gap-2 mb-10">
            <a href="{{ url('/') }}" class="text-gray-400 hover:text-[#71C229] transition-colors">Inicio</a>
            <span class="text-gray-200">/</span>
            <span class="text-gray-900" itemprop="name">{{ $this->product->translateAttribute('name') }}</span>
        </nav>

        <div class="grid items-start grid-cols-1 gap-12 lg:grid-cols-11">
            
            {{-- Galería de Imágenes (5/11 para compactar) --}}
            <div class="lg:col-span-5 space-y-4">
                <div class="bg-gray-50 rounded-2xl overflow-hidden aspect-[4/5] border border-gray-100 shadow-sm relative group/main">
                    @if ($this->image)
                        <img class="w-full h-full object-cover transition-transform duration-700 group-hover/main:scale-105"
                             src="{{ $this->image->getUrl('large') }}"
                             alt="{{ $this->product->translateAttribute('name') }}"
                             itemprop="image" />
                    @endif
                    <div class="absolute inset-0 bg-black/0 group-hover/main:bg-black/5 transition-colors duration-500"></div>
                </div>

                <div class="grid grid-cols-5 gap-2">
                    @foreach ($this->images as $image)
                        <button class="aspect-square rounded-xl border-2 transition-all duration-300 overflow-hidden bg-gray-50 @if($this->image->id === $image->id) border-[#71C229] shadow-lg shadow-[#71C229]/10 @else border-transparent hover:border-gray-200 @endif"
                                wire:click="$set('selectedOptionValues', {{ json_encode($this->variant->values->pluck('id')) }})" {{-- Placeholder for image sync --}}
                                wire:key="image_{{ $image->id }}">
                            <img loading="lazy" class="w-full h-full object-cover @if($this->image->id === $image->id) opacity-100 @else opacity-60 hover:opacity-100 @endif transition-opacity"
                                 src="{{ $image->getUrl('small') }}"
                                 alt="{{ $this->product->translateAttribute('name') }}" />
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Información de Producto (6/11) --}}
            <div class="lg:col-span-6 flex flex-col items-start pt-2 lg:sticky lg:top-32">
                <div class="w-full space-y-8">
                    {{-- Título y Marca --}}
                    <div class="space-y-3">
                        <span class="text-[9px] font-black text-[#71C229] uppercase tracking-[0.3em] block">Óptica Guzmán — Premium Eyewear</span>
                        <h1 class="text-3xl sm:text-4xl font-black text-gray-900 leading-none uppercase tracking-tighter italic" itemprop="name">
                            {{ $this->product->translateAttribute('name') }}
                        </h1>
                        <div class="flex items-center gap-3 pt-1">
                            <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest border-r border-gray-200 pr-3">SKU: {{ $this->variant->sku }}</p>
                            <span class="text-[8px] font-black text-white bg-black px-2 py-0.5 rounded uppercase tracking-widest">Original</span>
                        </div>
                    </div>

                    {{-- Precio --}}
                    <div class="py-6 border-y border-gray-100 flex flex-col gap-1" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                        <div class="flex items-baseline gap-2">
                            <span class="text-3xl font-black text-gray-900" itemprop="price">
                                <x-product-price :variant="$this->variant" />
                            </span>
                            <span class="text-[9px] text-[#71C229] font-black uppercase tracking-widest italic">Mejor precio</span>
                        </div>
                        <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest">Contado / Transferencia / 1 Pago</p>
                        <meta itemprop="priceCurrency" content="ARS">
                    </div>

                    {{-- Selector de Opciones --}}
                    <div class="space-y-6">
                        @foreach ($this->productOptions as $option)
                            <div class="space-y-3">
                                <label class="text-[9px] font-black text-gray-900 uppercase tracking-[0.2em] flex items-center gap-2">
                                    <span class="w-1 h-1 bg-[#71C229] rounded-full"></span>
                                    {{ $option['option']->translate('name') }}
                                </label>

                                <div class="flex flex-wrap gap-2"
                                     x-data="{ selectedOption: @entangle('selectedOptionValues').live }">
                                    @foreach ($option['values'] as $value)
                                        <button class="px-5 py-2.5 text-[9px] font-black uppercase tracking-widest border-2 rounded-xl transition-all duration-300 shadow-sm active:scale-95"
                                                type="button"
                                                wire:click="$set('selectedOptionValues.{{ $option['option']->id }}', {{ $value->id }})"
                                                :class="Object.values(selectedOption).includes({{ $value->id }}) 
                                                    ? 'bg-[#71C229] border-[#71C229] text-white shadow-[#71C229]/20' 
                                                    : 'bg-white border-gray-100 text-gray-400 hover:border-[#71C229] hover:text-[#71C229]'">
                                            {{ $value->translate('name') }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        {{-- Compra --}}
                        <div class="pt-2">
                            <livewire:components.add-to-cart :purchasable="$this->variant"
                                                             :wire:key="$this->variant->id">
                        </div>
                    </div>

                    {{-- Descripción --}}
                    <div class="pt-8 border-t border-gray-100">
                        <label class="text-[9px] font-black text-gray-900 uppercase tracking-[0.2em] block mb-3">Detalles del producto</label>
                        <div class="prose prose-sm max-w-none text-gray-500 text-[10px] leading-relaxed uppercase tracking-tight font-medium" itemprop="description">
                            {!! $this->product->translateAttribute('description') !!}
                        </div>
                    </div>

                    {{-- Badges de Confianza --}}
                    <div class="grid grid-cols-2 gap-4 pt-8 border-t border-gray-100">
                        <div class="flex flex-col gap-1.5">
                            <svg class="w-4 h-4 text-[#71C229]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Transacción Segura</span>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <svg class="w-4 h-4 text-[#71C229]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Garantía de Óptica</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Espacio para futuros carruseles (Relacionados, etc.) --}}
        <div class="mt-24 pt-24 border-t border-gray-100">
            <div class="flex items-end justify-between mb-12">
                <h3 class="text-2xl font-black uppercase tracking-tighter italic text-black">
                    Productos Similares
                    <span class="block text-[10px] font-black text-[#71C229] uppercase tracking-[0.3em] mt-2 italic not-italic">También te pueden gustar</span>
                </h3>
            </div>
            
            {{-- TODO: Implementar carrusel de productos relacionados aquí --}}
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 opacity-50 grayscale transition hover:grayscale-0">
                <div class="aspect-[4/5] bg-gray-50 rounded-2xl animate-pulse"></div>
                <div class="aspect-[4/5] bg-gray-50 rounded-2xl animate-pulse"></div>
                <div class="aspect-[4/5] bg-gray-50 rounded-2xl animate-pulse"></div>
                <div class="aspect-[4/5] bg-gray-50 rounded-2xl animate-pulse"></div>
            </div>
        </div>
    </div>
</section>
