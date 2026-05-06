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
const lensUses    = ref([])
const lensLoading = ref(false)
const lensError   = ref(null)

/** Step 1 — selected use id */
const selectedUse  = ref(null)

/** Step 2 — selected type id (within selectedUse.types) */
const selectedType = ref(null)

/** Step 3 — selected crystal object (within selectedType.crystals) */
const selectedCrystal = ref(null)

/** Prescription field values — keyed as od_{key}, oi_{key}, pd, pd_od, pd_oi */
const prescriptionValues = ref({})
const doublePd           = ref(false)

// ─── Derived from selected use ────────────────────────────────────────────────
const selectedUseData    = computed(() => lensUses.value.find(u => u.id === selectedUse.value))
const prescriptionFields = computed(() => selectedUseData.value?.prescription_type?.prescription_fields ?? [])
const requiresPrescription = computed(() => prescriptionFields.value.length > 0)
const totalSteps  = computed(() => requiresPrescription.value ? 4 : 3)
const typeStep    = computed(() => requiresPrescription.value ? 3 : 2)
const crystalStep = computed(() => requiresPrescription.value ? 4 : 3)

/** Field used as PD (single/double) — detected by key or label */
const pdField = computed(() =>
  prescriptionFields.value.find(f => /pd|dp|pupilar/i.test(f.key + ' ' + f.label))
)

/** Eye-specific fields — all except PD */
const tableFields = computed(() =>
  prescriptionFields.value.filter(f => f.key !== pdField.value?.key)
)

const availableTypes = computed(() => selectedUseData.value?.types ?? [])

const availableCrystals = computed(() => {
  if (!selectedType.value) return []
  const type = availableTypes.value.find(t => t.id === selectedType.value)
  return type?.crystals ?? []
})

/** Opciones pre-generadas para campos con step decimal */
const fieldOptions = computed(() => {
  const result = {}
  const decimalFields = [...tableFields.value.filter(f => f.step < 1), ...(pdField.value ? [pdField.value] : [])]
  decimalFields.forEach(f => {
      const precision = (f.step.toString().split('.')[1] ?? '').length
      const opts = []
      let v = f.min
      while (v <= f.max + Number.EPSILON) {
        const r = Math.round(v * 1000) / 1000
        opts.push({
          value: r,
          label: r === 0 ? 'Plano' : (r > 0 ? `+${r.toFixed(precision)}` : r.toFixed(precision)),
        })
        v = Math.round((v + f.step) * 1000) / 1000
      }
      result[f.key] = opts
    })
  return result
})

/** Picker overlay para campos decimales */
const activePicker = ref(null) // { key, field }

const pickerNegative = computed(() =>
  activePicker.value ? (fieldOptions.value[activePicker.value.field.key] ?? []).filter(o => o.value < 0) : []
)
const pickerPositive = computed(() =>
  activePicker.value ? (fieldOptions.value[activePicker.value.field.key] ?? []).filter(o => o.value >= 0) : []
)

function openPicker(key, field) { activePicker.value = { key, field } }
function closePicker()          { activePicker.value = null }
function pickValue(value)       { prescriptionValues.value[activePicker.value.key] = value; closePicker() }

function pickerLabel(value, field) {
  if (value === undefined || value === null) return '—'
  if (value === 0) return 'Plano'
  const precision = (field.step.toString().split('.')[1] ?? '').length
  return value > 0 ? `+${value.toFixed(precision)}` : value.toFixed(precision)
}

function isFilled(key) {
  const v = prescriptionValues.value[key]
  return v !== '' && v !== null && v !== undefined && !Number.isNaN(Number(v))
}

const prescriptionComplete = computed(() => {
  const eyeKeys = tableFields.value.flatMap(f => [`od_${f.key}`, `oi_${f.key}`])
  const pdKeys  = pdField.value
    ? (doublePd.value ? ['pd_od', 'pd_oi'] : ['pd'])
    : []
  const all = [...eyeKeys, ...pdKeys]
  return all.length > 0 && all.every(isFilled)
})

const canConfirm = computed(() => {
  if (!selectedCrystal.value) return false
  if (requiresPrescription.value) return prescriptionComplete.value
  return true
})

async function openConfigurator() {
  configuratorOpen.value   = true
  configuratorStep.value   = 1
  selectedUse.value        = null
  selectedType.value       = null
  selectedCrystal.value    = null
  prescriptionValues.value = {}

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
  selectedUse.value        = useId
  selectedType.value       = null
  doublePd.value           = false
  prescriptionValues.value = {}
  configuratorStep.value   = 2
}

function resetPrescription() {
  doublePd.value           = false
  prescriptionValues.value = {}
}

function toggleDoublePd() {
  doublePd.value = !doublePd.value
  delete prescriptionValues.value['pd']
  delete prescriptionValues.value['pd_od']
  delete prescriptionValues.value['pd_oi']
}

function stepField(key, field, direction) {
  const current = prescriptionValues.value[key] ?? 0
  const next    = current + direction * field.step
  prescriptionValues.value[key] = Math.min(field.max, Math.max(field.min, Math.round(next * 1000) / 1000))
}

function advancePrescription() {
  configuratorStep.value = typeStep.value
}

function selectType(typeId) {
  selectedType.value     = typeId
  selectedCrystal.value  = null
  configuratorStep.value = crystalStep.value
}

function configuratorBack() {
  if (configuratorStep.value === crystalStep.value) {
    configuratorStep.value = typeStep.value
    selectedCrystal.value  = null
  } else if (configuratorStep.value === typeStep.value) {
    configuratorStep.value = requiresPrescription.value ? 2 : 1
    selectedType.value     = null
  } else if (configuratorStep.value === 2) {
    configuratorStep.value = 1
  }
}

function closeConfigurator() {
  configuratorOpen.value   = false
  configuratorStep.value   = 1
  selectedUse.value        = null
  selectedType.value       = null
  selectedCrystal.value    = null
  prescriptionValues.value = {}
  doublePd.value           = false
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

    let prescriptionData = null
    if (requiresPrescription.value) {
      prescriptionData = {}
      tableFields.value.forEach(f => {
        prescriptionData[`od_${f.key}`] = prescriptionValues.value[`od_${f.key}`]
        prescriptionData[`oi_${f.key}`] = prescriptionValues.value[`oi_${f.key}`]
      })
      if (pdField.value) {
        if (doublePd.value) {
          prescriptionData.pd_od = prescriptionValues.value.pd_od
          prescriptionData.pd_oi = prescriptionValues.value.pd_oi
        } else {
          prescriptionData.pd = prescriptionValues.value.pd
        }
      }
    }

    await postToCartRaw(frameVariantId, 1, null, selectedCrystal.value.configuration_id, prescriptionData)

    closeConfigurator()
    if (openCartSidebar) openCartSidebar()
  } catch (err) {
    cartError.value = err.message ?? 'Error al agregar al carrito.'
  } finally {
    addingCart.value = false
  }
}

async function postToCartRaw(variantId, quantity, parentLineId = null, lensConfigurationId = null, prescriptionData = null) {
  const body = { variant_id: variantId, quantity }
  if (parentLineId) body.parent_line_id = parentLineId
  if (lensConfigurationId) body.lens_configuration_id = lensConfigurationId
  if (prescriptionData) body.prescription_data = prescriptionData

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
              <template v-for="n in totalSteps" :key="n">
                <div :class="configuratorStep >= n ? 'bg-primary-500' : 'bg-gray-200'" class="w-3 h-3 rounded-full transition-colors duration-300" />
                <div v-if="n < totalSteps" class="w-8 h-px bg-gray-200" />
              </template>
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
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <button
                  v-for="use in lensUses"
                  :key="use.id"
                  :class="selectedUse === use.id
                    ? 'border-primary-500 bg-primary-500/5'
                    : 'border-gray-200 hover:border-gray-300'"
                  class="flex items-center justify-center p-5 border-2 rounded-2xl transition-all duration-200 text-center"
                  @click="selectUse(use.id)"
                >
                  <span class="text-sm font-black uppercase tracking-wider text-gray-900 leading-tight">{{ use.name }}</span>
                </button>
              </div>
            </div>

            <!-- Step 2: Receta (si el uso la requiere) -->
            <div v-else-if="configuratorStep === 2 && requiresPrescription">

              <!-- Header -->
              <div class="flex items-center justify-between mb-6">
                <div>
                  <h2 class="text-xl font-black uppercase tracking-tight">Ingresá tu receta</h2>
                  <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest mt-1">Completá los datos de graduación</p>
                </div>
                <button
                  class="flex items-center gap-1.5 text-[9px] font-black text-gray-400 uppercase tracking-widest hover:text-primary-500 transition-colors"
                  type="button"
                  @click="resetPrescription"
                >
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  Reiniciar
                </button>
              </div>

              <!-- Mobile: cards por ojo -->
              <div class="sm:hidden space-y-3">
                <div
                  v-for="(eyeLabel, eyePrefix) in { od: 'OD — Ojo Derecho', oi: 'OI — Ojo Izquierdo' }"
                  :key="eyePrefix"
                  class="border-2 border-gray-100 rounded-2xl p-4 space-y-3"
                >
                  <p class="text-[9px] font-black text-gray-500 uppercase tracking-[0.2em]">{{ eyeLabel }}</p>
                  <div
                    v-for="field in tableFields"
                    :key="field.key"
                    class="flex items-center justify-between gap-3"
                  >
                    <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest whitespace-nowrap">{{ field.label }}</span>

                    <!-- Stepper -->
                    <div
                      v-if="field.step >= 1"
                      :class="['inline-flex items-center rounded-xl border-2 overflow-hidden transition-colors', isFilled(`${eyePrefix}_${field.key}`) ? 'border-primary-500' : 'border-gray-200']"
                    >
                      <button type="button" class="w-9 h-10 flex items-center justify-center font-black text-gray-500 hover:bg-gray-50 text-base" @click="stepField(`${eyePrefix}_${field.key}`, field, -1)">−</button>
                      <div class="w-10 h-10 flex items-center justify-center text-sm font-bold text-gray-900 border-x-2 border-inherit bg-white">{{ prescriptionValues[`${eyePrefix}_${field.key}`] ?? '—' }}</div>
                      <button type="button" class="w-9 h-10 flex items-center justify-center font-black text-gray-500 hover:bg-gray-50 text-base" @click="stepField(`${eyePrefix}_${field.key}`, field, 1)">+</button>
                    </div>

                    <!-- Picker trigger -->
                    <button
                      v-else
                      type="button"
                      :class="['h-10 px-3 border-2 rounded-xl text-sm font-bold focus:outline-none transition-colors flex items-center gap-2 min-w-[6rem] justify-between', isFilled(`${eyePrefix}_${field.key}`) ? 'border-primary-500 bg-primary-500/5 text-gray-900' : 'border-gray-200 text-gray-400']"
                      @click="openPicker(`${eyePrefix}_${field.key}`, field)"
                    >
                      <span>{{ pickerLabel(prescriptionValues[`${eyePrefix}_${field.key}`], field) }}</span>
                      <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                  </div>
                </div>
              </div>

              <!-- Desktop: tabla -->
              <div class="hidden sm:block overflow-x-auto">
                <table class="border-collapse mx-auto">
                  <thead>
                    <tr>
                      <th class="w-32 pb-3" />
                      <th
                        v-for="field in tableFields"
                        :key="field.key"
                        class="text-[9px] font-black text-gray-500 uppercase tracking-widest pb-3 text-center px-3 min-w-[7rem]"
                      >
                        {{ field.label }}
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr
                      v-for="(eye, eyePrefix) in { od: 'OD (Derecha)', oi: 'OI (Izquierda)' }"
                      :key="eyePrefix"
                      class="border-t border-gray-100"
                    >
                      <td class="text-[9px] font-black text-gray-500 uppercase tracking-widest py-4 pr-6 whitespace-nowrap">{{ eye }}</td>
                      <td v-for="field in tableFields" :key="field.key" class="py-4 px-3 text-center">

                        <!-- Stepper -->
                        <div
                          v-if="field.step >= 1"
                          :class="['inline-flex items-center rounded-xl border-2 overflow-hidden transition-colors', isFilled(`${eyePrefix}_${field.key}`) ? 'border-primary-500' : 'border-gray-200']"
                        >
                          <button type="button" class="w-8 h-10 flex items-center justify-center font-black text-gray-500 hover:bg-gray-50 text-base" @click="stepField(`${eyePrefix}_${field.key}`, field, -1)">−</button>
                          <div class="w-10 h-10 flex items-center justify-center text-sm font-bold text-gray-900 border-x-2 border-inherit bg-white">{{ prescriptionValues[`${eyePrefix}_${field.key}`] ?? '—' }}</div>
                          <button type="button" class="w-8 h-10 flex items-center justify-center font-black text-gray-500 hover:bg-gray-50 text-base" @click="stepField(`${eyePrefix}_${field.key}`, field, 1)">+</button>
                        </div>

                        <!-- Picker trigger -->
                        <button
                          v-else
                          type="button"
                          :class="['h-10 px-3 border-2 rounded-xl text-sm font-bold focus:outline-none transition-colors flex items-center gap-2 min-w-[5.5rem] justify-between', isFilled(`${eyePrefix}_${field.key}`) ? 'border-primary-500 bg-primary-500/5 text-gray-900' : 'border-gray-200 text-gray-400 hover:border-gray-300']"
                          @click="openPicker(`${eyePrefix}_${field.key}`, field)"
                        >
                          <span>{{ pickerLabel(prescriptionValues[`${eyePrefix}_${field.key}`], field) }}</span>
                          <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- DP / PD -->
              <div v-if="pdField" class="mt-5 pt-5 border-t border-gray-100 space-y-3">

                <!-- Fila principal: label + control(s) -->
                <div class="flex flex-wrap items-center gap-3">
                  <span class="text-[9px] font-black text-gray-500 uppercase tracking-widest w-10">{{ pdField.label }}</span>

                  <template v-if="!doublePd">
                    <button
                      type="button"
                      :class="['h-10 px-3 border-2 rounded-xl text-sm font-bold focus:outline-none transition-colors flex items-center gap-2 min-w-[5.5rem] justify-between', isFilled('pd') ? 'border-primary-500 bg-primary-500/5 text-gray-900' : 'border-gray-200 text-gray-400 hover:border-gray-300']"
                      @click="openPicker('pd', pdField)"
                    >
                      <span>{{ pickerLabel(prescriptionValues['pd'], pdField) }}</span>
                      <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                  </template>
                  <template v-else>
                    <div class="flex items-center gap-2">
                      <span class="text-[8px] text-gray-400 font-black uppercase tracking-widest">OD</span>
                      <button
                        type="button"
                        :class="['h-10 px-3 border-2 rounded-xl text-sm font-bold focus:outline-none transition-colors flex items-center gap-2 min-w-[5rem] justify-between', isFilled('pd_od') ? 'border-primary-500 bg-primary-500/5 text-gray-900' : 'border-gray-200 text-gray-400 hover:border-gray-300']"
                        @click="openPicker('pd_od', pdField)"
                      >
                        <span>{{ pickerLabel(prescriptionValues['pd_od'], pdField) }}</span>
                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                      </button>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="text-[8px] text-gray-400 font-black uppercase tracking-widest">OI</span>
                      <button
                        type="button"
                        :class="['h-10 px-3 border-2 rounded-xl text-sm font-bold focus:outline-none transition-colors flex items-center gap-2 min-w-[5rem] justify-between', isFilled('pd_oi') ? 'border-primary-500 bg-primary-500/5 text-gray-900' : 'border-gray-200 text-gray-400 hover:border-gray-300']"
                        @click="openPicker('pd_oi', pdField)"
                      >
                        <span>{{ pickerLabel(prescriptionValues['pd_oi'], pdField) }}</span>
                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7" /></svg>
                      </button>
                    </div>
                  </template>
                </div>

                <!-- Checkbox siempre en su propia línea -->
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" :checked="doublePd" class="w-3.5 h-3.5 rounded border-gray-300 accent-primary-500 cursor-pointer" @change="toggleDoublePd" />
                  <span class="text-[9px] font-bold text-gray-500 uppercase tracking-widest select-none">Dos números de DP</span>
                </label>
              </div>

              <div class="flex justify-center mt-8">
                <AppButton
                  variant="secondary"
                  size="lg"
                  :disabled="!prescriptionComplete"
                  @click="advancePrescription"
                >
                  Continuar
                </AppButton>
              </div>
            </div>

            <!-- Step 2 (sin receta) / Step 3 (con receta): Elegir tipo de lente -->
            <div v-else-if="configuratorStep === typeStep">
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

            <!-- Step 3 (sin receta) / Step 4 (con receta): Elegir cristal -->
            <div v-else-if="configuratorStep === crystalStep">
              <h2 class="text-xl font-black uppercase tracking-tight text-center mb-2">Elegí tu cristal</h2>
              <p class="text-[9px] text-gray-400 font-bold uppercase tracking-widest text-center mb-8">Seleccioná el tipo de cristal</p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <button
                  v-for="crystal in availableCrystals"
                  :key="crystal.configuration_id"
                  :class="selectedCrystal?.configuration_id === crystal.configuration_id
                    ? 'border-primary-500 bg-primary-500/5'
                    : 'border-gray-200 hover:border-gray-300'"
                  class="flex flex-col gap-3 p-5 border-2 rounded-2xl transition-all duration-200 text-left"
                  @click="selectedCrystal = crystal"
                >
                  <span class="text-sm font-black uppercase tracking-wider text-gray-900">{{ crystal.name }}</span>
                  <span class="text-lg font-black text-gray-900 mt-1">{{ formatPrice(crystal.effective_price) }}</span>
                </button>
              </div>

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
            <div v-if="selectedCrystal" class="text-right">
              <p class="text-[9px] text-gray-400 uppercase tracking-widest font-bold">Cristal seleccionado</p>
              <p class="text-[9px] font-black uppercase tracking-wider text-gray-900">{{ selectedCrystal.name }}</p>
              <p class="text-xs font-bold text-primary-500">{{ formatPrice(selectedCrystal.effective_price) }}</p>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Picker de valores decimales para receta -->
    <Teleport to="body">
      <div
        v-if="activePicker"
        class="fixed inset-0 z-[60] flex items-end sm:items-center justify-center p-0 sm:p-4"
      >
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closePicker" />

        <div class="relative bg-white w-full sm:max-w-sm rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">

          <!-- Header -->
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h3 class="text-[9px] font-black uppercase tracking-[0.2em] text-gray-900">
              {{ activePicker.field.label }}
            </h3>
            <button
              type="button"
              class="text-gray-400 hover:text-gray-700 transition-colors"
              @click="closePicker"
            >
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Valores en columnas -->
          <div class="flex gap-3 p-4 max-h-[60vh] sm:max-h-80">

            <!-- Negativos — solo si existen -->
            <div v-if="pickerNegative.length" class="flex-1 flex flex-col gap-2 min-w-0">
              <p class="text-[8px] font-black uppercase tracking-widest text-gray-400 text-center">(-) Negativo</p>
              <div class="overflow-y-auto flex-1 space-y-1 pr-1">
                <button
                  v-for="opt in pickerNegative"
                  :key="opt.value"
                  type="button"
                  :class="[
                    'w-full py-2 rounded-xl text-sm font-bold text-center transition-colors',
                    prescriptionValues[activePicker.key] === opt.value
                      ? 'bg-primary-500 text-white'
                      : 'bg-gray-50 text-gray-700 hover:bg-gray-100 active:bg-gray-200',
                  ]"
                  @click="pickValue(opt.value)"
                >{{ opt.label }}</button>
              </div>
            </div>

            <!-- Positivos (incluye Plano) -->
            <div class="flex-1 flex flex-col gap-2 min-w-0">
              <p v-if="pickerNegative.length" class="text-[8px] font-black uppercase tracking-widest text-gray-400 text-center">(+) Positivo</p>
              <div class="overflow-y-auto flex-1 space-y-1" :class="pickerNegative.length ? 'pl-1' : ''">
                <button
                  v-for="opt in pickerPositive"
                  :key="opt.value"
                  type="button"
                  :class="[
                    'w-full py-2 rounded-xl text-sm font-bold text-center transition-colors',
                    prescriptionValues[activePicker.key] === opt.value
                      ? 'bg-primary-500 text-white'
                      : 'bg-gray-50 text-gray-700 hover:bg-gray-100 active:bg-gray-200',
                  ]"
                  @click="pickValue(opt.value)"
                >{{ opt.label }}</button>
              </div>
            </div>

          </div>
        </div>
      </div>
    </Teleport>
  </section>
</template>
