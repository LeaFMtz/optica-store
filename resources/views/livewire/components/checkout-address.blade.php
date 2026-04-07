<form wire:submit="save"
      class="border rounded shadow-lg">
    <div class="flex justify-between p-4 font-medium border-b">
        <span class="text-xl">{{ ucfirst($type) }} Details</span>
        @if ($type == 'shipping' && $editing)
            <label class="text-sm">
                <input type="checkbox"
                       value="1"
                       wire:model.live="shippingIsBilling" />
                Same as billing
            </label>
        @endif
    </div>
    <div class="p-4 space-y-4">
        @if ($editing)
            <div class="grid grid-cols-2 gap-4">
                <x-input.group label="First name"
                               :errors="$errors->get('address.first_name')"
                               required>
                    <x-input.text wire:model.live="address.first_name"
                                  required />
                </x-input.group>

                <x-input.group label="Last name"
                               :errors="$errors->get('address.last_name')">
                    <x-input.text wire:model.live="address.last_name" />
                </x-input.group>
            </div>

            <div>
                <x-input.group label="Company name"
                               :errors="$errors->get('address.company_name')"
                               required>
                    <x-input.text wire:model.live="address.company_name"
                                  required />
                </x-input.group>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <x-input.group label="Contact phone"
                               :errors="$errors->get('address.contact_phone')">
                    <x-input.text wire:model.live="address.contact_phone" />
                </x-input.group>

                <x-input.group label="Contact email"
                               :errors="$errors->get('address.contact_email')">
                    <x-input.text wire:model.live="address.contact_email"
                                  type="email" />
                </x-input.group>
            </div>

            <hr />

            <div class="grid grid-cols-3 gap-4">
                <x-input.group label="Address line 1"
                               :errors="$errors->get('address.line_one')"
                               required>
                    <x-input.text wire:model.live="address.line_one"
                                  required />
                </x-input.group>

                <x-input.group label="Address line 2"
                               :errors="$errors->get('address.line_two')">
                    <x-input.text wire:model.live="address.line_two" />
                </x-input.group>

                <x-input.group label="Address line 3"
                               :errors="$errors->get('address.line_three')">
                    <x-input.text wire:model.live="address.line_three" />
                </x-input.group>
            </div>

            <div class="grid grid-cols-3 gap-4">
                <x-input.group label="City"
                               :errors="$errors->get('address.city')"
                               required>
                    <x-input.text wire:model.live="address.city"
                                  required />
                </x-input.group>

                <x-input.group label="State / Province"
                               :errors="$errors->get('address.state')">
                    <x-input.text wire:model.live="address.state" />
                </x-input.group>

                <x-input.group label="Postcode"
                               :errors="$errors->get('address.postcode')"
                               required>
                    <x-input.text wire:model.live="address.postcode"
                                  required />
                </x-input.group>
            </div>

            <div>
                <x-input.group label="Country"
                               required>
                    <select class="w-full p-4 text-sm border-2 border-gray-200 rounded-lg"
                            wire:model.live="address.country_id">
                        <option value>Select a country</option>
                        @foreach ($this->countries as $country)
                            <option value="{{ $country->id }}"
                                    wire:key="country_{{ $country->id }}">
                                {{ $country->native }}
                            </option>
                        @endforeach
                    </select>
                </x-input.group>
            </div>
        @else
            <dl class="flex">
                <div class="w-1/2">
                    <div class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium">Name</dt>
                            <dd>{{ $address->first_name }} {{ $address->last_name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium">Company</dt>
                            <dd>{{ $address->company_name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium">Phone Number</dt>
                            <dd>{{ $address->contact_phone }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium">Email</dt>
                            <dd>{{ $address->contact_email }}</dd>
                        </div>
                    </div>
                </div>

                <div class="w-1/2">
                    <dt class="text-sm font-medium">Address</dt>
                    <dd>
                        {{ $address->line_one }}<br>
                        @if ($address->line_two)
                            {{ $address->line_two }}<br>
                        @endif
                        @if ($address->line_three)
                            {{ $address->line_three }}<br>
                        @endif
                        @if ($address->city)
                            {{ $address->city }}<br>
                        @endif
                        {{ $address->state }}<br>
                        {{ $address->postcode }}<br>
                        {{ $address->country()->first()->native }}
                    </dd>
                </div>
            </dl>
        @endif
    </div>
    <div class="flex justify-end w-full p-8 bg-gray-50/50 border-t border-gray-50">
        <div>
            @if ($editing)
                <button type="submit"
                        wire:key="submit_btn"
                        class="px-10 py-4 text-[10px] font-black uppercase tracking-widest text-white bg-black rounded-xl hover:bg-[#71C229] transition-all duration-300 shadow-lg shadow-black/10 flex items-center gap-2 group">
                    Continuar
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            @else
                <button type="button"
                        wire:key="edit_btn"
                        wire:click.prevent="$set('editing', true)"
                        class="px-8 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400 border border-gray-100 rounded-xl hover:text-black hover:border-black transition-all">
                    Editar Detalles
                </button>
            @endif
        </div>
    </div>
</form>
