<div>
    <div class="flex flex-col sm:flex-row gap-3">
        {{-- Boutique Stepper --}}
        <div class="w-full sm:w-32 h-14 bg-gray-50 border border-gray-100 rounded-xl flex items-center overflow-hidden transition-all hover:bg-white hover:border-gray-200 shadow-sm">
            <button type="button" 
                    wire:click="quantity > 1 ? $set('quantity', {{ $quantity - 1 }}) : null"
                    class="flex-1 h-full flex items-center justify-center text-gray-400 hover:text-black transition-colors active:scale-90">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                </svg>
            </button>
            
            <div class="w-8 text-center">
                <span class="text-xs font-bold text-gray-900 tabular-nums">{{ $quantity }}</span>
            </div>

            <button type="button" 
                    wire:click="$set('quantity', {{ $quantity + 1 }})"
                    class="flex-1 h-full flex items-center justify-center text-gray-400 hover:text-[#71C229] transition-colors active:scale-90">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                </svg>
            </button>
        </div>

        {{-- CTA Button --}}
        <button type="submit"
                class="flex-1 h-14 px-8 text-[10px] font-bold uppercase tracking-[0.3em] text-white bg-black rounded-xl hover:bg-[#71C229] hover:text-black transition-all duration-500 shadow-lg shadow-black/5 hover:shadow-[#71C229]/20 flex items-center justify-center gap-3 group/add active:scale-[0.98]"
                wire:click.prevent="addToCart">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform group-hover/add:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <span>Añadir al carrito</span>
        </button>
    </div>

    @if ($errors->has('quantity'))
        <div class="p-3 mt-4 text-[9px] font-bold text-center text-red-600 rounded-xl bg-red-50 border border-red-100 uppercase tracking-[0.2em] shadow-sm animate-bounce" role="alert">
            {{ $errors->first('quantity') }}
        </div>
    @endif
</div>
