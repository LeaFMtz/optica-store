<div>
    <div class="flex gap-2">
        <div class="w-20">
            <label for="quantity" class="sr-only">Cantidad</label>
            <input class="w-full px-1 py-3 text-sm font-bold text-center text-gray-900 bg-gray-50 border border-gray-100 rounded-xl focus:ring-[#71C229] focus:border-[#71C229] transition-all no-spinner"
                   type="number"
                   id="quantity"
                   min="1"
                   wire:model.live="quantity" />
        </div>

        <button type="submit"
                class="flex-1 px-8 py-3 text-xs font-black uppercase tracking-widest text-white bg-[#71C229] rounded-xl hover:bg-black transition-all duration-300 shadow-lg shadow-[#71C229]/20 flex items-center justify-center gap-2 group"
                wire:click.prevent="addToCart">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Añadir
        </button>
    </div>

    @if ($errors->has('quantity'))
        <div class="p-2 mt-3 text-[10px] font-bold text-center text-red-600 rounded-lg bg-red-50 border border-red-100 uppercase tracking-tighter" role="alert">
            @foreach ($errors->get('quantity') as $error)
                {{ $error }}
            @endforeach
        </div>
    @endif
</div>
