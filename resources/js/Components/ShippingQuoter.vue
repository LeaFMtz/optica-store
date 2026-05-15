<script setup>
import { ref, computed, watch } from 'vue'

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

const emit = defineEmits(['option-selected', 'postcode-changed', 'options-cleared'])

const POSTCODE_REGEX = /^\d{4}$/

const postcode = ref('')
const options = ref([])
const loading = ref(false)
const error = ref(null)
const selected = ref(null)
const postcodeError = ref(null)
const unknownPostcode = ref(false)
let debounceTimer = null

const isValid = computed(() => POSTCODE_REGEX.test(postcode.value))

watch(postcode, (val) => {
  clearTimeout(debounceTimer)
  postcodeError.value = null

  if (val.length < 4) {
    options.value = []
    error.value = null
    unknownPostcode.value = false
    emit('options-cleared')
    return
  }

  if (!POSTCODE_REGEX.test(val)) {
    postcodeError.value = 'El código postal debe tener 4 dígitos.'
    return
  }

  debounceTimer = setTimeout(fetchQuotes, 400)
})

function getWeightGrams() {
  return Math.max(10, props.productWeight ?? 10)
}

function getCsrf() {
  return decodeURIComponent(
    document.cookie.split('; ').find(r => r.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '',
  )
}

function formatPrice(price) {
  return new Intl.NumberFormat('es-AR', {
    style: 'currency',
    currency: 'ARS',
    maximumFractionDigits: 0,
  }).format(price)
}

async function fetchQuotes() {
  if (!isValid.value) return

  loading.value = true
  error.value = null
  unknownPostcode.value = false
  options.value = []
  selected.value = null
  emit('options-cleared')

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
        weight_grams: getWeightGrams(),
      }),
      credentials: 'same-origin',
    })

    const data = await response.json()

    if (data.unknown_postcode) {
      unknownPostcode.value = true
      return
    }

    options.value = data.options ?? []

    if (options.value.length === 0) {
      error.value = 'no disponible'
    }

    emit('postcode-changed', postcode.value)
  } catch {
    error.value = 'no disponible'
  } finally {
    loading.value = false
  }
}

function clearPostcode() {
  postcode.value = ''
  options.value = []
  error.value = null
  unknownPostcode.value = false
  postcodeError.value = null
  selected.value = null
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

    <!-- CP input -->
    <div class="relative">
      <input
        v-model="postcode"
        type="text"
        inputmode="numeric"
        maxlength="4"
        placeholder="Código postal"
        :class="[
          'w-full rounded-xl px-4 py-3 pr-10 text-sm border transition-all duration-200 focus:outline-none focus:ring-2 focus:border-transparent',
          postcodeError
            ? 'border-red-400 focus:ring-red-400 bg-red-50'
            : isValid
              ? 'border-primary-400 focus:ring-primary-400'
              : 'border-gray-200 focus:ring-primary-400',
        ]"
      >
      <div class="absolute inset-y-0 right-3 flex items-center">
        <span
          v-if="loading"
          class="w-4 h-4 border-2 border-gray-200 border-t-primary-500 rounded-full animate-spin"
        />
        <button
          v-else-if="postcode"
          type="button"
          class="text-gray-300 hover:text-gray-500 transition-colors"
          @click="clearPostcode"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>

    <p v-if="postcodeError" class="text-[10px] font-bold text-red-500">{{ postcodeError }}</p>

    <!-- Unknown CP -->
    <p v-if="unknownPostcode" class="text-[10px] font-bold uppercase tracking-widest text-amber-500">
      Código postal no encontrado.
    </p>

    <!-- No options -->
    <p v-else-if="error === 'no disponible'" class="text-[10px] font-bold uppercase tracking-widest text-gray-400">
      Envío no disponible para ese destino.
    </p>

    <!-- Options -->
    <div v-else-if="options.length" class="flex flex-col gap-2">
      <template v-if="cartMode">
        <div v-for="option in options" :key="option.identifier">
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
            class="flex items-center justify-between gap-3 px-4 py-3.5 rounded-xl border cursor-pointer transition-all duration-200
              border-gray-100 bg-white hover:border-gray-300 hover:bg-gray-50
              peer-checked:border-primary-500 peer-checked:bg-primary-50 peer-checked:ring-1 peer-checked:ring-primary-400"
          >
            <div class="flex items-center gap-3 min-w-0">
              <img
                v-if="option.carrier_logo"
                :src="option.carrier_logo"
                :alt="option.name"
                class="w-8 h-8 object-contain flex-shrink-0 rounded"
              >
              <svg v-else class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
              </svg>
              <div class="min-w-0">
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-800 truncate">{{ option.name }}</p>
                <p class="text-[9px] text-gray-400 mt-0.5">{{ option.estimated_days }}</p>
              </div>
            </div>
            <p class="text-sm font-black text-primary-500 flex-shrink-0">{{ formatPrice(option.price) }}</p>
          </label>
        </div>
      </template>

      <template v-else>
        <div
          v-for="option in options"
          :key="option.identifier"
          class="flex items-center justify-between gap-3 px-4 py-3.5 rounded-xl border border-gray-100 bg-gray-50"
        >
          <div class="flex items-center gap-3 min-w-0">
            <img
              v-if="option.carrier_logo"
              :src="option.carrier_logo"
              :alt="option.name"
              class="w-8 h-8 object-contain flex-shrink-0 rounded"
            >
            <svg v-else class="w-4 h-4 text-gray-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            </svg>
            <div class="min-w-0">
              <p class="text-[10px] font-black uppercase tracking-widest text-gray-800 truncate">{{ option.name }}</p>
              <p class="text-[9px] text-gray-400 mt-0.5">{{ option.estimated_days }}</p>
            </div>
          </div>
          <p class="text-sm font-black text-primary-500 flex-shrink-0">{{ formatPrice(option.price) }}</p>
        </div>
      </template>
    </div>
  </div>
</template>
