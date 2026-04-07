<form wire:submit="saveAddress('{{ $type }}')"
      class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
    <div class="flex items-center justify-between h-16 px-8 border-b border-gray-50 bg-gray-50/50">
        <h3 class="text-xs font-black uppercase tracking-widest text-gray-900 flex items-center gap-2">
            Detalles de {{ $type == 'shipping' ? 'Envío' : 'Facturación' }}
            <span class="h-1 w-1 rounded-full bg-[#71C229]"></span>
        </h3>

        @if ($type == 'shipping' && $step == $currentStep)
            <label class="flex items-center gap-2 px-3 py-1.5 rounded-xl cursor-pointer hover:bg-white transition-colors border border-transparent hover:border-gray-100">
                <input class="w-4 h-4 text-[#71C229] border-gray-200 rounded focus:ring-[#71C229]"
                       type="checkbox"
                       value="1"
                       wire:model.live="shippingIsBilling" />

                <span class="text-[10px] font-bold text-gray-600 uppercase tracking-tight">
                    Usar como facturación
                </span>
            </label>
        @endif

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
            @if ($step == $currentStep)
                <div class="grid grid-cols-6 gap-6">
                    <x-input.group class="col-span-3"
                                   label="Nombre"
                                   :errors="$errors->get($type . '.first_name')"
                                   required>
                        <x-input.text wire:model.live="{{ $type }}.first_name"
                                      class="rounded-xl border-gray-100 focus:ring-[#71C229] focus:border-[#71C229] text-xs font-bold"
                                      required />
                    </x-input.group>

                    <x-input.group class="col-span-3"
                                   label="Apellido"
                                   :errors="$errors->get($type . '.last_name')"
                                   required>
                        <x-input.text wire:model.live="{{ $type }}.last_name"
                                      class="rounded-xl border-gray-100 focus:ring-[#71C229] focus:border-[#71C229] text-xs font-bold"
                                      required />
                    </x-input.group>

                    <x-input.group class="col-span-6"
                                   label="Empresa (Opcional)"
                                   :errors="$errors->get($type . '.company_name')">
                        <x-input.text wire:model.live="{{ $type }}.company_name"
                                      class="rounded-xl border-gray-100 focus:ring-[#71C229] focus:border-[#71C229] text-xs font-bold" />
                    </x-input.group>

                    <x-input.group class="col-span-6 sm:col-span-3"
                                   label="Teléfono"
                                   :errors="$errors->get($type . '.contact_phone')">
                        <x-input.text wire:model.live="{{ $type }}.contact_phone"
                                      class="rounded-xl border-gray-100 focus:ring-[#71C229] focus:border-[#71C229] text-xs font-bold" />
                    </x-input.group>

                    <x-input.group class="col-span-6 sm:col-span-3"
                                   label="Email"
                                   :errors="$errors->get($type . '.contact_email')"
                                   required>
                        <x-input.text wire:model.live="{{ $type }}.contact_email"
                                      type="email"
                                      class="rounded-xl border-gray-100 focus:ring-[#71C229] focus:border-[#71C229] text-xs font-bold"
                                      required />
                    </x-input.group>

                    <div class="col-span-6">
                        <hr class="h-px my-4 bg-gray-50 border-none">
                    </div>

                    <x-input.group class="col-span-3 sm:col-span-2"
                                   label="Dirección"
                                   :errors="$errors->get($type . '.line_one')"
                                   required>
                        <x-input.text wire:model.live="{{ $type }}.line_one"
                                      class="rounded-xl border-gray-100 focus:ring-[#71C229] focus:border-[#71C229] text-xs font-bold"
                                      required />
                    </x-input.group>

                    <x-input.group class="col-span-3 sm:col-span-2"
                                   label="Piso/Depto"
                                   :errors="$errors->get($type . '.line_two')">
                        <x-input.text wire:model.live="{{ $type }}.line_two"
                                      class="rounded-xl border-gray-100 focus:ring-[#71C229] focus:border-[#71C229] text-xs font-bold" />
                    </x-input.group>

                    <x-input.group class="col-span-3 sm:col-span-2"
                                   label="Referencia"
                                   :errors="$errors->get($type . '.line_three')">
                        <x-input.text wire:model.live="{{ $type }}.line_three"
                                      class="rounded-xl border-gray-100 focus:ring-[#71C229] focus:border-[#71C229] text-xs font-bold" />
                    </x-input.group>

                    <x-input.group class="col-span-3 sm:col-span-2"
                                   label="Ciudad"
                                   :errors="$errors->get($type . '.city')"
                                   required>
                        <x-input.text wire:model.live="{{ $type }}.city"
                                      class="rounded-xl border-gray-100 focus:ring-[#71C229] focus:border-[#71C229] text-xs font-bold"
                                      required />
                    </x-input.group>

                    <x-input.group class="col-span-3 sm:col-span-2"
                                   label="Provincia"
                                   :errors="$errors->get($type . '.state')">
                        <x-input.text wire:model.live="{{ $type }}.state"
                                      class="rounded-xl border-gray-100 focus:ring-[#71C229] focus:border-[#71C229] text-xs font-bold" />
                    </x-input.group>

                    <x-input.group class="col-span-3 sm:col-span-2"
                                   label="Cód. Postal"
                                   :errors="$errors->get($type . '.postcode')"
                                   required>
                        <x-input.text wire:model.live="{{ $type }}.postcode"
                                      class="rounded-xl border-gray-100 focus:ring-[#71C229] focus:border-[#71C229] text-xs font-bold"
                                      required />
                    </x-input.group>

                    <x-input.group class="col-span-6"
                                   label="País"
                                   required>
                        <select class="w-full px-4 py-3 border border-gray-100 rounded-xl text-xs font-bold focus:ring-[#71C229] focus:border-[#71C229] appearance-none bg-gray-50"
                                wire:model.live="{{ $type }}.country_id">
                            <option value>Seleccionar país</option>
                            @foreach ($this->countries as $country)
                                <option value="{{ $country->id }}"
                                        wire:key="country_{{ $country->id }}">
                                    {{ $country->native }}
                                </option>
                            @endforeach
                        </select>
                    </x-input.group>
                </div>
            @elseif($currentStep > $step)
                <dl class="grid grid-cols-1 gap-8 text-[10px] uppercase tracking-widest font-bold sm:grid-cols-2">
                    <div>
                        <div class="space-y-4">
                            <div>
                                <dt class="text-gray-400">
                                    Nombre Completo
                                </dt>

                                <dd class="mt-1 text-gray-900 font-black">
                                    {{ $this->{$type}->first_name }} {{ $this->{$type}->last_name }}
                                </dd>
                            </div>

                            @if ($this->{$type}->company_name)
                                <div>
                                    <dt class="text-gray-400">
                                        Empresa
                                    </dt>

                                    <dd class="mt-1 text-gray-900 font-black">
                                        {{ $this->{$type}->company_name }}
                                    </dd>
                                </div>
                            @endif

                            @if ($this->{$type}->contact_phone)
                                <div>
                                    <dt class="text-gray-400">
                                        Teléfono
                                    </dt>

                                    <dd class="mt-1 text-gray-900 font-black">
                                        {{ $this->{$type}->contact_phone }}
                                    </dd>
                                </div>
                            @endif

                            <div>
                                <dt class="text-gray-400">
                                    Email
                                </dt>

                                <dd class="mt-1 text-gray-900 font-black lowercase tracking-normal">
                                    {{ $this->{$type}->contact_email }}
                                </dd>
                            </div>
                        </div>
                    </div>

                    <div>
                        <dt class="text-gray-400">
                            Dirección de Entrega
                        </dt>

                        <dd class="mt-1 text-gray-900 font-black">
                            {{ $this->{$type}->line_one }}<br>
                            @if ($this->{$type}->line_two)
                                {{ $this->{$type}->line_two }}<br>
                            @endif
                            @if ($this->{$type}->line_three)
                                {{ $this->{$type}->line_three }}<br>
                            @endif
                            @if ($this->{$type}->city)
                                {{ $this->{$type}->city }}<br>
                            @endif
                            @if ($this->{$type}->state)
                                {{ $this->{$type}->state }}<br>
                            @endif
                            CP: {{ $this->{$type}->postcode }}<br>
                            {{ $this->{$type}->country?->native }}
                        </dd>
                    </div>
                </dl>
            @endif

            @if ($step == $currentStep)
                <div class="mt-10 text-right">
                    <button class="px-10 py-4 text-[10px] font-black uppercase tracking-widest text-white bg-black rounded-xl hover:bg-[#71C229] transition-all duration-300 shadow-lg shadow-black/10 flex items-center gap-2 ml-auto group"
                            type="submit"
                            wire:key="submit_btn"
                            wire:loading.attr="disabled"
                            wire:target="saveAddress">
                        <span wire:loading.remove
                              wire:target="saveAddress"
                              class="flex items-center gap-2">
                            Guardar Dirección
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </span>

                        <span wire:loading
                              wire:target="saveAddress">
                            <span class="inline-flex items-center gap-2">
                                Guardando...
                                <x-icon.loading class="w-4 h-4" />
                            </span>
                        </span>
                    </button>
                </div>
            @endif
        </div>

    @endif
</form>
