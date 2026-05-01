<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  isOpen: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'cartUpdated'])

const lines       = ref([])
const count       = ref(0)
const loading     = ref(false)
const error       = ref(null)
const updatingId  = ref(null)

// Fetch cart on open
watch(() => props.isOpen, (open) => {
  if (open) fetchCart()
})

async function fetchCart() {
  loading.value = true
  error.value   = null
  try {
    const res  = await fetch('/cart', {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    const data = await res.json()
    lines.value = data.lines ?? []
    count.value = data.count ?? 0
    emit('cartUpdated', data.count ?? 0)
  } catch {
    error.value = 'No se pudo cargar el carrito.'
  } finally {
    loading.value = false
  }
}

async function updateQuantity(lineId, quantity) {
  if (quantity < 1) return
  updatingId.value = lineId
  error.value      = null
  try {
    const res  = await apiRequest(`/cart/lines/${lineId}`, 'PATCH', { quantity })
    const data = await res.json()
    lines.value = data.lines ?? []
    count.value = data.count ?? 0
    emit('cartUpdated', data.count ?? 0)
  } catch {
    error.value = 'Error al actualizar la cantidad.'
  } finally {
    updatingId.value = null
  }
}

async function removeLine(lineId) {
  updatingId.value = lineId
  error.value      = null
  try {
    const res  = await apiRequest(`/cart/lines/${lineId}`, 'DELETE')
    const data = await res.json()
    lines.value = data.lines ?? []
    count.value = data.count ?? 0
    emit('cartUpdated', data.count ?? 0)
  } catch {
    error.value = 'Error al eliminar el producto.'
  } finally {
    updatingId.value = null
  }
}

async function apiRequest(url, method, body = null) {
  const options = {
    method,
    headers: {
      'Accept':           'application/json',
      'Content-Type':     'application/json',
      'X-XSRF-TOKEN':     decodeURIComponent(getCookie('XSRF-TOKEN') ?? ''),
      'X-Requested-With': 'XMLHttpRequest',
    },
    credentials: 'same-origin',
  }
  if (body) options.body = JSON.stringify(body)

  const res = await fetch(url, options)
  if (!res.ok) throw new Error('Server error')
  return res
}

function getCookie(name) {
  const value = `; ${document.cookie}`
  const parts = value.split(`; ${name}=`)
  if (parts.length === 2) return parts.pop().split(';').shift()
  return null
}

const subtotal = () => {
  // sub_total is already formatted by the server; show the last line's sub_total
  // or just return the last item's sub_total. Real subtotal needs a dedicated field.
  // For now we don't have a cart-level total from the endpoint, show per-line totals.
  return null
}
</script>

<template>
  <!-- Backdrop -->
  <Transition name="backdrop">
    <div
      v-if="isOpen"
      class="fixed inset-0 bg-black/40 backdrop-blur-sm z-40"
      @click="$emit('close')"
    />
  </Transition>

  <!-- Sidebar panel -->
  <Transition name="slide">
    <div
      v-if="isOpen"
      class="fixed top-0 right-0 h-full w-full max-w-md bg-white z-50 flex flex-col shadow-2xl"
    >
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100">
        <div class="flex items-center gap-3">
          <h2 class="text-sm font-black uppercase tracking-widest text-gray-900">Carrito</h2>
          <span
            v-if="count > 0"
            class="bg-[#71C229] text-white text-[10px] font-bold rounded-full w-5 h-5 flex items-center justify-center"
          >{{ count }}</span>
        </div>
        <button
          class="text-gray-400 hover:text-gray-700 transition-colors p-1"
          @click="$emit('close')"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="flex-1 overflow-y-auto px-6 py-6">

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-20">
          <div class="w-6 h-6 border-2 border-[#71C229] border-t-transparent rounded-full animate-spin" />
        </div>

        <!-- Error -->
        <div
          v-else-if="error"
          class="p-4 text-[9px] font-bold text-red-600 bg-red-50 border border-red-100 rounded-xl uppercase tracking-[0.2em]"
        >
          {{ error }}
        </div>

        <!-- Empty cart -->
        <div v-else-if="lines.length === 0" class="flex flex-col items-center justify-center py-20 text-center">
          <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          <p class="text-sm text-gray-400 font-medium">Tu carrito está vacío</p>
          <p class="text-xs text-gray-300 mt-1">Explorá nuestros productos</p>
        </div>

        <!-- Lines -->
        <ul v-else class="space-y-6">
          <li
            v-for="line in lines"
            :key="line.id"
            class="flex gap-4 relative"
          >
            <!-- Thumbnail -->
            <div class="w-20 h-20 flex-shrink-0 rounded-xl overflow-hidden bg-gray-50 border border-gray-100">
              <img
                v-if="line.thumbnail"
                :src="line.thumbnail"
                :alt="line.description"
                class="w-full h-full object-cover"
              >
              <div v-else class="w-full h-full flex items-center justify-center text-gray-200">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <p class="text-xs font-black text-gray-900 uppercase tracking-tight leading-tight line-clamp-2">
                {{ line.description }}
              </p>
              <p v-if="line.options" class="text-[9px] text-gray-400 uppercase tracking-widest mt-0.5">
                {{ line.options }}
              </p>
              <p class="text-xs font-bold text-[#71C229] mt-1">{{ line.unit_price }}</p>

              <!-- Quantity controls -->
              <div class="flex items-center gap-2 mt-3">
                <button
                  :disabled="updatingId === line.id || line.quantity <= 1"
                  class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded-lg text-gray-600 hover:border-[#71C229] hover:text-[#71C229] transition-colors disabled:opacity-40 text-sm font-bold"
                  @click="updateQuantity(line.id, line.quantity - 1)"
                >
                  -
                </button>
                <span class="text-xs font-bold text-gray-900 w-6 text-center">{{ line.quantity }}</span>
                <button
                  :disabled="updatingId === line.id"
                  class="w-7 h-7 flex items-center justify-center border border-gray-200 rounded-lg text-gray-600 hover:border-[#71C229] hover:text-[#71C229] transition-colors disabled:opacity-40 text-sm font-bold"
                  @click="updateQuantity(line.id, line.quantity + 1)"
                >
                  +
                </button>
              </div>
            </div>

            <!-- Sub-total + remove -->
            <div class="flex flex-col items-end justify-between">
              <button
                :disabled="updatingId === line.id"
                class="text-gray-300 hover:text-red-400 transition-colors disabled:opacity-40"
                @click="removeLine(line.id)"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
              <span class="text-xs font-black text-gray-900">{{ line.sub_total }}</span>
            </div>

            <!-- Updating overlay -->
            <div
              v-if="updatingId === line.id"
              class="absolute inset-0 bg-white/70 flex items-center justify-center rounded-xl"
            >
              <div class="w-4 h-4 border-2 border-[#71C229] border-t-transparent rounded-full animate-spin" />
            </div>
          </li>
        </ul>
      </div>

      <!-- Footer -->
      <div v-if="lines.length > 0" class="border-t border-gray-100 px-6 py-6 space-y-4">
        <a
          :href="route('checkout.view')"
          class="block w-full h-14 bg-black text-white text-[10px] font-bold uppercase tracking-[0.3em] rounded-xl hover:bg-[#71C229] hover:text-black transition-all duration-500 flex items-center justify-center shadow-lg shadow-black/5 hover:shadow-[#71C229]/20"
        >
          Ir al Checkout
        </a>
        <button
          class="block w-full text-center text-[9px] font-black text-gray-400 uppercase tracking-widest hover:text-gray-700 transition-colors"
          @click="$emit('close')"
        >
          Seguir comprando
        </button>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.backdrop-enter-active,
.backdrop-leave-active {
  transition: opacity 0.25s ease;
}
.backdrop-enter-from,
.backdrop-leave-to {
  opacity: 0;
}

.slide-enter-active,
.slide-leave-active {
  transition: transform 0.3s ease;
}
.slide-enter-from,
.slide-leave-to {
  transform: translateX(100%);
}
</style>
