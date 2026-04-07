<form wire:submit="saveShippingOption"
      class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between h-16 px-8 border-b border-gray-50 bg-gray-50/50">
        <h3 class="text-xs font-black uppercase tracking-widest text-gray-900 flex items-center gap-2">
            Opciones de Envío
            <span class="h-1 w-1 rounded-full bg-[#71C229]"></span>
        </h3>

        @if ($currentStep > $step)
            <button class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-black transition-colors flex items-center gap-1 group"
                    type="button"
                    wire:click.prevent="$set('currentStep', {{ $step }})">
                Editar
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
            </button>
        @endif
    </div>

    @if ($currentStep >= $step)
        <div class="p-8">
            @if ($currentStep == $step)
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ($this->shippingOptions as $option)
                        <div>
                            <input class="hidden peer"
                                   type="radio"
                                   wire:model.live="chosenShipping"
                                   name="shippingOption"
                                   value="{{ $option->getIdentifier() }}"
                                   id="{{ $option->getIdentifier() }}" />

                            <label class="flex items-center justify-between p-5 text-[10px] font-black uppercase tracking-widest border border-gray-100 rounded-xl shadow-sm cursor-pointer peer-checked:border-[#71C229] hover:bg-gray-50 peer-checked:ring-2 peer-checked:ring-[#71C229]/20 transition-all duration-300"
                                   for="{{ $option->getIdentifier() }}">
                                <p class="text-gray-900">
                                    {{ $option->getName() }}
                                </p>

                                <p class="text-[#71C229]">
                                    {{ $option->getPrice()->formatted() }}
                                </p>
                            </label>
                        </div>
                    @endforeach
                </div>

                @if ($errors->has('chosenShipping'))
                    <p class="p-4 text-[10px] font-bold text-red-600 uppercase tracking-widest">
                        {{ $errors->first('chosenShipping') }}
                    </p>
                @endif
            @elseif($currentStep > $step && $this->shippingOption)
                <dl class="flex flex-wrap max-w-xs text-[10px] font-black uppercase tracking-widest">
                    <dt class="w-1/2 text-gray-400">
                        {{ $this->shippingOption->getDescription() }}
                    </dt>

                    <dd class="w-1/2 text-right text-[#71C229]">
                        {{ $this->shippingOption->getPrice()->formatted() }}
                    </dd>
                </dl>
            @endif

            @if ($step == $currentStep)
                <div class="mt-10 text-right">
                    <button class="px-10 py-4 text-[10px] font-black uppercase tracking-widest text-white bg-black rounded-xl hover:bg-[#71C229] transition-all duration-300 shadow-lg shadow-black/10 flex items-center gap-2 ml-auto group"
                            type="submit"
                            wire:key="shipping_submit_btn">
                        <span wire:loading.remove.delay
                              wire:target="saveShippingOption"
                              class="flex items-center gap-2">
                            Seleccionar Envío
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </span>
                        <span wire:loading.delay
                              wire:target="saveShippingOption">
                            <span class="inline-flex items-center gap-2">
                                Procesando...
                                <x-icon.loading class="w-4 h-4" />
                            </span>
                        </span>
                    </button>
                </div>
            @endif
        </div>
    @endif
</form>
