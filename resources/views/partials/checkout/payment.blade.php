<div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
    <div class="flex items-center h-16 px-8 border-b border-gray-50 bg-gray-50/50">
        <h3 class="text-xs font-black uppercase tracking-widest text-gray-900 flex items-center gap-2">
            Método de Pago
            <span class="h-1 w-1 rounded-full bg-[#71C229]"></span>
        </h3>
    </div>

    @if ($currentStep >= $step)
        <div class="p-8 space-y-8">
            <div class="flex flex-wrap gap-4">
                <button @class([
                    'px-6 py-3 text-[10px] border transition-all duration-300 rounded-xl uppercase tracking-widest',
                    'font-black bg-[#71C229] border-[#71C229] text-white shadow-lg shadow-[#71C229]/20' => $paymentType === 'card',
                    'font-bold bg-gray-50 border-gray-100 text-gray-400 hover:bg-white hover:border-gray-200' => $paymentType !== 'card',
                ])
                        type="button"
                        wire:click.prevent="$set('paymentType', 'card')">
                    Tarjeta de Crédito/Débito
                </button>

                <button @class([
                    'px-6 py-3 text-[10px] border transition-all duration-300 rounded-xl uppercase tracking-widest',
                    'font-black bg-[#71C229] border-[#71C229] text-white shadow-lg shadow-[#71C229]/20' => $paymentType === 'cash-in-hand',
                    'font-bold bg-gray-50 border-gray-100 text-gray-400 hover:bg-white hover:border-gray-200' => $paymentType !== 'cash-in-hand',
                ])
                        type="button"
                        wire:click.prevent="$set('paymentType', 'cash-in-hand')">
                    Pago Offline / Efectivo
                </button>
            </div>

            @if ($paymentType == 'card')
                <div class="p-6 bg-gray-50 rounded-2xl border border-gray-100">
                    <livewire:stripe.payment :cart="$cart"
                                             :returnUrl="route('checkout.view')" />
                </div>
            @endif

            @if ($paymentType == 'cash-in-hand')
                <form wire:submit="checkout" class="space-y-6">
                    <div class="p-6 text-[10px] font-bold uppercase tracking-widest text-center text-gray-600 rounded-2xl bg-gray-50 border border-gray-100 flex flex-col items-center gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-[#71C229]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        El pago se coordinará de forma offline. No se requieren datos de tarjeta.
                    </div>

                    <button class="px-10 py-4 text-[10px] font-black uppercase tracking-widest text-white bg-black rounded-xl hover:bg-[#71C229] transition-all duration-300 shadow-lg shadow-black/10 flex items-center gap-2 ml-auto group"
                            type="submit"
                            wire:key="payment_submit_btn">
                        <span wire:loading.remove.delay
                              wire:target="checkout"
                              class="flex items-center gap-2">
                            Confirmar Pedido
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </span>
                        <span wire:loading.delay
                              wire:target="checkout">
                            <span class="inline-flex items-center gap-2">
                                Procesando...
                                <x-icon.loading class="w-4 h-4" />
                            </span>
                        </span>
                    </button>
                </form>
            @endif
        </div>
    @endif
</div>
