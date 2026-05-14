<script setup>
import { ref, computed } from 'vue'

const props = defineProps({
  productWeight: {
    type: Number,
    default: null,
  },
  cartMode: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['option-selected', 'location-changed'])

const PROVINCES = [
  'Buenos Aires',
  'Capital Federal',
  'Catamarca',
  'Chaco',
  'Chubut',
  'Córdoba',
  'Corrientes',
  'Entre Ríos',
  'Formosa',
  'Jujuy',
  'La Pampa',
  'La Rioja',
  'Mendoza',
  'Misiones',
  'Neuquén',
  'Río Negro',
  'Salta',
  'San Juan',
  'San Luis',
  'Santa Cruz',
  'Santa Fe',
  'Santiago del Estero',
  'Tierra del Fuego',
  'Tucumán',
]

// ─── State ────────────────────────────────────────────────────────────────────
const postcode = ref('')
const city = ref('')
const state = ref('')
const options = ref([])
const loading = ref(false)
const error = ref(null)
const selected = ref(null)
const fieldErrors = ref({})

// ─── Validation ───────────────────────────────────────────────────────────────
const POSTCODE_REGEX = /^\d{4}$/

const isFormValid = computed(
  () => POSTCODE_REGEX.test(postcode.value) && city.value.trim() !== '' && state.value !== '',
)

function validate() {
  const errors = {}
  if (!postcode.value) {
    errors.postcode = 'Ingresá un código postal.'
  } else if (!POSTCODE_REGEX.test(postcode.value)) {
    errors.postcode = 'El código postal debe tener 4 dígitos.'
  }
  if (!city.value.trim()) errors.city = 'Ingresá tu ciudad.'
  if (!state.value) errors.state = 'Seleccioná una provincia.'
  fieldErrors.value = errors
  return Object.keys(errors).length === 0
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function getWeightGrams() {
  return Math.max(10, props.productWeight ?? 10)
}

function getCsrf() {
  return decodeURIComponent(
    document.cookie.split('; ').find(r => r.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '',
  )
}

function formatPrice(price) {
  return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', maximumFractionDigits: 0 }).format(price)
}

// ─── Fetch quotes ─────────────────────────────────────────────────────────────
async function fetchQuotes() {
  if (!validate()) return

  loading.value = true
  error.value = null
  options.value = []
  selected.value = null

  try {
    const response = await fetch('/api/shipping/quote', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-XSRF-TOKEN': getCsrf(),
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({
        postcode: postcode.value,
        city: city.value.trim(),
        state: state.value,
        weight_grams: getWeightGrams(),
      }),
      credentials: 'same-origin',
    })

    const data = await response.json()

    if (!response.ok) {
      const apiErrors = data.errors ?? {}
      fieldErrors.value = {
        postcode: apiErrors.postcode?.[0],
        city: apiErrors.city?.[0],
        state: apiErrors.state?.[0],
      }
      return
    }

    options.value = data.options ?? []

    if (options.value.length === 0) {
      error.value = 'no disponible'
    }

    emit('location-changed', { postcode: postcode.value, city: city.value.trim(), state: state.value })
  } catch {
    error.value = 'no disponible'
    options.value = []
  } finally {
    loading.value = false
  }
}

function selectOption(option) {
  if (!props.cartMode) return
  selected.value = option.identifier
  emit('option-selected', option)
}
</script>

<template>
  <div class="space-y-3">
    <p class="text-[9px] font-black uppercase tracking-widest text-gray-500">
      Calcular envío
    </p>

    <!-- Form fields -->
    <div class="grid grid-cols-2 gap-2">
      <!-- Province -->
      <div class="col-span-2">
        <select
          v-model="state"
          :class="[
            'w-full rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent transition-colors border bg-white',
            fieldErrors.state ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-primary-500',
          ]"
        >
          <option value="" disabled>Provincia</option>
          <option v-for="p in PROVINCES" :key="p" :value="p">{{ p }}</option>
        </select>
        <p v-if="fieldErrors.state" class="mt-1 text-[10px] font-bold text-red-600">{{ fieldErrors.state }}</p>
      </div>

      <!-- City -->
      <div>
        <input
          v-model="city"
          type="text"
          placeholder="Ciudad"
          :class="[
            'w-full rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent transition-colors border',
            fieldErrors.city ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-primary-500',
          ]"
          @keydown.enter.prevent="fetchQuotes"
        >
        <p v-if="fieldErrors.city" class="mt-1 text-[10px] font-bold text-red-600">{{ fieldErrors.city }}</p>
      </div>

      <!-- Postcode -->
      <div>
        <input
          v-model="postcode"
          type="text"
          inputmode="numeric"
          maxlength="4"
          placeholder="Cód. postal"
          :class="[
            'w-full rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent transition-colors border',
            fieldErrors.postcode ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-primary-500',
          ]"
          @keydown.enter.prevent="fetchQuotes"
        >
        <p v-if="fieldErrors.postcode" class="mt-1 text-[10px] font-bold text-red-600">{{ fieldErrors.postcode }}</p>
      </div>
    </div>

    <!-- Submit button -->
    <button
      type="button"
      :disabled="loading"
      class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
      @click="fetchQuotes"
    >
      <span v-if="loading" class="w-4 h-4 border-2 border-gray-400 border-t-transparent rounded-full animate-spin" />
      <span v-else>Calcular</span>
    </button>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
      <span class="w-3 h-3 border-2 border-gray-400 border-t-transparent rounded-full animate-spin" />
      Calculando...
    </div>

    <!-- No options -->
    <div
      v-else-if="error === 'no disponible' && !options.length"
      class="text-[10px] font-bold text-gray-400 uppercase tracking-widest"
    >
      Envío no disponible para ese destino.
    </div>

    <!-- Options list -->
    <div v-else-if="options.length" class="space-y-2">
      <div v-for="option in options" :key="option.identifier">
        <!-- Cart mode: selectable -->
        <template v-if="cartMode">
          <input
            :id="option.identifier"
            v-model="selected"
            type="radio"
            :value="option.identifier"
            name="zipnovaShippingOption"
            class="hidden peer"
            @change="selectOption(option)"
          >
          <label
            :for="option.identifier"
            class="flex items-center justify-between p-4 text-[10px] font-black uppercase tracking-widest border border-gray-100 rounded-xl shadow-sm cursor-pointer peer-checked:border-primary-500 hover:bg-gray-50 peer-checked:ring-2 peer-checked:ring-primary-500/20 transition-all duration-300"
          >
            <div>
              <p class="text-gray-900">{{ option.name }}</p>
              <p class="text-gray-400 font-bold normal-case tracking-normal text-[9px] mt-0.5">{{ option.estimated_days }}</p>
            </div>
            <p class="text-primary-500">{{ formatPrice(option.price) }}</p>
          </label>
        </template>

        <!-- Display mode (product page) -->
        <template v-else>
          <div class="flex items-center justify-between p-4 text-[10px] font-black uppercase tracking-widest border border-gray-100 rounded-xl bg-gray-50">
            <div>
              <p class="text-gray-900">{{ option.name }}</p>
              <p class="text-gray-400 font-bold normal-case tracking-normal text-[9px] mt-0.5">{{ option.estimated_days }}</p>
            </div>
            <p class="text-primary-500">{{ formatPrice(option.price) }}</p>
          </div>
        </template>
      </div>
    </div>
  </div>
</template>
