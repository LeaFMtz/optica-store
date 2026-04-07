<div class="sm:relative"
     x-data="{
         linesVisible: @entangle('linesVisible').live
     }">
    <button class="flex items-center gap-2 px-4 py-2 transition border border-gray-800 rounded-full hover:border-[#71C229] hover:text-[#71C229] group relative"
            x-on:click="linesVisible = !linesVisible">
        <span class="sr-only">Carrito</span>

        <svg xmlns="http://www.w3.org/2000/svg"
             class="w-5 h-5 transition-transform group-hover:scale-110"
             fill="none"
             viewBox="0 0 24 24"
             stroke="currentColor">
            <path stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>

        @if($this->cartLines->count() > 0)
            <span class="absolute -top-1 -right-1 flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#71C229] opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-[#71C229] text-[10px] font-black items-center justify-center text-white">
                    {{ $this->cartLines->sum('quantity') }}
                </span>
            </span>
        @endif
    </button>

    <div class="absolute inset-x-0 top-auto z-50 w-screen max-w-sm px-6 py-8 mx-auto mt-4 bg-white border border-gray-100 shadow-2xl sm:left-auto rounded-xl"
         x-show="linesVisible"
         x-on:click.away="linesVisible = false"
         x-transition
         x-cloak>
        <button class="absolute text-gray-400 transition-all top-4 right-4 hover:scale-110 hover:text-[#71C229]"
                type="button"
                aria-label="Cerrar"
                x-on:click="linesVisible = false">
            <svg xmlns="http://www.w3.org/2000/svg"
                 class="w-5 h-5"
                 fill="none"
                 viewBox="0 0 24 24"
                 stroke="currentColor">
                <path stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <div>
            <h3 class="text-xs font-black uppercase tracking-widest text-gray-900 mb-6 flex items-center gap-2">
                Tu Carrito
                <span class="h-1 w-1 rounded-full bg-[#71C229]"></span>
            </h3>

            @if ($this->cart)
                @if ($lines)
                    <div class="flow-root">
                        <ul class="-my-4 overflow-y-auto divide-y divide-gray-50 max-h-96 pr-2 custom-scrollbar">
                            @foreach ($lines as $index => $line)
                                <li>
                                    <div class="flex py-6"
                                         wire:key="line_{{ $line['id'] }}">
                                        @if($line['thumbnail'])
                                        <img class="object-cover w-16 h-16 rounded-lg shadow-sm"
                                             src="{{ $line['thumbnail'] }}">
                                        @endif

                                        <div class="flex-1 ml-4">
                                            <p class="max-w-[20ch] text-xs font-bold text-gray-900 leading-tight">
                                                {{ $line['description'] }}
                                            </p>

                                            <span class="block mt-1 text-[10px] font-bold text-gray-400 uppercase tracking-tighter">
                                                {{ $line['identifier'] }} / {{ $line['options'] }}
                                            </span>

                                            <div class="flex items-center mt-3">
                                                <div class="flex items-center bg-gray-50 rounded-lg border border-gray-100 px-2">
                                                    <input class="w-12 py-1 text-xs font-bold text-center bg-transparent border-none focus:ring-0 no-spinner"
                                                           type="number"
                                                           wire:model.live="lines.{{ $index }}.quantity" />
                                                </div>

                                                <p class="ml-3 text-[10px] font-black text-[#71C229]">
                                                    {{ $line['unit_price'] }}
                                                </p>

                                                <button class="p-2 ml-auto text-gray-300 transition-colors rounded-lg hover:bg-red-50 hover:text-red-500"
                                                        type="button"
                                                        wire:click="removeLine('{{ $line['id'] }}')">
                                                    <svg xmlns="http://www.w3.org/2000/svg"
                                                         class="w-4 h-4"
                                                         fill="none"
                                                         viewBox="0 0 24 24"
                                                         stroke="currentColor">
                                                        <path stroke-linecap="round"
                                                              stroke-linejoin="round"
                                                              stroke-width="2"
                                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    @if ($errors->get('lines.' . $index . '.quantity'))
                                        <div class="p-2 mb-4 text-[10px] font-bold text-center text-red-600 rounded-lg bg-red-50 border border-red-100 uppercase tracking-tighter"
                                             role="alert">
                                            @foreach ($errors->get('lines.' . $index . '.quantity') as $error)
                                                {{ $error }}
                                            @endforeach
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="py-12 text-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-gray-100 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                            Tu carrito está vacío
                        </p>
                    </div>
                @endif

                <div class="pt-6 mt-6 border-t border-gray-100">
                    <div class="flex items-center justify-between text-xs font-black uppercase tracking-widest">
                        <span class="text-gray-400">Sub Total</span>
                        <span class="text-gray-900">{{ $this->cart->subTotal->formatted() }}</span>
                    </div>
                </div>
            @else
                <div class="py-12 text-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-gray-100 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">
                        Tu carrito está vacío
                    </p>
                </div>
            @endif
        </div>

        @if ($this->cart && $lines)
            <div class="mt-8 space-y-3">
                <button class="block w-full px-8 py-3.5 text-[10px] font-bold uppercase tracking-[0.2em] text-gray-400 bg-white border border-gray-100 rounded-xl hover:text-black hover:border-black transition-all duration-300 shadow-sm"
                        type="button"
                        wire:click="updateLines">
                    Actualizar Carrito
                </button>

                <a class="block w-full px-8 py-4 text-[10px] font-bold uppercase tracking-[0.3em] text-center text-white bg-black rounded-xl hover:bg-[#71C229] hover:text-black transition-all duration-500 shadow-xl shadow-black/10 hover:shadow-[#71C229]/20 flex items-center justify-center gap-3 group active:scale-[0.98]"
                   href="{{ route('checkout.view') }}"
                   wire:navigate
                >
                    <span>Finalizar Compra</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </a>

                <a class="inline-block w-full text-[10px] font-bold text-gray-400 uppercase tracking-widest hover:text-[#71C229] transition-colors text-center mt-2"
                   href="{{ url('/') }}">
                    Continuar comprando
                </a>
            </div>
        @endif
    </div>
</div>
