<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'

defineOptions({ layout: StorefrontLayout })

const props = defineProps({
  cart: { type: Object, required: true },
  shippingOptions: { type: Array, default: () => [] },
  savedAddress: { type: Object, default: null },
  countries: { type: Array, default: () => [] },
})

// ─── Steps: 1 = address, 2 = shipping, 3 = order summary ────────────────────
const currentStep = ref(props.savedAddress ? 2 : 1)

// ─── Address form ─────────────────────────────────────────────────────────────
const address = ref({
  first_name: props.savedAddress?.first_name ?? '',
  last_name: props.savedAddress?.last_name ?? '',
  company_name: props.savedAddress?.company_name ?? '',
  line_one: props.savedAddress?.line_one ?? '',
  line_two: props.savedAddress?.line_two ?? '',
  line_three: props.savedAddress?.line_three ?? '',
  city: props.savedAddress?.city ?? '',
  state: props.savedAddress?.state ?? '',
  postcode: props.savedAddress?.postcode ?? '',
  country_id: props.savedAddress?.country_id ?? '',
  contact_email: props.savedAddress?.contact_email ?? '',
  contact_phone: props.savedAddress?.contact_phone ?? '',
  delivery_instructions: props.savedAddress?.delivery_instructions ?? '',
  shipping_is_billing: true,
})

const addressErrors = ref({})
const addressLoading = ref(false)

// ─── Shipping selection ───────────────────────────────────────────────────────
const selectedShipping = ref(props.shippingOptions[0]?.identifier ?? null)
const shippingErrors = ref({})
const shippingLoading = ref(false)

// ─── Place order ──────────────────────────────────────────────────────────────
const placeLoading = ref(false)
const placeError = ref(null)

// ─── Cart totals (may update after shipping chosen) ───────────────────────────
const cartTotal = ref(props.cart.total)
const cartSubTotal = ref(props.cart.sub_total)

const selectedShippingOption = computed(() =>
  props.shippingOptions.find(o => o.identifier === selectedShipping.value),
)

// ─── CSRF helper ──────────────────────────────────────────────────────────────
function getCsrf() {
  return decodeURIComponent(
    document.cookie.split('; ').find(r => r.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '',
  )
}

async function jsonPost(url, data) {
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-XSRF-TOKEN': getCsrf(),
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify(data),
    credentials: 'same-origin',
  })
  const json = await response.json().catch(() => ({}))
  if (!response.ok) {
    throw { status: response.status, data: json }
  }
  return json
}

// ─── Step 1: save address ─────────────────────────────────────────────────────
async function submitAddress() {
  addressErrors.value = {}
  addressLoading.value = true
  try {
    await jsonPost('/checkout/address', address.value)
    currentStep.value = 2
  } catch (err) {
    if (err.status === 422 && err.data?.errors) {
      addressErrors.value = err.data.errors
    } else {
      addressErrors.value = { _general: ['Error al guardar la dirección. Intentá de nuevo.'] }
    }
  } finally {
    addressLoading.value = false
  }
}

// ─── Step 2: save shipping option ────────────────────────────────────────────
async function submitShipping() {
  shippingErrors.value = {}
  shippingLoading.value = true
  try {
    await jsonPost('/checkout/shipping', { identifier: selectedShipping.value })
    currentStep.value = 3
  } catch (err) {
    if (err.status === 422 && err.data?.errors) {
      shippingErrors.value = err.data.errors
    } else {
      shippingErrors.value = { _general: ['Error al seleccionar el envío. Intentá de nuevo.'] }
    }
  } finally {
    shippingLoading.value = false
  }
}

// ─── Step 3: place order ──────────────────────────────────────────────────────
async function placeOrder() {
  placeError.value = null
  placeLoading.value = true
  try {
    const result = await jsonPost('/checkout/place', {})
    router.visit(`/checkout/success?order=${encodeURIComponent(result.reference)}`)
  } catch (err) {
    placeError.value = err.data?.message ?? 'Error al procesar el pedido. Intentá de nuevo.'
  } finally {
    placeLoading.value = false
  }
}
</script>

<template>
  <section class="bg-gray-50 py-12 lg:py-24">
    <div class="max-w-screen-xl px-4 mx-auto sm:px-6 lg:px-8">

      <!-- Page title -->
      <div class="mb-12">
        <h1 class="text-4xl font-black text-gray-900 uppercase tracking-tighter italic">
          Checkout
          <span class="block text-[10px] font-black text-[#71C229] uppercase tracking-[0.3em] mt-2 not-italic">
            Finalizá tu compra de forma segura
          </span>
        </h1>
      </div>

      <div class="grid grid-cols-1 gap-12 lg:grid-cols-3 lg:items-start">

        <!-- Order summary sidebar -->
        <div class="px-8 py-10 space-y-6 bg-white border border-gray-100 lg:sticky lg:top-32 rounded-2xl shadow-sm lg:order-last">
          <h3 class="text-xs font-black uppercase tracking-widest text-gray-900 flex items-center gap-2">
            Resumen del Pedido
            <span class="h-1 w-1 rounded-full bg-[#71C229]" />
          </h3>

          <div class="flow-root">
            <div class="-my-6 divide-y divide-gray-50">
              <div
                v-for="line in cart.lines"
                :key="line.id"
                class="flex items-center py-6"
              >
                <img
                  v-if="line.thumbnail"
                  :src="line.thumbnail"
                  class="object-cover w-16 h-16 rounded-xl shadow-sm"
                  alt=""
                >
                <div
                  v-else
                  class="w-16 h-16 rounded-xl bg-gray-100 shadow-sm"
                />

                <div class="flex-1 ml-4">
                  <p class="text-xs font-bold text-gray-900 leading-tight max-w-[25ch]">
                    {{ line.description }}
                  </p>
                  <div class="flex items-center justify-between mt-2">
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                      Cant. {{ line.quantity }}
                    </span>
                    <span class="text-[10px] font-black text-[#71C229]">
                      {{ line.sub_total }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="flow-root pt-6 mt-6 border-t border-gray-100">
            <dl class="-my-3 text-[10px] uppercase tracking-widest font-bold divide-y divide-gray-50">
              <div class="flex flex-wrap py-3 items-center justify-between">
                <dt class="text-gray-400">Sub Total</dt>
                <dd class="text-gray-900 font-black">{{ cart.sub_total }}</dd>
              </div>

              <div
                v-if="selectedShippingOption && currentStep >= 3"
                class="flex flex-wrap py-3 items-center justify-between"
              >
                <dt class="text-gray-400">Envío ({{ selectedShippingOption.name }})</dt>
                <dd class="text-gray-900 font-black">{{ selectedShippingOption.price }}</dd>
              </div>

              <div
                v-for="(tax, i) in cart.tax_breakdown"
                :key="i"
                class="flex flex-wrap py-3 items-center justify-between"
              >
                <dt class="text-gray-400">{{ tax.description }}</dt>
                <dd class="text-gray-900 font-black">{{ tax.price }}</dd>
              </div>

              <div class="flex flex-wrap pt-6 mt-3 items-center justify-between border-t-2 border-gray-900">
                <dt class="text-sm font-black text-gray-900">TOTAL</dt>
                <dd class="text-lg font-black text-[#71C229]">{{ cart.total }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Steps -->
        <div class="space-y-8 lg:col-span-2">

          <!-- Step indicator -->
          <div class="flex items-center gap-3 mb-4">
            <div
              v-for="(label, idx) in ['Dirección', 'Envío', 'Confirmar']"
              :key="idx"
              class="flex items-center gap-2"
            >
              <span
                :class="[
                  'w-6 h-6 rounded-full text-[9px] font-black flex items-center justify-center transition-colors',
                  currentStep === idx + 1
                    ? 'bg-black text-white'
                    : currentStep > idx + 1
                      ? 'bg-[#71C229] text-white'
                      : 'bg-gray-100 text-gray-400',
                ]"
              >{{ idx + 1 }}</span>
              <span
                :class="[
                  'text-[9px] font-black uppercase tracking-widest transition-colors',
                  currentStep === idx + 1 ? 'text-gray-900' : 'text-gray-400',
                ]"
              >{{ label }}</span>
              <span v-if="idx < 2" class="h-px w-6 bg-gray-200" />
            </div>
          </div>

          <!-- ─── Step 1: Address ────────────────────────────────────────── -->
          <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="flex items-center justify-between h-16 px-8 border-b border-gray-50 bg-gray-50/50">
              <h3 class="text-xs font-black uppercase tracking-widest text-gray-900 flex items-center gap-2">
                Detalles de Envío
                <span class="h-1 w-1 rounded-full bg-[#71C229]" />
              </h3>
              <button
                v-if="currentStep > 1"
                type="button"
                class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-black transition-colors flex items-center gap-1 group"
                @click="currentStep = 1"
              >
                Editar
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
              </button>
            </div>

            <!-- Address form (step 1 active) -->
            <div v-if="currentStep === 1" class="p-8">
              <div
                v-if="addressErrors._general"
                class="mb-6 p-4 bg-red-50 border border-red-100 rounded-xl text-[10px] font-bold text-red-600 uppercase tracking-widest"
              >
                {{ addressErrors._general[0] }}
              </div>

              <form class="grid grid-cols-6 gap-6" @submit.prevent="submitAddress">
                <div class="col-span-3">
                  <label class="block text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">Nombre *</label>
                  <input
                    v-model="address.first_name"
                    type="text"
                    required
                    class="w-full rounded-xl border border-gray-100 px-4 py-3 text-xs font-bold focus:ring-2 focus:ring-[#71C229] focus:border-[#71C229] outline-none"
                    :class="{ 'border-red-400': addressErrors.first_name }"
                  >
                  <p v-if="addressErrors.first_name" class="mt-1 text-[9px] text-red-500 font-bold">{{ addressErrors.first_name[0] }}</p>
                </div>

                <div class="col-span-3">
                  <label class="block text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">Apellido *</label>
                  <input
                    v-model="address.last_name"
                    type="text"
                    required
                    class="w-full rounded-xl border border-gray-100 px-4 py-3 text-xs font-bold focus:ring-2 focus:ring-[#71C229] focus:border-[#71C229] outline-none"
                    :class="{ 'border-red-400': addressErrors.last_name }"
                  >
                  <p v-if="addressErrors.last_name" class="mt-1 text-[9px] text-red-500 font-bold">{{ addressErrors.last_name[0] }}</p>
                </div>

                <div class="col-span-6">
                  <label class="block text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">Empresa (Opcional)</label>
                  <input
                    v-model="address.company_name"
                    type="text"
                    class="w-full rounded-xl border border-gray-100 px-4 py-3 text-xs font-bold focus:ring-2 focus:ring-[#71C229] focus:border-[#71C229] outline-none"
                  >
                </div>

                <div class="col-span-6 sm:col-span-3">
                  <label class="block text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">Teléfono</label>
                  <input
                    v-model="address.contact_phone"
                    type="tel"
                    class="w-full rounded-xl border border-gray-100 px-4 py-3 text-xs font-bold focus:ring-2 focus:ring-[#71C229] focus:border-[#71C229] outline-none"
                  >
                </div>

                <div class="col-span-6 sm:col-span-3">
                  <label class="block text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">Email *</label>
                  <input
                    v-model="address.contact_email"
                    type="email"
                    required
                    class="w-full rounded-xl border border-gray-100 px-4 py-3 text-xs font-bold focus:ring-2 focus:ring-[#71C229] focus:border-[#71C229] outline-none"
                    :class="{ 'border-red-400': addressErrors.contact_email }"
                  >
                  <p v-if="addressErrors.contact_email" class="mt-1 text-[9px] text-red-500 font-bold">{{ addressErrors.contact_email[0] }}</p>
                </div>

                <div class="col-span-6">
                  <hr class="h-px my-2 bg-gray-50 border-none">
                </div>

                <div class="col-span-3 sm:col-span-2">
                  <label class="block text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">Dirección *</label>
                  <input
                    v-model="address.line_one"
                    type="text"
                    required
                    class="w-full rounded-xl border border-gray-100 px-4 py-3 text-xs font-bold focus:ring-2 focus:ring-[#71C229] focus:border-[#71C229] outline-none"
                    :class="{ 'border-red-400': addressErrors.line_one }"
                  >
                  <p v-if="addressErrors.line_one" class="mt-1 text-[9px] text-red-500 font-bold">{{ addressErrors.line_one[0] }}</p>
                </div>

                <div class="col-span-3 sm:col-span-2">
                  <label class="block text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">Piso/Depto</label>
                  <input
                    v-model="address.line_two"
                    type="text"
                    class="w-full rounded-xl border border-gray-100 px-4 py-3 text-xs font-bold focus:ring-2 focus:ring-[#71C229] focus:border-[#71C229] outline-none"
                  >
                </div>

                <div class="col-span-3 sm:col-span-2">
                  <label class="block text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">Referencia</label>
                  <input
                    v-model="address.line_three"
                    type="text"
                    class="w-full rounded-xl border border-gray-100 px-4 py-3 text-xs font-bold focus:ring-2 focus:ring-[#71C229] focus:border-[#71C229] outline-none"
                  >
                </div>

                <div class="col-span-3 sm:col-span-2">
                  <label class="block text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">Ciudad *</label>
                  <input
                    v-model="address.city"
                    type="text"
                    required
                    class="w-full rounded-xl border border-gray-100 px-4 py-3 text-xs font-bold focus:ring-2 focus:ring-[#71C229] focus:border-[#71C229] outline-none"
                    :class="{ 'border-red-400': addressErrors.city }"
                  >
                  <p v-if="addressErrors.city" class="mt-1 text-[9px] text-red-500 font-bold">{{ addressErrors.city[0] }}</p>
                </div>

                <div class="col-span-3 sm:col-span-2">
                  <label class="block text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">Provincia</label>
                  <input
                    v-model="address.state"
                    type="text"
                    class="w-full rounded-xl border border-gray-100 px-4 py-3 text-xs font-bold focus:ring-2 focus:ring-[#71C229] focus:border-[#71C229] outline-none"
                  >
                </div>

                <div class="col-span-3 sm:col-span-2">
                  <label class="block text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">Cód. Postal *</label>
                  <input
                    v-model="address.postcode"
                    type="text"
                    required
                    class="w-full rounded-xl border border-gray-100 px-4 py-3 text-xs font-bold focus:ring-2 focus:ring-[#71C229] focus:border-[#71C229] outline-none"
                    :class="{ 'border-red-400': addressErrors.postcode }"
                  >
                  <p v-if="addressErrors.postcode" class="mt-1 text-[9px] text-red-500 font-bold">{{ addressErrors.postcode[0] }}</p>
                </div>

                <div class="col-span-6">
                  <label class="block text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">País *</label>
                  <select
                    v-model="address.country_id"
                    required
                    class="w-full px-4 py-3 border border-gray-100 rounded-xl text-xs font-bold focus:ring-2 focus:ring-[#71C229] focus:border-[#71C229] appearance-none bg-gray-50 outline-none"
                    :class="{ 'border-red-400': addressErrors.country_id }"
                  >
                    <option value="">Seleccionar país</option>
                    <option
                      v-for="country in countries"
                      :key="country.id"
                      :value="country.id"
                    >
                      {{ country.name }}
                    </option>
                  </select>
                  <p v-if="addressErrors.country_id" class="mt-1 text-[9px] text-red-500 font-bold">{{ addressErrors.country_id[0] }}</p>
                </div>

                <div class="col-span-6">
                  <label class="flex items-center gap-2 cursor-pointer w-fit">
                    <input
                      v-model="address.shipping_is_billing"
                      type="checkbox"
                      class="w-4 h-4 text-[#71C229] border-gray-200 rounded focus:ring-[#71C229]"
                    >
                    <span class="text-[10px] font-bold text-gray-600 uppercase tracking-tight">
                      Usar como dirección de facturación
                    </span>
                  </label>
                </div>

                <div class="col-span-6 text-right">
                  <button
                    type="submit"
                    :disabled="addressLoading"
                    class="px-10 py-4 text-[10px] font-black uppercase tracking-widest text-white bg-black rounded-xl hover:bg-[#71C229] transition-all duration-300 shadow-lg shadow-black/10 flex items-center gap-2 ml-auto group disabled:opacity-60"
                  >
                    {{ addressLoading ? 'Guardando...' : 'Guardar Dirección' }}
                    <svg v-if="!addressLoading" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                  </button>
                </div>
              </form>
            </div>

            <!-- Address summary (steps 2+) -->
            <div v-else-if="currentStep > 1" class="p-8">
              <dl class="grid grid-cols-1 gap-8 text-[10px] uppercase tracking-widest font-bold sm:grid-cols-2">
                <div class="space-y-4">
                  <div>
                    <dt class="text-gray-400">Nombre Completo</dt>
                    <dd class="mt-1 text-gray-900 font-black">{{ address.first_name }} {{ address.last_name }}</dd>
                  </div>
                  <div v-if="address.contact_email">
                    <dt class="text-gray-400">Email</dt>
                    <dd class="mt-1 text-gray-900 font-black lowercase tracking-normal">{{ address.contact_email }}</dd>
                  </div>
                  <div v-if="address.contact_phone">
                    <dt class="text-gray-400">Teléfono</dt>
                    <dd class="mt-1 text-gray-900 font-black">{{ address.contact_phone }}</dd>
                  </div>
                </div>
                <div>
                  <dt class="text-gray-400">Dirección de Entrega</dt>
                  <dd class="mt-1 text-gray-900 font-black">
                    {{ address.line_one }}<br>
                    <template v-if="address.line_two">{{ address.line_two }}<br></template>
                    <template v-if="address.city">{{ address.city }}<br></template>
                    <template v-if="address.state">{{ address.state }}<br></template>
                    CP: {{ address.postcode }}
                  </dd>
                </div>
              </dl>
            </div>
          </div>

          <!-- ─── Step 2: Shipping ───────────────────────────────────────── -->
          <div v-if="currentStep >= 2" class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="flex items-center justify-between h-16 px-8 border-b border-gray-50 bg-gray-50/50">
              <h3 class="text-xs font-black uppercase tracking-widest text-gray-900 flex items-center gap-2">
                Opciones de Envío
                <span class="h-1 w-1 rounded-full bg-[#71C229]" />
              </h3>
              <button
                v-if="currentStep > 2"
                type="button"
                class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-black transition-colors flex items-center gap-1 group"
                @click="currentStep = 2"
              >
                Editar
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
              </button>
            </div>

            <!-- Shipping options form (step 2 active) -->
            <div v-if="currentStep === 2" class="p-8">
              <div
                v-if="shippingErrors._general"
                class="mb-6 p-4 bg-red-50 border border-red-100 rounded-xl text-[10px] font-bold text-red-600 uppercase tracking-widest"
              >
                {{ shippingErrors._general[0] }}
              </div>

              <div
                v-if="shippingOptions.length === 0"
                class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"
              >
                No hay opciones de envío disponibles para esta dirección.
              </div>

              <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div v-for="option in shippingOptions" :key="option.identifier">
                  <input
                    :id="option.identifier"
                    v-model="selectedShipping"
                    class="hidden peer"
                    type="radio"
                    :value="option.identifier"
                    name="shippingOption"
                  >
                  <label
                    :for="option.identifier"
                    class="flex items-center justify-between p-5 text-[10px] font-black uppercase tracking-widest border border-gray-100 rounded-xl shadow-sm cursor-pointer peer-checked:border-[#71C229] hover:bg-gray-50 peer-checked:ring-2 peer-checked:ring-[#71C229]/20 transition-all duration-300"
                  >
                    <p class="text-gray-900">{{ option.name }}</p>
                    <p class="text-[#71C229]">{{ option.price }}</p>
                  </label>
                </div>
              </div>

              <p v-if="shippingErrors.identifier" class="mt-4 text-[10px] font-bold text-red-600 uppercase tracking-widest">
                {{ shippingErrors.identifier[0] }}
              </p>

              <div class="mt-10 text-right">
                <button
                  type="button"
                  :disabled="shippingLoading || !selectedShipping"
                  class="px-10 py-4 text-[10px] font-black uppercase tracking-widest text-white bg-black rounded-xl hover:bg-[#71C229] transition-all duration-300 shadow-lg shadow-black/10 flex items-center gap-2 ml-auto group disabled:opacity-60"
                  @click="submitShipping"
                >
                  {{ shippingLoading ? 'Procesando...' : 'Seleccionar Envío' }}
                  <svg v-if="!shippingLoading" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Shipping summary (step 3+) -->
            <div v-else-if="currentStep > 2 && selectedShippingOption" class="p-8">
              <dl class="flex flex-wrap max-w-xs text-[10px] font-black uppercase tracking-widest">
                <dt class="w-1/2 text-gray-400">{{ selectedShippingOption.name }}</dt>
                <dd class="w-1/2 text-right text-[#71C229]">{{ selectedShippingOption.price }}</dd>
              </dl>
            </div>
          </div>

          <!-- ─── Step 3: Confirm order ──────────────────────────────────── -->
          <div v-if="currentStep >= 3" class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="h-16 px-8 border-b border-gray-50 bg-gray-50/50 flex items-center">
              <h3 class="text-xs font-black uppercase tracking-widest text-gray-900 flex items-center gap-2">
                Confirmar Pedido
                <span class="h-1 w-1 rounded-full bg-[#71C229]" />
              </h3>
            </div>

            <div class="p-8">
              <p class="text-xs font-bold text-gray-600 mb-6 leading-relaxed">
                Revisá tu pedido antes de confirmar. Nos pondremos en contacto para coordinar la entrega.
              </p>

              <div
                v-if="placeError"
                class="mb-6 p-4 bg-red-50 border border-red-100 rounded-xl text-[10px] font-bold text-red-600 uppercase tracking-widest"
              >
                {{ placeError }}
              </div>

              <button
                type="button"
                :disabled="placeLoading"
                class="w-full px-10 py-4 text-[10px] font-black uppercase tracking-widest text-white bg-black rounded-xl hover:bg-[#71C229] transition-all duration-300 shadow-lg shadow-black/10 flex items-center justify-center gap-2 group disabled:opacity-60"
                @click="placeOrder"
              >
                {{ placeLoading ? 'Procesando pedido...' : 'Confirmar Pedido' }}
                <svg v-if="!placeLoading" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
              </button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>
</template>
