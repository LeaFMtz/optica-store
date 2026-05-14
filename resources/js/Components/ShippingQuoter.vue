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

const emit = defineEmits(['option-selected', 'postcode-changed'])

const POSTCODE_REGEX = /^\d{4}$/

const postcode = ref('')
const options = ref([])
const loading = ref(false)
const error = ref(null)
const selected = ref(null)
const postcodeError = ref(null)
const unknownPostcode = ref(false)

const isValid = computed(() => POSTCODE_REGEX.test(postcode.value))

function validate() {
  if (!postcode.value) {
    postcodeError.value = 'Ingresá un código postal.'
    return false
  }
  if (!POSTCODE_REGEX.test(postcode.value)) {
    postcodeError.value = 'El código postal debe tener 4 dígitos.'
    return false
  }
  postcodeError.value = null
  return true
}

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
  if (!validate()) return

  loading.value = true
  error.value = null
  unknownPostcode.value = false
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

function handleBlur() {
  if (isValid.value) fetchQuotes()
  else if (postcode.value) validate()
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
    <div class="flex gap-2">
      <input
        v-model="postcode"
        type="text"
        inputmode="numeric"
        maxlength="4"
        placeholder="Código postal"
        :class="[
          'flex-1 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent transition-colors border',
          postcodeError ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-primary-500',
        ]"
        @blur="handleBlur"
        @keydown.enter.prevent="fetchQuotes"
      >
      <button
        type="button"
        :disabled="loading || !postcode"
        class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-medium rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        @click="fetchQuotes"
      >
        <span v-if="loading" class="w-4 h-4 border-2 border-gray-400 border-t-transparent rounded-full animate-spin" />
        <span v-else>Calcular</span>
      </button>
    </div>

    <p v-if="postcodeError" class="text-[10px] font-bold text-red-600">{{ postcodeError }}</p>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center gap-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
      <span class="w-3 h-3 border-2 border-gray-400 border-t-transparent rounded-full animate-spin" />
      Calculando...
    </div>

    <!-- Unknown CP -->
    <div v-else-if="unknownPostcode" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
      Código postal no encontrado.
    </div>

    <!-- No options -->
    <div v-else-if="error === 'no disponible'" class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
      Envío no disponible para ese destino.
    </div>

    <!-- Options -->
    <div v-else-if="options.length" class="space-y-2">
      <div v-for="option in options" :key="option.identifier">
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
