<div>
    @section('title', 'Catálogo de Lentes | Óptica Guzmán')

    <div class="bg-gray-50 min-h-screen">
        {{-- Encabezado Limpio y Profesional --}}
        <header class="bg-white border-b border-gray-100 py-16">
            <div class="max-w-screen-xl px-4 mx-auto sm:px-6 lg:px-8 text-center">
                <nav class="flex justify-center mb-4 text-[10px] font-bold tracking-widest uppercase text-gray-400 space-x-2">
                    <a href="{{ url('/') }}" class="hover:text-[#71C229] transition">Inicio</a>
                    <span>/</span>
                    <span class="text-gray-900">Catálogo</span>
                </nav>
                
                <h1 class="text-4xl font-black text-gray-900 sm:text-6xl tracking-tight uppercase">
                    Nuestros <span class="text-[#71C229]">Productos</span>
                </h1>
                <p class="mt-4 max-w-xl mx-auto text-gray-500 text-lg font-medium">
                    Calidad visual y las últimas tendencias en marcos y cristales.
                </p>
            </div>
        </header>

        {{-- Cuerpo del Catálogo --}}
        <div class="max-w-screen-xl px-4 mx-auto sm:px-6 lg:px-8 py-12">
            
            {{-- Utilidades: Contador y Orden --}}
            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-12 pb-6 border-b border-gray-100 gap-4">
                <div class="text-sm text-gray-500">
                    Mostrando <span class="text-gray-900 font-bold">{{ $this->products->firstItem() }}</span> - <span class="text-gray-900 font-bold">{{ $this->products->lastItem() }}</span> de <span class="text-gray-900 font-bold">{{ $this->products->total() }}</span> items
                </div>
                
                <div class="flex items-center gap-3">
                    <span class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Ordenar por</span>
                    <select class="bg-white text-gray-900 text-xs border border-gray-200 rounded-lg px-4 py-2 focus:ring-2 focus:ring-[#71C229]/20 focus:border-[#71C229] outline-none transition">
                        <option>Novedades</option>
                        <option>Precio: Menor a Mayor</option>
                        <option>Precio: Mayor a Menor</option>
                    </select>
                </div>
            </div>

            {{-- Grid de Productos --}}
            @if($this->products->count() > 0)
                <div class="grid grid-cols-2 gap-x-4 gap-y-12 lg:grid-cols-4 lg:gap-x-8 lg:gap-y-16">
                    @foreach ($this->products as $product)
                        <div wire:key="p-{{ $product->id }}">
                            <x-product-card :product="$product" />
                        </div>
                    @endforeach
                </div>

                {{-- Paginación --}}
                <div class="mt-20">
                    {{ $this->products->links() }}
                </div>
            @else
                <div class="py-20 text-center bg-white rounded-3xl border border-dashed border-gray-200 shadow-sm">
                    <p class="text-gray-500 font-medium italic">No hay productos disponibles en esta sección.</p>
                </div>
            @endif
        </div>
    </div>
</div>
