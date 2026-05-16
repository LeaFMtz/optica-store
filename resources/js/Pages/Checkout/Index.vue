<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'
import AppButton from '@/Components/AppButton.vue'
import AppInput from '@/Components/AppInput.vue'
import ShippingQuoter from '@/Components/ShippingQuoter.vue'

defineOptions({ layout: StorefrontLayout })

const props = defineProps({
  cart: { type: Object, required: true },
  shippingOptions: { type: Array, default: () => [] },
  savedAddress: { type: Object, default: null },
  countries: { type: Array, default: () => [] },
  hasDeliveryShipping: { type: Boolean, required: true },
  initialZipnovaOption: { type: Object, default: null },
  provincias: { type: Object, default: () => ({}) },
})

const page = usePage()

// ─── Steps: 1=contacto, 2=envío, 3=recibe, 4=pago ──────────────────────────
const stepLabels = ['Datos de Contacto', 'Tipo de Envío', 'Persona que Recibe', 'Pago']

const currentStep = ref(props.savedAddress ? 2 : 1)

// ─── Delivery type (inferred from selected shipping) ──────────────────────────
const deliveryType = computed(() => {
  if (selectedShipping.value?.startsWith('RETLOC')) return 'retiro_local'

  const znOpt = zipnovaOptions.value.find(o => o.identifier === selectedShipping.value)
  if (znOpt?.service_type_code === 'pickup_point') return 'pickup_point'

  return 'domicilio'
})

const selectedIsCollect = computed(() => {
  // retiro_local is always collect
  if (deliveryType.value === 'retiro_local') return true

  const staticOpt = props.shippingOptions.find(o => o.identifier === selectedShipping.value)
  if (staticOpt) return staticOpt.collect ?? true

  const znOpt = zipnovaOptions.value.find(o => o.identifier === selectedShipping.value)
  if (znOpt) return znOpt.service_type_code === 'pickup_point'

  return true
})

// Address fields shown only for domicilio delivery type in step 3
const showAddressFields = computed(() => deliveryType.value === 'domicilio')

// ─── Contact form (step 1) ───────────────────────────────────────────────────
const contact = ref({
  first_name: props.savedAddress?.first_name ?? '',
  last_name: props.savedAddress?.last_name ?? '',
  contact_email: props.savedAddress?.contact_email ?? '',
  contact_phone: props.savedAddress?.contact_phone ?? '',
})

const contactErrors = ref({})
const contactLoading = ref(false)

// ─── Shipping selection (step 2) ──────────────────────────────────────────────
const selectedShipping = ref(
  props.initialZipnovaOption?.identifier ?? props.shippingOptions[0]?.identifier ?? null,
)
const shippingErrors = ref({})
const shippingLoading = ref(false)

// ─── Zipnova dynamic options ──────────────────────────────────────────────────
const zipnovaOptions = ref(props.initialZipnovaOption ? [props.initialZipnovaOption] : [])
const selectedPointId = ref(null)

const showPickupSelector = computed(() =>
  selectedShipping.value?.startsWith('ZN_') &&
  selectedIsCollect.value &&
  (zipnovaOptions.value.find(o => o.identifier === selectedShipping.value)?.pickup_points?.length ?? 0) > 0
)

function onZipnovaSelected(option) {
  if (!zipnovaOptions.value.find(o => o.identifier === option.identifier)) {
    zipnovaOptions.value.push(option)
  }
  selectedShipping.value = option.identifier
  selectedPointId.value = null
}

function onZipnovaPostcodeChanged(pc) {
  receiver.value.postcode = pc
}

function onZipnovaOptionsCleared() {
  zipnovaOptions.value = []
  if (selectedShipping.value?.startsWith('ZN_')) {
    selectedShipping.value = props.shippingOptions[0]?.identifier ?? null
  }
}

// ─── Receiver form (step 3) ──────────────────────────────────────────────────
const receiver = ref({
  first_name: '',
  last_name: '',
  dni: '',
  line_one: props.savedAddress?.line_one || '',
  line_two: props.savedAddress?.line_two || '',
  city: props.savedAddress?.city || '',
  state: props.savedAddress?.state || '',
  postcode: props.savedAddress?.postcode || '',
  contact_phone: props.savedAddress?.contact_phone || '',
  delivery_instructions: props.savedAddress?.delivery_instructions || '',
})

const receiverErrors = ref({})
const receiverLoading = ref(false)

const provinciaOptions = computed(() =>
  Object.entries(props.provincias).map(([code, name]) => ({ value: code, label: name }))
)

// ─── Payment — Card Payment Brick ─────────────────────────────────────────────
const payLoading = ref(false)
const paymentError = ref(null)
const mpReady = ref(false)

let cardPaymentBrickController = null

// ─── Cart totals (may update after shipping chosen) ────────────────────────────
const cartTotal = ref(props.cart.total)
const cartSubTotal = ref(props.cart.sub_total)

function formatPrice(value) {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    maximumFractionDigits: 0,
  }).format(value / 100)
}

const selectedShippingOption = computed(() => {
  const staticOpt = props.shippingOptions.find(o => o.identifier === selectedShipping.value)
  if (staticOpt) return staticOpt

  const zipnovaOpt = zipnovaOptions.value.find(o => o.identifier === selectedShipping.value)
  if (!zipnovaOpt) return null

  return { ...zipnovaOpt, price: formatPrice(zipnovaOpt.price) }
})

const storeLogo = '/images/logo.webp'

// Argentina country ID for backend
const argentinaCountryId = computed(() => {
  const arg = props.countries.find(c => c.name === 'Argentina')
  return arg?.id ?? ''
})

// ─── CSRF helper ───────────────────────────────────────────────────────────────
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

// ─── Step 1: save contact info ─────────────────────────────────────────────────
async function submitContact() {
  contactErrors.value = {}
  contactLoading.value = true
  try {
    await jsonPost('/checkout/address', {
      first_name: contact.value.first_name,
      last_name: contact.value.last_name,
      contact_email: contact.value.contact_email,
      contact_phone: contact.value.contact_phone,
      save_type: 'contact',
    })
    currentStep.value = 2
  } catch (err) {
    if (err.status === 422 && err.data?.errors) {
      contactErrors.value = err.data.errors
    } else {
      contactErrors.value = { _general: ['Error al guardar los datos. Intentá de nuevo.'] }
    }
  } finally {
    contactLoading.value = false
  }
}

// ─── Step 2: save shipping option ──────────────────────────────────────────────
async function submitShipping() {
  shippingErrors.value = {}
  shippingLoading.value = true
  try {
    await jsonPost('/checkout/shipping', { identifier: selectedShipping.value, point_id: selectedPointId.value ?? null })

    // Pre-fill receiver name from contact data
    receiver.value.first_name = contact.value.first_name
    receiver.value.last_name = contact.value.last_name

    router.reload({
      only: ['cart'],
      onSuccess: () => { currentStep.value = 3 },
      onError: () => { currentStep.value = 3 },
    })
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

// ─── Step 3: save receiver ─────────────────────────────────────────────────────
async function submitReceiver() {
  receiverErrors.value = {}
  receiverLoading.value = true
  try {
    const payload = {
      save_type: 'receiver',
      delivery_type: deliveryType.value,
      first_name: receiver.value.first_name,
      last_name: receiver.value.last_name,
      dni: receiver.value.dni,
      contact_phone: receiver.value.contact_phone,
      delivery_instructions: receiver.value.delivery_instructions,
    }

    if (showAddressFields.value) {
      Object.assign(payload, {
        line_one: receiver.value.line_one,
        city: receiver.value.city,
        state: receiver.value.state,
        postcode: receiver.value.postcode,
      })
    }

    await jsonPost('/checkout/address', payload)
    currentStep.value = 4
  } catch (err) {
    if (err.status === 422 && err.data?.errors) {
      receiverErrors.value = err.data.errors
    } else {
      receiverErrors.value = { _general: ['Error al guardar los datos. Intentá de nuevo.'] }
    }
  } finally {
    receiverLoading.value = false
  }
}

// ─── Card Payment Brick ────────────────────────────────────────────────────────
const mpPublicKey = computed(() => page.props.mpPublicKey)

function loadMpSdk() {
  return new Promise((resolve, reject) => {
    if (window.MercadoPago) {
      resolve()
      return
    }
    const script = document.createElement('script')
    script.src = 'https://sdk.mercadopago.com/js/v2'
    script.onload = resolve
    script.onerror = reject
    document.head.appendChild(script)
  })
}

async function mountCardPaymentBrick() {
  paymentError.value = null

  if (!mpPublicKey.value) {
    paymentError.value = 'Clave pública de MercadoPago no disponible.'
    return
  }

  try {
    await loadMpSdk()

    const mp = new window.MercadoPago(mpPublicKey.value, { locale: 'es-AR' })
    const bricksBuilder = mp.bricks()

    cardPaymentBrickController = await bricksBuilder.create(
      'cardPayment',
      'cardPaymentBrick_container',
      {
        initialization: {
          amount: props.cart.total_raw,
          payer: {
            email: contact.value.contact_email || '',
          },
        },
        customization: {
          visual: {
            style: { theme: 'default' },
            hidePaymentButton: true,
          },
          paymentMethods: {
            creditCard: 'all',
            debitCard: 'all',
          },
        },
        callbacks: {
          onReady: () => {
            mpReady.value = true
          },
          onError: (error) => {
            console.error('Card Payment Brick error:', error)
            paymentError.value = 'Error en el formulario de pago. Intentá de nuevo.'
          },
        },
      },
    )
  } catch (err) {
    console.error('Error mounting Card Payment Brick:', err)
    paymentError.value = 'No se pudo cargar el formulario de pago.'
  }
}

function destroyBrick() {
  if (cardPaymentBrickController) {
    try { cardPaymentBrickController.unmount() } catch { /* ignore */ }
    cardPaymentBrickController = null
  }
  mpReady.value = false
}

async function submitPayment() {
  if (!cardPaymentBrickController) return
  if (payLoading.value) return

  payLoading.value = true
  paymentError.value = null

  try {
    const formData = await cardPaymentBrickController.getFormData()
    const additionalData = await cardPaymentBrickController.getAdditionalData().catch(() => ({}))

    const result = await jsonPost('/checkout/payment', {
      token: formData.token,
      payment_method_id: formData.payment_method_id,
      installments: formData.installments,
      payment_type_id: additionalData?.paymentTypeId ?? 'credit_card',
      payer: {
        email: formData.payer?.email || contact.value.contact_email,
        identification: formData.payer?.identification || {},
      },
    })

    paymentError.value = null
    router.visit(`/checkout/success?order=${encodeURIComponent(result.reference)}`)
  } catch (err) {
    if (err.status === 422) {
      paymentError.value = err.data?.message ?? 'Pago rechazado. Verificá los datos de tu tarjeta.'
    } else {
      paymentError.value = err.data?.message ?? 'Error al procesar el pago. Intentá de nuevo más tarde.'
    }
  } finally {
    payLoading.value = false
  }
}

// When step changes to payment, mount brick; when leaving, destroy it
watch(currentStep, (step) => {
  if (step === 4 && !cardPaymentBrickController) {
    setTimeout(() => mountCardPaymentBrick(), 100)
  } else if (step !== 4 && cardPaymentBrickController) {
    destroyBrick()
    paymentError.value = null
  }
})

onUnmounted(() => {
  destroyBrick()
})
</script>

<template>
  <section class="bg-gray-50 py-12 lg:py-24">
    <div class="max-w-screen-xl px-4 mx-auto sm:px-6 lg:px-8">

      <!-- Page title -->
      <div class="mb-12">
        <h1 class="text-4xl font-black text-gray-900 uppercase tracking-tighter italic">
          Checkout
          <span class="block text-[10px] font-black text-primary-500 uppercase tracking-[0.3em] mt-2 not-italic">
            Finalizá tu compra de forma segura
          </span>
        </h1>
      </div>

      <div class="grid grid-cols-1 gap-12 lg:grid-cols-3 lg:items-start">

        <!-- Order summary sidebar -->
        <div class="px-8 py-10 space-y-6 bg-white border border-gray-100 lg:sticky lg:top-32 rounded-2xl shadow-sm lg:order-last">
          <h3 class="text-xs font-black uppercase tracking-widest text-gray-900 flex items-center gap-2">
            Resumen del Pedido
            <span class="h-1 w-1 rounded-full bg-primary-500" />
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
                    <span class="text-[10px] font-black text-primary-500">
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
                v-if="selectedShippingOption && currentStep >= 2"
                class="flex py-3 items-start justify-between gap-2"
              >
                <dt class="text-gray-400 flex-1 min-w-0 line-clamp-2">Envío ({{ selectedShippingOption.name }})</dt>
                <dd class="text-gray-900 font-black shrink-0">{{ selectedShippingOption.price }}</dd>
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
                <dd class="text-lg font-black text-primary-500">{{ cart.total }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Steps -->
        <div class="space-y-8 lg:col-span-2">

          <!-- Step indicator -->
          <div class="flex flex-wrap items-center gap-3 mb-4">
            <div
              v-for="(label, idx) in stepLabels"
              :key="idx"
              class="flex items-center gap-2"
            >
              <span
                :class="[
                  'w-6 h-6 rounded-full text-[9px] font-black flex items-center justify-center transition-colors',
                  currentStep === idx + 1
                    ? 'bg-black text-white'
                    : currentStep > idx + 1
                      ? 'bg-primary-500 text-white'
                      : 'bg-gray-100 text-gray-400',
                ]"
              >{{ idx + 1 }}</span>
              <span
                :class="[
                  'text-[9px] font-black uppercase tracking-widest transition-colors',
                  currentStep === idx + 1 ? 'text-gray-900' : 'text-gray-400',
                ]"
              >{{ label }}</span>
              <span v-if="idx < stepLabels.length - 1" class="h-px w-6 bg-gray-200" />
            </div>
          </div>

          <!-- ═══ Step 1: Datos de Contacto ═══════════════════════════════════════ -->
          <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="flex items-center justify-between h-16 px-8 border-b border-gray-50 bg-gray-50/50">
              <h3 class="text-xs font-black uppercase tracking-widest text-gray-900 flex items-center gap-2">
                Datos de Contacto
                <span class="h-1 w-1 rounded-full bg-primary-500" />
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

            <div v-if="currentStep === 1" class="p-8">
              <div v-if="contactErrors._general" class="mb-6 p-4 bg-red-50 border border-red-100 rounded-xl text-[10px] font-bold text-red-600 uppercase tracking-widest">
                {{ contactErrors._general[0] }}
              </div>
              <form class="grid grid-cols-6 gap-6" @submit.prevent="submitContact">
                <div class="col-span-3">
                  <AppInput v-model="contact.first_name" label="Nombre *" type="text" :error="contactErrors.first_name?.[0]" />
                </div>
                <div class="col-span-3">
                  <AppInput v-model="contact.last_name" label="Apellido *" type="text" :error="contactErrors.last_name?.[0]" />
                </div>
                <div class="col-span-6 sm:col-span-3">
                  <AppInput v-model="contact.contact_email" label="Email *" type="email" :error="contactErrors.contact_email?.[0]" />
                </div>
                <div class="col-span-6 sm:col-span-3">
                  <AppInput v-model="contact.contact_phone" label="Teléfono" type="tel" />
                </div>
                <div class="col-span-6 text-right">
                  <AppButton type="submit" variant="secondary" size="lg" :disabled="contactLoading" class="ml-auto">
                    {{ contactLoading ? 'Guardando...' : 'Continuar' }}
                  </AppButton>
                </div>
              </form>
            </div>

            <div v-else-if="currentStep > 1" class="p-8">
              <dl class="grid grid-cols-1 gap-4 text-[10px] uppercase tracking-widest font-bold sm:grid-cols-3">
                <div>
                  <dt class="text-gray-400">Nombre</dt>
                  <dd class="mt-1 text-gray-900 font-black">{{ contact.first_name }} {{ contact.last_name }}</dd>
                </div>
                <div v-if="contact.contact_email">
                  <dt class="text-gray-400">Email</dt>
                  <dd class="mt-1 text-gray-900 font-black lowercase tracking-normal">{{ contact.contact_email }}</dd>
                </div>
                <div v-if="contact.contact_phone">
                  <dt class="text-gray-400">Teléfono</dt>
                  <dd class="mt-1 text-gray-900 font-black">{{ contact.contact_phone }}</dd>
                </div>
              </dl>
            </div>
          </div>

          <!-- ═══ Step 2: Tipo de Envío ═══════════════════════════════════════════ -->
          <div v-if="currentStep >= 2" class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="flex items-center justify-between h-16 px-8 border-b border-gray-50 bg-gray-50/50">
              <h3 class="text-xs font-black uppercase tracking-widest text-gray-900 flex items-center gap-2">
                Tipo de Envío
                <span class="h-1 w-1 rounded-full bg-primary-500" />
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

            <div v-if="currentStep === 2" class="p-8">
              <div v-if="shippingErrors._general" class="mb-6 p-4 bg-red-50 border border-red-100 rounded-xl text-[10px] font-bold text-red-600 uppercase tracking-widest">
                {{ shippingErrors._general[0] }}
              </div>

              <!-- Zipnova CP quoter — always visible -->
              <div class="mb-6 pb-6 border-b border-gray-100">
                <ShippingQuoter
                  :cart-mode="true"
                  @option-selected="onZipnovaSelected"
                  @postcode-changed="onZipnovaPostcodeChanged"
                  @options-cleared="onZipnovaOptionsCleared"
                />
              </div>

              <div v-if="shippingOptions.length === 0 && zipnovaOptions.length === 0" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                No hay opciones de envío disponibles.
              </div>
              <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- Static shipping options (RETLOC) -->
                <div v-for="option in shippingOptions" :key="option.identifier">
                  <input :id="option.identifier" v-model="selectedShipping" class="hidden peer" type="radio" :value="option.identifier" name="shippingOption">
                  <label
                    :for="option.identifier"
                    class="flex items-center justify-between gap-3 px-4 py-3.5 h-full text-[10px] font-black uppercase tracking-widest border border-gray-100 rounded-xl shadow-sm cursor-pointer peer-checked:border-primary-500 hover:bg-gray-50 peer-checked:ring-2 peer-checked:ring-primary-500/20 transition-all duration-300"
                  >
                    <div class="flex items-center gap-3 min-w-0">
                      <img
                        :src="storeLogo"
                        alt="Óptica Guzmán"
                        class="w-8 h-8 object-contain flex-shrink-0 rounded"
                      >
                      <p class="text-gray-900 truncate">{{ option.name }}</p>
                    </div>
                    <p class="text-primary-500 flex-shrink-0">{{ option.price }}</p>
                  </label>
                </div>

                <!-- Dynamic Zipnova options -->
                <div v-for="option in zipnovaOptions" :key="option.identifier">
                  <input :id="option.identifier" v-model="selectedShipping" class="hidden peer" type="radio" :value="option.identifier" name="shippingOption">
                  <label
                    :for="option.identifier"
                    class="flex items-center justify-between gap-3 px-4 py-3.5 h-full text-[10px] font-black uppercase tracking-widest border border-gray-100 rounded-xl shadow-sm cursor-pointer peer-checked:border-primary-500 hover:bg-gray-50 peer-checked:ring-2 peer-checked:ring-primary-500/20 transition-all duration-300"
                  >
                    <div class="flex items-center gap-3 min-w-0">
                      <img
                        v-if="option.carrier_logo"
                        :src="option.carrier_logo"
                        :alt="option.name"
                        class="w-8 h-8 object-contain flex-shrink-0 rounded"
                      >
                      <svg v-else class="w-5 h-5 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                      </svg>
                      <div class="min-w-0">
                        <p class="text-gray-900 truncate">{{ option.name }}</p>
                        <p class="text-gray-400 font-bold normal-case tracking-normal text-[9px] mt-0.5">
                          {{ option.estimated_days }}
                        </p>
                      </div>
                    </div>
                    <p class="text-primary-500 flex-shrink-0">{{ formatPrice(option.price) }}</p>
                  </label>
                </div>
              </div>
              <!-- Pickup point selector -->
              <div v-if="showPickupSelector" class="mt-4 px-1 pb-2">
                <p class="text-[10px] font-bold text-gray-600 mb-2 uppercase tracking-widest">Seleccioná un punto de retiro</p>
                <div class="space-y-2">
                  <label
                    v-for="point in zipnovaOptions.find(o => o.identifier === selectedShipping)?.pickup_points ?? []"
                    :key="point.point_id"
                    class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all duration-200"
                    :class="selectedPointId === point.point_id ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
                  >
                    <input type="radio" :value="point.point_id" v-model="selectedPointId" class="mt-0.5 accent-primary-500" />
                    <div>
                      <p class="text-[10px] font-bold text-gray-800">{{ point.description }}</p>
                      <p class="text-[9px] text-gray-500">{{ point.location.street }} {{ point.location.street_number }}, {{ point.location.city }} ({{ point.location.geolocation?.distance }}m)</p>
                    </div>
                  </label>
                </div>
              </div>

              <p v-if="shippingErrors.identifier" class="mt-4 text-[10px] font-bold text-red-600 uppercase tracking-widest">
                {{ shippingErrors.identifier[0] }}
              </p>
              <div class="mt-10 text-right">
                <AppButton variant="secondary" size="lg" :disabled="shippingLoading || !selectedShipping" class="ml-auto" @click="submitShipping">
                  {{ shippingLoading ? 'Procesando...' : 'Continuar' }}
                </AppButton>
              </div>
            </div>

            <div v-else-if="currentStep > 2 && selectedShippingOption" class="px-8 py-6">
              <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-4 min-w-0">
                  <img
                    :src="selectedShippingOption.carrier_logo || storeLogo"
                    :alt="selectedShippingOption.name"
                    class="w-10 h-10 object-contain flex-shrink-0 rounded-lg border border-gray-100 p-1"
                  >
                  <div class="min-w-0">
                    <p class="text-[10px] font-black uppercase tracking-widest text-gray-800 truncate">{{ selectedShippingOption.name }}</p>
                    <p v-if="selectedShippingOption.estimated_days" class="text-[9px] text-gray-400 mt-0.5">{{ selectedShippingOption.estimated_days }}</p>
                  </div>
                </div>
                <p class="text-sm font-black text-primary-500 flex-shrink-0">{{ selectedShippingOption.price }}</p>
              </div>
            </div>
          </div>

          <!-- ═══ Step 3: Persona que Recibe ═══════════════════════════════════════ -->
          <div v-if="currentStep >= 3" class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="flex items-center justify-between h-16 px-8 border-b border-gray-50 bg-gray-50/50">
              <h3 class="text-xs font-black uppercase tracking-widest text-gray-900 flex items-center gap-2">
                Persona que Recibe
                <span class="h-1 w-1 rounded-full bg-primary-500" />
              </h3>
              <button
                v-if="currentStep > 3"
                type="button"
                class="text-[10px] font-black uppercase tracking-widest text-gray-400 hover:text-black transition-colors flex items-center gap-1 group"
                @click="currentStep = 3"
              >
                Editar
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 transition-transform group-hover:scale-110" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
              </button>
            </div>

            <div v-if="currentStep === 3" class="p-8">
              <div v-if="receiverErrors._general" class="mb-6 p-4 bg-red-50 border border-red-100 rounded-xl text-[10px] font-bold text-red-600 uppercase tracking-widest">
                {{ receiverErrors._general[0] }}
              </div>
              <form class="grid grid-cols-6 gap-6" @submit.prevent="submitReceiver">
                <!-- Name fields — always shown -->
                <div class="col-span-3">
                  <AppInput v-model="receiver.first_name" label="Nombre *" type="text" :error="receiverErrors.first_name?.[0]" />
                </div>
                <div class="col-span-3">
                  <AppInput v-model="receiver.last_name" label="Apellido *" type="text" :error="receiverErrors.last_name?.[0]" />
                </div>

                <!-- DNI — always shown, always required -->
                <div class="col-span-6 sm:col-span-3">
                  <AppInput v-model="receiver.dni" label="DNI *" type="text" placeholder="Sin puntos ni espacios" :error="receiverErrors.dni?.[0]" />
                </div>

                <div class="col-span-6 sm:col-span-3">
                  <AppInput v-model="receiver.contact_phone" label="Teléfono de contacto" type="tel" :error="receiverErrors.contact_phone?.[0]" />
                </div>

                <!-- Address fields — only for domicilio -->
                <template v-if="showAddressFields">
                  <div class="col-span-6">
                    <hr class="border-gray-100">
                    <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-4 mb-2">Dirección de entrega</p>
                  </div>
                  <div class="col-span-6 sm:col-span-4">
                    <AppInput v-model="receiver.line_one" label="Dirección *" type="text" :error="receiverErrors.line_one?.[0]" />
                  </div>
                  <div class="col-span-6 sm:col-span-2">
                    <AppInput v-model="receiver.line_two" label="Piso/Depto" type="text" />
                  </div>
                  <div class="col-span-6 sm:col-span-3">
                    <AppInput v-model="receiver.city" label="Ciudad *" type="text" :error="receiverErrors.city?.[0]" />
                  </div>
                  <div class="col-span-6 sm:col-span-3">
                    <label class="block text-[9px] font-black uppercase tracking-widest text-gray-500 mb-1">Provincia *</label>
                    <select
                      v-model="receiver.state"
                      class="w-full px-4 py-3 border border-gray-100 rounded-xl text-xs font-bold focus:ring-2 focus:ring-primary-500 focus:border-primary-500 appearance-none bg-gray-50 outline-none"
                      :class="{ 'border-red-400': receiverErrors.state }"
                    >
                      <option value="">Seleccionar</option>
                      <option v-for="opt in provinciaOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                    </select>
                    <p v-if="receiverErrors.state" class="mt-1 text-[9px] text-red-500 font-bold">{{ receiverErrors.state[0] }}</p>
                  </div>
                  <div class="col-span-6 sm:col-span-3">
                    <AppInput v-model="receiver.postcode" label="Cód. Postal *" type="text" :error="receiverErrors.postcode?.[0]" />
                  </div>
                </template>

                <!-- Delivery instructions -->
                <div class="col-span-6">
                  <AppInput v-model="receiver.delivery_instructions" label="Instrucciones de entrega (opcional)" type="text" />
                </div>

                <div class="col-span-6 text-right">
                  <AppButton type="submit" variant="secondary" size="lg" :disabled="receiverLoading" class="ml-auto">
                    {{ receiverLoading ? 'Guardando...' : 'Continuar' }}
                  </AppButton>
                </div>
              </form>
            </div>

            <div v-else-if="currentStep > 3" class="p-8">
              <dl class="grid grid-cols-1 gap-4 text-[10px] uppercase tracking-widest font-bold sm:grid-cols-3">
                <div>
                  <dt class="text-gray-400">Recibe</dt>
                  <dd class="mt-1 text-gray-900 font-black">{{ receiver.first_name }} {{ receiver.last_name }}</dd>
                </div>
                <div>
                  <dt class="text-gray-400">DNI</dt>
                  <dd class="mt-1 text-gray-900 font-black">{{ receiver.dni }}</dd>
                </div>
                <template v-if="showAddressFields && receiver.line_one">
                  <div class="col-span-full">
                    <dt class="text-gray-400">Dirección</dt>
                    <dd class="mt-1 text-gray-900 font-black">
                      {{ receiver.line_one }}<br>
                      <template v-if="receiver.line_two">{{ receiver.line_two }}<br></template>
                      <template v-if="receiver.city">{{ receiver.city }}, </template>
                      <template v-if="receiver.state">{{ receiver.state }} </template>
                      CP: {{ receiver.postcode }}
                    </dd>
                  </div>
                </template>
              </dl>
            </div>
          </div>

          <!-- ═══ Step 4: Pago ═════════════════════════════════════════════════════ -->
          <div v-if="currentStep >= 4" class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="h-16 px-8 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between">
              <h3 class="text-xs font-black uppercase tracking-widest text-gray-900 flex items-center gap-2">
                Pago
                <span class="h-1 w-1 rounded-full bg-primary-500" />
              </h3>
              <span v-if="!mpReady" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest animate-pulse">
                Cargando formulario…
              </span>
            </div>

            <div class="p-8">
              <div
                v-if="paymentError"
                class="mb-6 p-4 bg-red-50 border border-red-100 rounded-xl text-[10px] font-bold text-red-600 uppercase tracking-widest"
              >
                {{ paymentError }}
              </div>

              <!-- Card Payment Brick renders its own form -->
              <div id="cardPaymentBrick_container" />

              <div v-if="mpReady" class="mt-6 flex justify-end">
                <AppButton
                  variant="secondary"
                  size="lg"
                  :disabled="payLoading"
                  @click="submitPayment()"
                >
                  {{ payLoading ? 'Procesando...' : 'Pagar ahora' }}
                </AppButton>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </section>
</template>
