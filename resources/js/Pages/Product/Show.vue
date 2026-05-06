<script setup>
import { ref, computed, inject } from 'vue'
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'
import AppButton from '@/Components/AppButton.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import Badge from '@/Components/Badge.vue'

defineOptions({ layout: StorefrontLayout })

const props = defineProps({
  product:               { type: Object,  required: true },
  images:                { type: Array,   default: () => [] },
  options:               { type: Array,   default: () => [] },
  hasLensConfigurations: { type: Boolean, default: false },
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

/** Loaded from /lens-configurations */
const lensUses          = ref([])
const lensLoading       = ref(false)
const lensError         = ref(null)

/** Step 1 — selected use */
const selectedUse  = ref(null)

/** Step 2 — selected type (within selectedUse.types) */
const selectedType = ref(null)

/** Step 3 — selected quality (within selectedType.qualities) */
const selectedQuality = ref(null)

const availableTypes = computed(() => {
  if (!selectedUse.value) return []
  const use = lensUses.value.find(u => u.id === selectedUse.value)
  return use?.types ?? []
})

const availableQualities = computed(() => {
  if (!selectedType.value) return []
  const type = availableTypes.value.find(t => t.id === selectedType.value)
  return type?.qualities ?? []
})

const canConfirm = computed(() => selectedQuality.value !== null)

async function openConfigurator() {
  configuratorOpen.value = true
  configuratorStep.value = 1
  selectedUse.value      = null
  selectedType.value     = null
  selectedQuality.value  = null

  if (lensUses.value.length > 0) return

  lensLoading.value = true
  lensError.value   = null
  try {
    const res  = await fetch(`/lens-configurations?product_id=${props.product.id}`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
    })
    const data = await res.json()
    lensUses.value = data.uses ?? []
  } catch {
    lensError.value = 'No se pudieron cargar las configuraciones de lentes.'
  } finally {
    lensLoading.value = false
  }
}

function selectUse(useId) {
  selectedUse.value  = useId
  selectedType.value = null
  configuratorStep.value = 2
}

function selectType(typeId) {
  selectedType.value    = typeId
  selectedQuality.value = null
  configuratorStep.value = 3
}

function configuratorBack() {
  if (configuratorStep.value === 3) {
    configuratorStep.value = 2
    selectedQuality.value  = null
  } else if (configuratorStep.value === 2) {
    configuratorStep.value = 1
    selectedType.value     = null
  }
}

function closeConfigurator() {
  configuratorOpen.value = false
  configuratorStep.value = 1
  selectedUse.value      = null
  selectedType.value     = null
  selectedQuality.value  = null
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
    const frameVariantId = getFirstVariantId()
    if (!frameVariantId) throw new Error('No se encontró variante del marco.')

    await postToCartRaw(frameVariantId, 1, null, selectedQuality.value.configuration_id)

    closeConfigurator()
    if (openCartSidebar) openCartSidebar()
  } catch (err) {
    cartError.value = err.message ?? 'Error al agregar al carrito.'
  } finally {
    addingCart.value = false
  }
}

async function postToCartRaw(variantId, quantity, parentLineId = null, lensConfigurationId = null) {
  const body = { variant_id: variantId, quantity }
  if (parentLineId) body.parent_line_id = parentLineId
  if (lensConfigurationId) body.lens_configuration_id = lensConfigurationId

  const response = await fetch('/cart/lines', {
    method:  'POST',
    headers: {
      'Content-Type':     'application/json',
      'Accept':           'application/json',
      'X-XSRF-TOKEN':     decodeURIComponent(getCookie('XSRF-TOKEN') ?? ''),
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify(body),
    credentials: 'same-origin',
  })

  const json = await response.json().catch(() => ({}))
  if (!response.ok) throw new Error(json.message ?? 'Error de servidor.')
  return json
}

async function postToCart(variantId, quantity) {
  await postToCartRaw(variantId, quantity)
}

function getCookie(name) {
  const value = `; ${document.cookie}`
  const parts = value.split(`; ${name}=`)
  if (parts.length === 2) {
    return parts.pop().split(';').shift()
  }
  return null
}

function getFirstVariantId() {
  return props.product.first_variant_id ?? null
}

function formatPrice(cents) {
  if (cents == null) return ''
  return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(cents / 100)
}
</script>

<template>
  <section class="bg-white py-12 lg:py-24" itemscope itemtype="http://schema.org/Product">
    <meta itemprop="sku" :content="product.sku">
    <meta itemprop="url" :content="$page.url">

    <div class="max-w-screen-xl px-4 mx-auto sm:px-6 lg:px-8">

      <!-- Breadcrumb -->
      <div class="mb-10">
        <Breadcrumb :items="[
          { label: 'Inicio', href: route('home') },
          { label: product.name },
        ]" />
      </div>

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
                  ? 'border-primary-500 shadow-lg shadow-primary-500/10'
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
              <span class="text-[9px] font-black text-primary-500 uppercase tracking-[0.3em] block">Óptica Guzmán — Premium Eyewear</span>
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
                <Badge v-if="product.discount_percentage > 0" variant="primary">{{ product.discount_percentage }}% OFF</Badge>
                <span class="text-[9px] text-primary-500 font-black uppercase tracking-widest italic">Mejor precio</span>
              </div>
              <p class="text-[9px] text-gray-400 font-black uppercase tracking-widest">Contado / Transferencia / 1 Pago</p>
              <meta itemprop="priceCurrency" content="ARS">
            </div>

            <!-- Option selectors -->
            <div class="space-y-6">
              <div v-for="opt in options" :key="opt.option_id" class="space-y-3">
                <label class="text-[9px] font-black text-gray-900 uppercase tracking-[0.2em] flex items-center gap-2">
                  <span class="w-1 h-1 bg-primary-500 rounded-full" />
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
                        ? 'bg-primary-500 border-primary-500 text-white shadow-primary-500/20'
                        : 'bg-white border-gray-100 text-gray-400 hover:border-primary-500 hover:text-primary-500',
                    ]"
                    @click="selectedValues[opt.option_id] = val.id"
                  >
                    {{ val.name }}
                  </button>
                </div>
              </div>

              <!-- CTA area -->
              <div class="pt-2">

                <!-- Dual CTA for frames with lens configurations -->
                <template v-if="hasLensConfigurations">
                  <div class="flex flex-col sm:flex-row gap-3">
                    <AppButton
                      variant="outline"
                      size="lg"
                      :disabled="addingCart"
                      class="flex-1"
                      @click="addFrameOnly"
                    >
                      Solo Marco
                    </AppButton>
                    <AppButton
                      variant="secondary"
                      size="lg"
                      :disabled="addingCart"
                      class="flex-1"
                      @click="openConfigurator"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                      </svg>
                      Agregar con Lente
                    </AppButton>
                  </div>
                </template>

                <!-- Standard add to cart -->
                <template v-else>
                  <AppButton
                    variant="secondary"
                    size="lg"
                    :disabled="addingCart"
                    class="w-full"
                    @click="addFrameOnly"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Agregar al Carrito
                  </AppButton>
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
                <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span class="text-[8px] font-black text-gray-400 uppercase tracking-widest">Transacción Segura</span>
              </div>
              <div class="flex flex-col gap-1.5">
                <svg class="w-4 h-4 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            <span class="block text-[10px] font-black text-primary-500 uppercase tracking-[0.3em] mt-2 italic not-italic">También te pueden gustar</span>
          </h3>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 opacity-50 grayscale transition hover:grayscale-0">
          <div v-for="n in 4" :key="n" class="aspect-[4/5] bg-gray-50 rounded-2xl animate-pulse" />
        </div>
      </div>
    </div>

    <!-- Lens configurator modal — 3-step wizard -->
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
            <div class="w-8">
              <button
                v-if="configuratorStep > 1"
                class="text-gray-400 hover:text-gray-700 transition-colors"
                @click="configuratorBack"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </button>
            </div>
            <div class="flex items-center gap-3">
              <div :class="configuratorStep >= 1 ? 'bg-primary-500' : 'bg-gray-200'" class="w-3 h-3 rounded-full transition-colors duration-300" />
              <div class="w-8 h-px bg-gray-200" />
              <div :class="configuratorStep >= 2 ? 'bg-primary-500' : 'bg-gray-200'" class="w-3 h-3 rounded-full transition-colors duration-300" />
              <div class="w-8 h-px bg-gray-200" />
              <div :class="configuratorStep >= 3 ? 'bg-primary-500' : 'bg-gray-200'" class="w-3 h-3 rounded-full transition-colors duration-300" />
            </div>
            <button class="text-gray-400 hover:text-gray-700 transition-colors" @click="closeConfigurator">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Step content -->
          <div class="flex-1 overflow-y-auto px-8 py-4">

            <!-- Loading state -->
            <div v-if="lensLoading" class="flex items-center justify-center py-20">
              <div class="w-6 h-6 border-2 border-primary-500 border-t-transparent rounded-full animate-spin" />
            </div>

            <!-- Error state -->
            <div v-else-if="lensError" class="flex items-center justify-center py-20">
              <p class="text-sm text-red-500 font-bold">{{ lensError }}</p>
            </div>

            <!-- Step 1: Elegir uso -->
            <div v-else-if="configuratorStep === 1">
              <h2 class="text-xl font-black uppercase tracking-tight text-center mb-2">¿Para qué vas a usar los lentes?</h2>
              <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest text-center mb-8">Seleccioná el tipo de uso</p>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <button
                  v-for="use in lensUses"
                  :key="use.id"
                  :class="selectedUse === use.id
                    ? 'border-primary-500 bg-primary-500/5'
                    : 'border-gray-200 hover:border-gray-300'"
                  class="flex flex-col items-center justify-center gap-3 p-6 border-2 rounded-2xl transition-all duration-200 text-center"
                  @click="selectUse(use.id)"
                >
                  <span class="text-sm font-black uppercase tracking-wider text-gray-900 leading-tight">{{ use.name }}</span>
                </button>
              </div>
            </div>

            <!-- Step 2: Elegir tipo de lente -->
            <div v-else-if="configuratorStep === 2">
              <h2 class="text-xl font-black uppercase tracking-tight text-center mb-2">Tipo de lente</h2>
              <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest text-center mb-8">Seleccioná el tipo de tratamiento</p>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <button
                  v-for="type in availableTypes"
                  :key="type.id"
                  :class="selectedType === type.id
                    ? 'border-primary-500 bg-primary-500/5'
                    : 'border-gray-200 hover:border-gray-300'"
                  class="flex flex-col items-center justify-center gap-3 p-6 border-2 rounded-2xl transition-all duration-200 text-center"
                  @click="selectType(type.id)"
                >
                  <span class="text-sm font-black uppercase tracking-wider text-gray-900 leading-tight">{{ type.name }}</span>
                </button>
              </div>
            </div>

            <!-- Step 3: Elegir calidad / paquete -->
            <div v-else-if="configuratorStep === 3">
              <h2 class="text-xl font-black uppercase tracking-tight text-center mb-2">Elegí tu paquete</h2>
              <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest text-center mb-8">Seleccioná la calidad de lente</p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <button
                  v-for="quality in availableQualities"
                  :key="quality.configuration_id"
                  :class="selectedQuality?.configuration_id === quality.configuration_id
                    ? 'border-primary-500 bg-primary-500/5'
                    : 'border-gray-200 hover:border-gray-300'"
                  class="flex flex-col gap-3 p-5 border-2 rounded-2xl transition-all duration-200 text-left relative"
                  @click="selectedQuality = quality"
                >
                  <!-- Recomendado badge -->
                  <span
                    v-if="quality.is_recommended"
                    class="absolute top-3 right-3 text-[8px] font-black uppercase tracking-widest bg-primary-500 text-white px-2 py-0.5 rounded-full"
                  >
                    Recomendado
                  </span>

                  <span class="text-sm font-black uppercase tracking-wider text-gray-900 pr-16">{{ quality.name }}</span>

                  <span v-if="quality.description" class="text-[10px] text-gray-500 leading-relaxed">
                    {{ quality.description }}
                  </span>

                  <!-- Features list -->
                  <ul v-if="quality.features && Object.keys(quality.features).length > 0" class="space-y-1">
                    <li
                      v-for="(value, key) in quality.features"
                      :key="key"
                      class="flex items-start gap-2 text-[9px] text-gray-500 uppercase tracking-wide"
                    >
                      <svg class="w-3 h-3 text-primary-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                      </svg>
                      {{ value }}
                    </li>
                  </ul>

                  <span class="text-lg font-black text-gray-900 mt-1">{{ formatPrice(quality.final_price) }}</span>
                </button>
              </div>

              <!-- Confirm button -->
              <div class="flex justify-center mt-10">
                <AppButton
                  variant="secondary"
                  size="lg"
                  :disabled="!canConfirm || addingCart"
                  @click="confirmAddWithLens"
                >
                  {{ addingCart ? 'Agregando…' : 'Confirmar' }}
                </AppButton>
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
            <div class="flex-1">
              <p class="text-xs font-black uppercase tracking-wider text-gray-900">{{ product.name }}</p>
              <p class="text-sm font-bold text-primary-500">{{ product.price_formatted }}</p>
            </div>
            <div v-if="selectedQuality" class="text-right">
              <p class="text-[9px] text-gray-400 uppercase tracking-widest font-bold">Lente seleccionado</p>
              <p class="text-[9px] font-black uppercase tracking-wider text-gray-900">{{ selectedQuality.name }}</p>
              <p class="text-xs font-bold text-primary-500">{{ formatPrice(selectedQuality.final_price) }}</p>
            </div>
          </div>
        </div>
      </div>
    </Teleport>
  </section>
</template>
