@props(['product'])

<article class="group relative bg-white rounded-xl border border-gray-100 shadow-md hover:shadow-2xl transition-all duration-500 flex flex-col h-full overflow-hidden" 
         itemscope itemtype="http://schema.org/Product">
    
    {{-- Imagen del Producto con Zoom --}}
    <a href="{{ route('product.view', $product->defaultUrl->slug) }}" 
       class="block aspect-square overflow-hidden bg-gray-50" 
       wire:navigate>
        @if ($product->thumbnail)
            <img class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                 src="{{ $product->thumbnail->getUrl('medium') }}"
                 alt="{{ $product->translateAttribute('name') }}"
                 itemprop="image" />
        @else
            <div class="w-full h-full flex items-center justify-center text-gray-300">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
        @endif
    </a>

    {{-- Información del Producto --}}
    <div class="p-5 flex flex-col flex-1">
        <div class="mb-2">
            <h3 class="text-sm font-bold text-gray-900 group-hover:text-[#71C229] transition-colors line-clamp-2 min-h-[2.5rem]" 
                itemprop="name">
                <a href="{{ route('product.view', $product->defaultUrl->slug) }}" wire:navigate>
                    {{ $product->translateAttribute('name') }}
                </a>
            </h3>
        </div>

        <div class="mt-auto pt-4 flex items-center justify-between border-t border-gray-50">
            <div class="flex flex-col" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                <span class="text-[10px] text-gray-400 uppercase font-bold tracking-widest">Desde</span>
                <span class="text-lg font-black text-[#71C229]" itemprop="price">
                    <x-product-price :product="$product" />
                </span>
                <meta itemprop="priceCurrency" content="ARS" />
            </div>

            {{-- Botón de Acción --}}
            <a href="{{ route('product.view', $product->defaultUrl->slug) }}" 
               wire:navigate
               class="bg-black text-white p-3 rounded-lg hover:bg-[#71C229] transition-colors shadow-sm"
               title="Ver detalles de {{ $product->translateAttribute('name') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </a>
        </div>
    </div>
</article>
