<section class="bg-gray-50 py-12 lg:py-24">
    <div class="max-w-screen-xl px-4 mx-auto sm:px-6 lg:px-8">
        <div class="mb-12">
            <h1 class="text-4xl font-black text-gray-900 uppercase tracking-tighter italic">
                Checkout
                <span class="block text-[10px] font-black text-[#71C229] uppercase tracking-[0.3em] mt-2 italic not-italic">
                    Finalizá tu compra de forma segura
                </span>
            </h1>
        </div>

        <div class="grid grid-cols-1 gap-12 lg:grid-cols-3 lg:items-start">
            <div class="px-8 py-10 space-y-6 bg-white border border-gray-100 lg:sticky lg:top-32 rounded-2xl shadow-sm lg:order-last">
                <h3 class="text-xs font-black uppercase tracking-widest text-gray-900 flex items-center gap-2">
                    Resumen del Pedido
                    <span class="h-1 w-1 rounded-full bg-[#71C229]"></span>
                </h3>

                <div class="flow-root">
                    <div class="-my-6 divide-y divide-gray-50">
                        @foreach ($cart->lines as $line)
                            <div class="flex items-center py-6"
                                 wire:key="cart_line_{{ $line->id }}">
                                <img class="object-cover w-16 h-16 rounded-xl shadow-sm"
                                     src="{{ $line->purchasable->getThumbnail()->getUrl() }}" />

                                <div class="flex-1 ml-4">
                                    <p class="text-xs font-bold text-gray-900 leading-tight max-w-[25ch]">
                                        {{ $line->purchasable->getDescription() }}
                                    </p>

                                    <div class="flex items-center justify-between mt-2">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                            Cant. {{ $line->quantity }}
                                        </span>
                                        <span class="text-[10px] font-black text-[#71C229]">
                                            {{ $line->subTotal->formatted() }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="flow-root pt-6 mt-6 border-t border-gray-100">
                    <dl class="-my-3 text-[10px] uppercase tracking-widest font-bold divide-y divide-gray-50">
                        <div class="flex flex-wrap py-3 items-center justify-between">
                            <dt class="text-gray-400">Sub Total</dt>
                            <dd class="text-gray-900 font-black">
                                {{ $cart->subTotal->formatted() }}
                            </dd>
                        </div>

                        @if ($this->shippingOption)
                            <div class="flex flex-wrap py-3 items-center justify-between">
                                <dt class="text-gray-400">
                                    Envío ({{ $this->shippingOption->getDescription() }})
                                </dt>
                                <dd class="text-gray-900 font-black">
                                    {{ $this->shippingOption->getPrice()->formatted() }}
                                </dd>
                            </div>
                        @endif

                        @foreach ($cart->taxBreakdown->amounts as $tax)
                            <div class="flex flex-wrap py-3 items-center justify-between">
                                <dt class="text-gray-400">
                                    {{ $tax->description }}
                                </dt>
                                <dd class="text-gray-900 font-black">
                                    {{ $tax->price->formatted() }}
                                </dd>
                            </div>
                        @endforeach

                        <div class="flex flex-wrap pt-6 mt-3 items-center justify-between border-t-2 border-gray-900">
                            <dt class="text-sm font-black text-gray-900">TOTAL</dt>
                            <dd class="text-lg font-black text-[#71C229]">
                                {{ $cart->total->formatted() }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="space-y-8 lg:col-span-2">
                @include('partials.checkout.address', [
                    'type' => 'shipping',
                    'step' => $steps['shipping_address'],
                ])

                @include('partials.checkout.shipping_option', [
                    'step' => $steps['shipping_option'],
                ])

                @include('partials.checkout.address', [
                    'type' => 'billing',
                    'step' => $steps['billing_address'],
                ])

                @include('partials.checkout.payment', [
                    'step' => $steps['payment'],
                ])
            </div>
        </div>
    </div>
</section>