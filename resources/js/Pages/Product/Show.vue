<script setup>
import { ref, computed, inject } from 'vue'
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'

defineOptions({ layout: StorefrontLayout })

const props = defineProps({
  product: { type: Object, required: true },
  images:  { type: Array,  default: () => [] },
  options: { type: Array,  default: () => [] },
  lensMap: { type: Object, default: () => ({}) },
  hasLens: { type: Boolean, default: false },
})

// ─── Cart sidebar open/close via provide/inject from StorefrontLayout ────────
const openCartSidebar = inject('openCartSidebar', null)

// ─── Image gallery ────────────────────────────────────────────────────────────
const primaryImage = computed(() => {
  const primary = props.images.find(img => img.is_primary)
  return primary ?? props.images[0] ?? null
})
const activeImage = ref(primaryImage.value)

// ─── Variant option selector ──────────────────────────────────────────────────
const selectedValues = ref(
  Object.fromEntries(props.options.map(opt => [opt.option_id, opt.values[0]?.id ?? null])),
)

// ─── Lens configurator state ──────────────────────────────────────────────────
const configuratorOpen = ref(false)
const configuratorStep = ref(1)
const selectedUso      = ref(null)
const selectedLens     = ref(null)

const lensMapEntries = computed(() => Object.entries(props.lensMap))

const availableLensValues = computed(() => {
  if (!selectedUso.value) {
    return []
  }
  return props.lensMap[selectedUso.value]?.values ?? []
})

const childOptionName = computed(() => {
  if (!selectedUso.value) {
    return 'Tipo de lente'
  }
  return props.lensMap[selectedUso.value]?.child_option_name ?? 'Tipo de lente'
})

const canConfirm = computed(() => selectedUso.value !== null && selectedLens.value !== null)

function selectUso(id) {
  selectedUso.value  = id
  selectedLens.value = null
  configuratorStep.value = 2
}
function selectLens(id) { selectedLens.value = id }
function configuratorBack() { configuratorStep.value = 1; selectedLens.value = null }
function closeConfigurator() {
  configuratorOpen.value = false
  configuratorStep.value = 1
  selectedUso.value      = null
  selectedLens.value     = null
}

// ─── Cart operations ──────────────────────────────────────────────────────────
const cartError  = ref(null)
const addingCart = ref(false)

async function addFrameOnly() {
  cartError.value  = null
  addingCart.value = true
  try {
    const firstVariantId = getFirstVariantId()
    if (!firstVariantId) {
      cartError.value = 'No se encontró variante disponible.'
      return
    }
    await postToCart(firstVariantId, 1)
    if (openCartSidebar) openCartSidebar()
  } catch (err) {
    cartError.value = err.message ?? 'Error al agregar al carrito.'
  } finally {
    addingCart.value = false
  }
}

async function confirmAddWithLens() {
  if (!canConfirm.value) return
  cartError.value  = null
  addingCart.value = true
  try {
    const variantId = resolveVariantByLens(Number(selectedUso.value), selectedLens.value)
    if (!variantId) {
      cartError.value = 'No encontramos esa combinación de lente.'
      return
    }
    await postToCart(variantId, 1)
    closeConfigurator()
    if (openCartSidebar) openCartSidebar()
  } catch (err) {
    cartError.value = err.message ?? 'Error al agregar al carrito.'
  } finally {
    addingCart.value = false
  }
}

async function postToCart(variantId, quantity) {
  const csrfMeta = document.querySelector('meta[name="csrf-token"]')
  const csrf     = csrfMeta ? csrfMeta.getAttribute('content') : ''

  const response = await fetch('/cart/lines', {
    method:  'POST',
    headers: {
      'Content-Type':     'application/json',
      'Accept':           'application/json',
      'X-XSRF-TOKEN':     decodeURIComponent(getCookie('XSRF-TOKEN') ?? ''),
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify({ variant_id: variantId, quantity }),
    credentials: 'same-origin',
  })

  if (!response.ok) {
    const json = await response.json().catch(() => ({}))
    throw new Error(json.message ?? 'Error de servidor.')
  }
}

function getCookie(name) {
  const value = `; ${document.cookie}`
  const parts = value.split(`; ${name}=`)
  if (parts.length === 2) {
    return parts.pop().split(';').shift()
  }
  return null
}

/**
 * Returns the first variant id — used for "frame only" add.
 * The backend resolves actual variant matching; here we only need a placeholder
 * that the Livewire ProductPage also used (it called product.variants.first()).
 */
function getFirstVariantId() {
  // We don't have variant IDs in props directly.
  // The "frame only" path passes variant_id derived from the first option selection.
  // Since options come with value IDs but not variant IDs, we rely on the backend
  // CartController to handle variant_id = the first option value's variant.
  // For now, expose variantId via the product prop extended in ProductController.
  return props.product.first_variant_id ?? null
}

/**
 * Resolve a variant ID by uso + lens value IDs.
 * Since we don't carry variant-to-value mapping in props, we rely on the
 * backend CartController to do the matching. Here we pass uso+lens as metadata.
 * NOTE: This requires CartController.store() to support uso_value_id + lens_value_id
 * OR we enrich the product prop with variant mappings. We enrich the prop (see ProductController).
 */
function resolveVariantByLens(usoValueId, lensValueId) {
  const variants = props.product.variants ?? []
  const match = variants.find(v => {
    const ids = v.value_ids ?? []
    return ids.includes(usoValueId) && ids.includes(lensValueId)
  })
  return match?.id ?? null
}
</script>

<template>
  <section class="bg-white py-12 lg:py-24" itemscope itemtype="http://schema.org/Product">
    <meta itemprop="sku" :content="product.sku">
    <meta itemprop="url" :content="$page.url">

    <div class="max-w-screen-xl px-4 mx-auto sm:px-6 lg:px-8">

      <!-- Breadcrumb -->
      <nav class="text-[9px] font-black uppercase tracking-[0.2em] flex items-center gap-2 mb-10">
        <a :href="route('home')" class="text-gray-400 hover:text-[#71C229] transition-colors">Inicio</a>
        <span class="text-gray-200">/</span>
        <span class="text-gray-900" itemprop="name">{{ product.name }}</span>
      </nav>

      <div class="grid items-start grid-cols-1 gap-12 lg:grid-cols-11">

        <!-- Image Gallery (5/11) -->
        <div class="lg:col-span-5 space-y-4">
          <div class="bg-gray-50 rounded-2xl overflow-hidden aspect-[4/5] border border-gray-100 shadow-sm relative group/main">
            <img
              v-if="activeImage"
              :src="activeImage.url_large"
              :alt="product.name"
              class="w-full h-full object-cover transition-transform duration-700 group-hover/main:scale-105"
              itemprop="image"
            >
            <div class="absolute inset-0 bg-black/0 group-hover/main:bg-black/5 transition-colors duration-500" />
          </div>

          <div class="grid grid-cols-5 gap-2">
            <button
              v-for="img in images"
              :key="img.id"
              :class="[
                'aspect-square rounded-xl border-2 transition-all duration-300 overflow-hidden bg-gray-50',
                activeImage?.id === img.id
                  ? 'border-[#71C229] shadow-lg shadow-[#71C229]/10'
                  : 'border-transparent hover:border-gray-200',
              ]"
              type="button"
              @click="activeImage = img"
            >
              <img
                loading="lazy"
                :src="img.url_small"
                :alt="product.name"
                :class="['w-full h-full object-cover transition-opacity', activeImage?.id === img.id ? 'opacity-100' : 'opacity-60 hover:opacity-100']"
              >
            </button>
          </div>
        </div>

        <!-- Product Info (6/11) -->
        <div class="lg:col-span-6 flex flex-col items-start pt-2 lg:sticky lg:top-32">
          <div class="w-full space-y-8">

            <!-- Title + brand -->
            <div class="space-y-3">
              <span class="text-[9px] font-black text-[#71C229] uppercase tracking-[0.3em] block">Óptica Guzmán — Premium Eyewear</span>
              <h1 class="text-3xl sm:text-4xl font-black text-gray-900 leading-none uppercase tracking-tighter italic" itemprop="name">
                {{ product.name }}
              </h1>
              <div class="flex items-center gap-3 pt-1">
                <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest border-r border-gray-200 pr-3">SKU: {{ product.sku }}</p>
                <span class="text-[8px] font-black text-white bg-black px-2 py-0.5 rounded uppercase tracking-widest">Original</span>
              </div>
            </div>

            <!-- Price -->
            <div class="py-6 border-y border-gray-100 flex flex-col gap-1" itemprop="offers" itemscope itemtype="http://schema.org/Offer">
              <div class="flex items-baseline gap-2">
                <span class="text-3xl font-black text-gray-900" itemprop="price">{{ product.price_formatted }}</span>
                <span v-if="product.base_price_formatted" class="text-lg text-gray-400 line-through">{{ product.base_price_formatted }}</span>
                <span v-if="product.discount_percentage > 0" class="text-[9px] text-white bg-[#71C229] font-black uppercase tracking-widest rounded px-2 py-0.5">{{ product.discount_percentage }}% OFF</span>
                <span class="text-[9px] text-[#71C229] font-black uppercase tracking-widest italic">Mejor precio</span>
              </div>
              <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest">Contado / Transferencia / 1 Pago</p>
              <meta itemprop="priceCurrency" content="ARS">
            </div>

            <!-- Option selectors -->
            <div class="space-y-6">
              <div v-for="opt in options" :key="opt.option_id" class="space-y-3">
                <label class="text-[9px] font-black text-gray-900 uppercase tracking-[0.2em] flex items-center gap-2">
                  <span class="w-1 h-1 bg-[#71C229] rounded-full" />
                  {{ opt.option_name }}
                </label>
                <div class="flex flex-wrap gap-2">
                  <button
                    v-for="val in opt.values"
                    :key="val.id"
                    type="button"
                    :class="[
                      'px-5 py-2.5 text-[9px] font-black uppercase tracking-widest border-2 rounded-xl transition-all duration-300 shadow-sm active:scale-95',
                      selectedValues[opt.option_id] === val.id
                        ? 'bg-[#71C229] border-[#71C229] text-white shadow-[#71C229]/20'
                        : 'bg-white border-gray-100 text-gray-400 hover:border-[#71C229] hover:text-[#71C229]',
                    ]"
                    @click="selectedValues[opt.option_id] = val.id"
                  >
                    {{ val.name }}
                  </button>
                </div>
              </div>

              <!-- CTA area -->
              <div class="pt-2">

                <!-- Dual CTA for lens products -->
                <template v-if="hasLens">
                  <div class="flex flex-col sm:flex-row gap-3">
                    <button
                      type="button"
                      :disabled="addingCart"
                      class="flex-1 h-14 px-8 text-[10px] font-bold uppercase tracking-[0.3em] text-gray-700 bg-gray-50 border border-gray-200 rounded-xl hover:border-gray-400 transition-all duration-300 flex items-center justify-center gap-3 active:scale-[0.98] disabled:opacity-60"
                      @click="addFrameOnly"
                    >
                      Solo Marco
                    </button>
                    <button
                      type="button"
                      :disabled="addingCart"
                      class="flex-1 h-14 px-8 text-[10px] font-bold uppercase tracking-[0.3em] text-white bg-black rounded-xl hover:bg-[#71C229] hover:text-black transition-all duration-500 shadow-lg shadow-black/5 hover:shadow-[#71C229]/20 flex items-center justify-center gap-3 group/add active:scale-[0.98] disabled:opacity-60"
                      @click="configuratorOpen = true"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                      Agregar con Lente
                    </button>
                  </div>
                </template>

                <!-- Standard add to cart -->
                <template v-else>
                  <button
                    type="button"
                    :disabled="addingCart"
                    class="w-full h-14 px-8 text-[10px] font-bold uppercase tracking-[0.3em] text-white bg-black rounded-xl hover:bg-[#71C229] hover:text-black transition-all duration-500 shadow-lg shadow-black/5 flex items-center justify-center gap-3 active:scale-[0.98] disabled:opacity-60"
                    @click="addFrameOnly"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Agregar al Carrito
                  </button>
                </template>

                <!-- Error message -->
                <div
                  v-if="cartError"
                  class="p-3 mt-4 text-[9px] font-bold text-center text-red-600 rounded-xl bg-red-50 border border-red-100 uppercase tracking-[0.2em] shadow-sm"
                  role="alert"
                >
                  {{ cartError }}
                </div>
              </div>
            </div>

            <!-- Description -->
            <div class="pt-8 border-t border-gray-100">
              <label class="text-[9px] font-black text-gray-900 uppercase tracking-[0.2em] block mb-3">Detalles del producto</label>
              <div
                class="prose prose-sm max-w-none text-gray-500 text-[10px] leading-relaxed uppercase tracking-tight font-medium"
                itemprop="description"
                v-html="product.description"
              />
            </div>

            <!-- Trust badges -->
            <div class="grid grid-cols-2 gap-4 pt-8 border-t border-gray-100">
              <div class="flex flex-col gap-1.5">
                <svg class="w-4 h-4 text-[#71C229]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Transacción Segura</span>
              </div>
              <div class="flex flex-col gap-1.5">
                <svg class="w-4 h-4 text-[#71C229]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Garantía de Óptica</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Related products placeholder -->
      <div class="mt-24 pt-24 border-t border-gray-100">
        <div class="flex items-end justify-between mb-12">
          <h3 class="text-2xl font-black uppercase tracking-tighter italic text-black">
            Productos Similares
            <span class="block text-[10px] font-black text-[#71C229] uppercase tracking-[0.3em] mt-2 italic not-italic">También te pueden gustar</span>
          </h3>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 opacity-50 grayscale transition hover:grayscale-0">
          <div v-for="n in 4" :key="n" class="aspect-[4/5] bg-gray-50 rounded-2xl animate-pulse" />
        </div>
      </div>
    </div>

    <!-- Lens configurator modal -->
    <Teleport to="body">
      <div
        v-if="configuratorOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
      >
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeConfigurator" />

        <!-- Modal panel -->
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col overflow-hidden">

          <!-- Progress dots + close -->
          <div class="flex items-center justify-between px-8 pt-8 pb-4">
            <div class="w-8" />
            <div class="flex items-center gap-3">
              <div :class="configuratorStep >= 1 ? 'bg-[#71C229]' : 'bg-gray-200'" class="w-3 h-3 rounded-full transition-colors duration-300" />
              <div class="w-8 h-px bg-gray-200" />
              <div :class="configuratorStep >= 2 ? 'bg-[#71C229]' : 'bg-gray-200'" class="w-3 h-3 rounded-full transition-colors duration-300" />
            </div>
            <button class="text-gray-400 hover:text-gray-700 transition-colors" @click="closeConfigurator">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Step content -->
          <div class="flex-1 overflow-y-auto px-8 py-4">

            <!-- Step 1: Uso -->
            <div v-if="configuratorStep === 1">
              <h2 class="text-xl font-black uppercase tracking-tight text-center mb-8">Uso</h2>
              <div class="flex flex-wrap justify-center gap-4">
                <button
                  v-for="[usoId, data] in lensMapEntries"
                  :key="usoId"
                  :class="String(selectedUso) === String(usoId) ? 'border-[#71C229] bg-[#71C229]/5' : 'border-gray-200 hover:border-gray-400'"
                  class="inline-flex flex-col items-center gap-3 p-6 border-2 rounded-2xl transition-all duration-200 text-left w-44"
                  @click="selectUso(usoId)"
                >
                  <span class="text-xs font-black uppercase tracking-wider text-center">{{ data.uso_name }}</span>
                </button>
              </div>
            </div>

            <!-- Step 2: Tipo de lente -->
            <div v-if="configuratorStep === 2">
              <div class="flex items-center gap-4 mb-8">
                <button class="text-gray-400 hover:text-gray-700 transition-colors" @click="configuratorBack">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                  </svg>
                </button>
                <h2 class="flex-1 text-xl font-black uppercase tracking-tight text-center">{{ childOptionName }}</h2>
              </div>
              <div class="flex flex-wrap justify-center gap-4">
                <button
                  v-for="lens in availableLensValues"
                  :key="lens.id"
                  :class="selectedLens === lens.id ? 'border-[#71C229] bg-[#71C229]/5' : 'border-gray-200 hover:border-gray-400'"
                  class="flex flex-col items-center gap-3 p-6 border-2 rounded-2xl transition-all duration-200 w-44"
                  @click="selectLens(lens.id)"
                >
                  <span class="text-xs font-black uppercase tracking-wider text-center">{{ lens.name }}</span>
                </button>
              </div>
              <div class="flex justify-center mt-8">
                <button
                  :disabled="!canConfirm || addingCart"
                  :class="canConfirm ? 'bg-black hover:bg-[#71C229] hover:text-black' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                  class="px-12 h-14 text-[10px] font-bold uppercase tracking-[0.3em] text-white rounded-xl transition-all duration-300 disabled:opacity-60"
                  @click="confirmAddWithLens"
                >
                  Agregar al carrito
                </button>
              </div>
            </div>
          </div>

          <!-- Bottom bar -->
          <div class="border-t border-gray-100 px-8 py-4 flex items-center gap-4 bg-gray-50/50">
            <img
              v-if="activeImage"
              :src="activeImage.url_small"
              class="w-12 h-12 object-cover rounded-xl"
              alt=""
            >
            <div>
              <p class="text-xs font-black uppercase tracking-wider text-gray-900">{{ product.name }}</p>
              <p class="text-sm font-bold text-[#71C229]">{{ product.price_formatted }}</p>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </section>
</template>
