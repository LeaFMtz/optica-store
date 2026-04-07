<form wire:submit="save"
      class="border rounded shadow-lg">
    <div class="flex justify-between p-4 font-medium">
        <span class="text-xl">Shipping Option</span>
    </div>
    @if ($this->shippingAddress)
        <div class="p-4 border-t">
            @foreach ($this->shippingOptions as $option)
                <label class="flex items-center w-full cursor-pointer"
                       wire:key="shipping_option_{{ $option->getIdentifier() }}">
                    <input type="radio"
                           wire:model.live="chosenOption"
                           value="{{ $option->getIdentifier() }}" />
                    <div class="flex items-center ml-2">
                        <span class="block mr-2 text-2xl">{{ $option->getPrice()->formatted() }}</span>
                        {{ $option->name }}
                    </div>
                </label>
            @endforeach
        </div>
    @else
    @endif
    @if ($errors->has('chosenOption'))
        <p class="p-4 text-sm text-red-500">{{ $errors->first('chosenOption') }}</p>
    @endif
    <div class="flex justify-end w-full p-8 bg-gray-50/50 border-t border-gray-50">
        <div>
            <button type="submit"
                    wire:key="submit_btn"
                    class="px-10 py-4 text-[10px] font-black uppercase tracking-widest text-white bg-black rounded-xl hover:bg-[#71C229] transition-all duration-300 shadow-lg shadow-black/10 flex items-center gap-2 group">
                Continuar
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
            </button>
        </div>
    </div>
</form>
