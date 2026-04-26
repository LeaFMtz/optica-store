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
                        <div class="pt-2" x-data="{
                            open: false,
                            step: 1,
                            selectedUso: null,
                            selectedLens: null,
                            lensMap: {{ Js::from($this->lensMap) }},
                            get availableLensValues() {
                                return this.selectedUso ? (this.lensMap[this.selectedUso]?.values ?? []) : []
                            },
                            get childOptionName() {
                                return this.selectedUso ? (this.lensMap[this.selectedUso]?.child_option_name ?? 'Tipo de lente') : 'Tipo de lente'
                            },
                            get canConfirm() {
                                return this.selectedUso !== null && this.selectedLens !== null
                            },
                            selectUso(id) { this.selectedUso = id; this.selectedLens = null; this.step = 2; },
                            selectLens(id) { this.selectedLens = id; },
                            confirm() { $wire.addWithLens(this.selectedUso, this.selectedLens); this.open = false; this.step = 1; this.selectedUso = null; this.selectedLens = null; },
                            back() { this.step = 1; this.selectedLens = null; },
                            close() { this.open = false; this.step = 1; this.selectedUso = null; this.selectedLens = null; }
                        }">
                            @if($this->hasLensOption())
                                {{-- Dual CTA --}}
                                <div class="flex flex-col sm:flex-row gap-3">
                                    {{-- Solo marco --}}
                                    <button type="button"
                                            wire:click="addFrameOnly"
                                            class="flex-1 h-14 px-8 text-[10px] font-bold uppercase tracking-[0.3em] text-gray-700 bg-gray-50 border border-gray-200 rounded-xl hover:border-gray-400 transition-all duration-300 flex items-center justify-center gap-3 active:scale-[0.98]">
                                        Solo Marco
                                    </button>
                                    {{-- Con lente --}}
                                    <button type="button"
                                            @click="open = true"
                                            class="flex-1 h-14 px-8 text-[10px] font-bold uppercase tracking-[0.3em] text-white bg-black rounded-xl hover:bg-[#71C229] hover:text-black transition-all duration-500 shadow-lg shadow-black/5 hover:shadow-[#71C229]/20 flex items-center justify-center gap-3 group/add active:scale-[0.98]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        Agregar con Lente
                                    </button>
                                </div>

                                {{-- Error inline --}}
                                @error('lens')
                                    <div class="p-3 mt-4 text-[9px] font-bold text-center text-red-600 rounded-xl bg-red-50 border border-red-100 uppercase tracking-[0.2em] shadow-sm" role="alert">
                                        {{ $message }}
                                    </div>
                                @enderror

                                {{-- Configurador Modal --}}
                                <div x-show="open"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0"
                                     x-transition:enter-end="opacity-100"
                                     x-transition:leave="transition ease-in duration-150"
                                     x-transition:leave-start="opacity-100"
                                     x-transition:leave-end="opacity-0"
                                     class="fixed inset-0 z-50 flex items-center justify-center p-4"
                                     style="display: none;">

                                    {{-- Backdrop --}}
                                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="close()"></div>

                                    {{-- Modal panel --}}
                                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">

                                        {{-- Progress dots + close --}}
                                        <div class="flex items-center justify-between px-8 pt-8 pb-4">
                                            <div class="w-8"></div>
                                            <div class="flex items-center gap-3">
                                                <div :class="step >= 1 ? 'bg-[#71C229]' : 'bg-gray-200'" class="w-3 h-3 rounded-full transition-colors duration-300"></div>
                                                <div class="w-8 h-px bg-gray-200"></div>
                                                <div :class="step >= 2 ? 'bg-[#71C229]' : 'bg-gray-200'" class="w-3 h-3 rounded-full transition-colors duration-300"></div>
                                            </div>
                                            <button @click="close()" class="text-gray-400 hover:text-gray-700 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>

                                        {{-- Step content --}}
                                        <div class="flex-1 overflow-y-auto px-8 py-4">

                                            {{-- Step 1: Uso --}}
                                            <div x-show="step === 1">
                                                <h2 class="text-xl font-black uppercase tracking-tight text-center mb-8">Uso</h2>
                                                <template x-for="(data, usoId) in lensMap" :key="usoId">
                                                    <button @click="selectUso(usoId)"
                                                            :class="selectedUso == usoId ? 'border-[#71C229] bg-[#71C229]/5' : 'border-gray-200 hover:border-gray-400'"
                                                            class="inline-flex flex-col items-center gap-3 p-6 border-2 rounded-2xl transition-all duration-200 text-left m-2 w-44">
                                                        <span class="text-xs font-black uppercase tracking-wider text-center" x-text="data.uso_name"></span>
                                                    </button>
                                                </template>
                                            </div>

                                            {{-- Step 2: Tipo de lente --}}
                                            <div x-show="step === 2">
                                                <div class="flex items-center gap-4 mb-8">
                                                    <button @click="back()" class="text-gray-400 hover:text-gray-700 transition-colors">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                                                    </button>
                                                    <h2 class="flex-1 text-xl font-black uppercase tracking-tight text-center" x-text="childOptionName"></h2>
                                                </div>
                                                <div class="flex flex-wrap justify-center gap-4">
                                                    <template x-for="lens in availableLensValues" :key="lens.id">
                                                        <button @click="selectLens(lens.id)"
                                                                :class="selectedLens === lens.id ? 'border-[#71C229] bg-[#71C229]/5' : 'border-gray-200 hover:border-gray-400'"
                                                                class="flex flex-col items-center gap-3 p-6 border-2 rounded-2xl transition-all duration-200 w-44">
                                                            <span class="text-xs font-black uppercase tracking-wider text-center" x-text="lens.name"></span>
                                                        </button>
                                                    </template>
                                                </div>

                                                <div class="flex justify-center mt-8">
                                                    <button @click="confirm()"
                                                            :disabled="!canConfirm"
                                                            :class="canConfirm ? 'bg-black hover:bg-[#71C229] hover:text-black' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                                                            class="px-12 h-14 text-[10px] font-bold uppercase tracking-[0.3em] text-white rounded-xl transition-all duration-300">
                                                        Agregar al carrito
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Bottom bar --}}
                                        <div class="border-t border-gray-100 px-8 py-4 flex items-center gap-4 bg-gray-50/50">
                                            @if($this->image)
                                                <img src="{{ $this->image->getUrl('small') }}" class="w-12 h-12 object-cover rounded-xl" alt="">
                                            @endif
                                            <div>
                                                <p class="text-xs font-black uppercase tracking-wider text-gray-900">{{ $this->product->translateAttribute('name') }}</p>
                                                <x-product-price :variant="$this->variant" />
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            @else
                                <livewire:components.add-to-cart :purchasable="$this->variant" :wire:key="$this->variant->id">
                            @endif
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
