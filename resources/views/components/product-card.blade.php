@props(['product'])

@php
    $variant = $product->variants->first();
    $discountPercentage = 0;
    if ($variant) {
        $pricing = Lunar\Facades\Pricing::for($variant)->get();
        if ($pricing->base && $pricing->base->price->value > $pricing->matched->price->value) {
            $discountPercentage = round((($pricing->base->price->value - $pricing->matched->price->value) / $pricing->base->price->value) * 100);
        }
    }
@endphp

<article class="group relative bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-2xl hover:-translate-y-1 transition-all duration-500 flex flex-col h-full overflow-hidden" 
         itemscope itemtype="http://schema.org/Product">
    
    {{-- Badge de Descuento Minimalista --}}
    @if($discountPercentage > 0)
        <div class="absolute top-4 left-4 z-20">
            <span class="px-2.5 py-1 text-[9px] font-black tracking-widest text-white uppercase bg-[#71C229] rounded-lg shadow-lg">
                {{ $discountPercentage }}% OFF
            </span>
        </div>
    @endif

    {{-- Área con Link a la Ficha del Producto --}}
    <a href="{{ route('product.view', $product->defaultUrl->slug) }}" 
       class="block flex-1 flex flex-col group/card" 
       wire:navigate>
        
        {{-- Contenedor de Imagen con Overlay Suave --}}
        <div class="aspect-[4/5] overflow-hidden bg-gray-50 relative">
            @if ($product->thumbnail)
                <img class="w-full h-full object-cover transition-transform duration-700 group-hover/card:scale-105"
                     src="{{ $product->thumbnail->getUrl('medium') }}"
                     alt="{{ $product->translateAttribute('name') }}"
                     itemprop="image" />
            @else
                <div class="w-full h-full flex items-center justify-center text-gray-200">
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            @endif

            {{-- Overlay al hacer hover --}}
            <div class="absolute inset-0 bg-black/0 group-hover/card:bg-black/5 transition-colors duration-500"></div>
        </div>

        {{-- Información del Producto --}}
        <div class="p-6 flex flex-col flex-1">
            <div class="mb-4 flex-1">
                <span class="text-[9px] font-black text-[#71C229] uppercase tracking-[0.2em] mb-2 block">Premium Eyewear</span>
                <h3 class="text-xs font-bold text-gray-900 line-clamp-2 tracking-tight leading-relaxed min-h-[2.5rem]" 
                    itemprop="name">
                    {{ $product->translateAttribute('name') }}
                </h3>
            </div>

            <div class="flex flex-col pt-4 border-t border-gray-50" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                <x-product-price :product="$product" class="!flex-row !items-baseline !gap-2 !justify-start" />
                <meta itemprop="priceCurrency" content="ARS" />
            </div>
        </div>
    </a>

    {{-- Botón de Añadido Rápido Separado --}}
    @if($variant)
    <div class="px-6 pb-6">
        <button wire:click.prevent="quickAdd({{ $variant->id }})"
                class="w-full bg-black text-white px-4 py-3.5 rounded-xl hover:bg-[#71C229] transition-all duration-300 shadow-sm flex items-center justify-center gap-2 text-[10px] font-black uppercase tracking-widest group/btn active:scale-95"
                title="Añadir {{ $product->translateAttribute('name') }} al carrito">
            <span>Añadir al carrito</span>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform group-hover/btn:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
        </button>
    </div>
    @endif
</article>
