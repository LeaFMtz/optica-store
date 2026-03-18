<section class="bg-white py-12" itemscope itemtype="http://schema.org/Product">
    {{-- SEO Meta Data --}}
    <meta itemprop="sku" content="{{ $this->variant->sku }}">
    <meta itemprop="url" content="{{ request()->url() }}">

    <div class="max-w-5xl px-4 mx-auto sm:px-6">
        {{-- Breadcrumbs Minimalistas alineados --}}
        <nav class="text-[9px] text-gray-400 uppercase tracking-[0.2em] flex items-center gap-2 mb-10">
            <a href="{{ url('/') }}" class="hover:text-[#71C229] transition-colors">Inicio</a>
            <span class="text-gray-200">/</span>
            <span class="text-gray-900 font-bold" itemprop="name">{{ $this->product->translateAttribute('name') }}</span>
        </nav>

        <div class="grid items-start grid-cols-1 gap-12 md:grid-cols-12">
            
            {{-- Galería de Imágenes (5/12) --}}
            <div class="md:col-span-5 space-y-4">
                <div class="bg-gray-50 rounded-xl overflow-hidden aspect-square border border-gray-100 shadow-sm">
                    @if ($this->image)
                        <img class="w-full h-full object-cover"
                             src="{{ $this->image->getUrl('large') }}"
                             alt="{{ $this->product->translateAttribute('name') }}"
                             itemprop="image" />
                    @endif
                </div>

                <div class="grid grid-cols-5 gap-2">
                    @foreach ($this->images as $image)
                        <button class="aspect-square rounded-lg border-2 @if($this->image->id === $image->id) border-[#71C229] @else border-transparent @endif overflow-hidden transition-all bg-gray-50"
                                wire:key="image_{{ $image->id }}">
                            <img loading="lazy" class="w-full h-full object-cover opacity-80 hover:opacity-100 transition-opacity"
                                 src="{{ $image->getUrl('small') }}"
                                 alt="{{ $this->product->translateAttribute('name') }}" />
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Información de Producto (7/12) - Bloque Alineado --}}
            <div class="md:col-span-7 flex flex-col items-start">
                <div class="max-w-md w-full space-y-6">
                    {{-- Título y Marca --}}
                    <div class="space-y-1">
                        <span class="text-[10px] font-black text-[#71C229] uppercase tracking-widest block">Óptica Guzmán</span>
                        <h1 class="text-3xl font-black text-gray-900 leading-none uppercase tracking-tighter" itemprop="name">
                            {{ $this->product->translateAttribute('name') }}
                        </h1>
                        <p class="text-[9px] text-gray-400 font-mono">REF: {{ $this->variant->sku }}</p>
                    </div>

                    {{-- Precio --}}
                    <div class="py-4 border-y border-gray-50 flex items-baseline gap-2" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                        <span class="text-2xl font-black text-gray-900" itemprop="price">
                            <x-product-price :variant="$this->variant" />
                        </span>
                        <span class="text-[9px] text-gray-400 font-bold uppercase tracking-widest">Contado / Transferencia</span>
                        <meta itemprop="priceCurrency" content="ARS">
                    </div>

                    {{-- Selector de Opciones --}}
                    <form class="space-y-6">
                        @foreach ($this->productOptions as $option)
                            <div class="space-y-3">
                                <label class="text-[10px] font-black text-gray-900 uppercase tracking-widest flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 bg-[#71C229] rounded-full"></span>
                                    {{ $option['option']->translate('name') }}
                                </label>

                                <div class="flex flex-wrap gap-2"
                                     x-data="{ selectedOption: @entangle('selectedOptionValues').live }">
                                    @foreach ($option['values'] as $value)
                                        <button class="px-5 py-2.5 text-[10px] font-bold uppercase border-2 rounded-xl transition-all duration-200"
                                                type="button"
                                                wire:click="$set('selectedOptionValues.{{ $option['option']->id }}', {{ $value->id }})"
                                                :class="Object.values(selectedOption).includes({{ $value->id }}) 
                                                    ? 'bg-[#71C229] border-[#71C229] text-white shadow-md' 
                                                    : 'bg-white border-gray-100 text-gray-400 hover:border-[#71C229] hover:text-[#71C229]'">
                                            {{ $value->translate('name') }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        {{-- Compra - Ubicación Premium --}}
                        <div class="pt-4 pb-8">
                            <livewire:components.add-to-cart :purchasable="$this->variant"
                                                             :wire:key="$this->variant->id">
                        </div>
                    </form>

                    {{-- Descripción Alineada --}}
                    <div class="pt-6 border-t border-gray-50">
                        <label class="text-[10px] font-black text-gray-900 uppercase tracking-widest block mb-3">Detalles del producto</label>
                        <div class="prose prose-sm max-w-none text-gray-500 text-[11px] leading-relaxed" itemprop="description">
                            {!! $this->product->translateAttribute('description') !!}
                        </div>
                    </div>

                    {{-- Badges de Confianza --}}
                    <div class="grid grid-cols-2 gap-4 pt-6 border-t border-gray-50">
                        <div class="flex items-center gap-2 text-[9px] font-black text-gray-400 uppercase tracking-tighter">
                            <svg class="w-4 h-4 text-[#71C229]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                            Transacción Segura
                        </div>
                        <div class="flex items-center gap-2 text-[9px] font-black text-gray-400 uppercase tracking-tighter">
                            <svg class="w-4 h-4 text-[#71C229]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                            Garantía de Óptica
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
