<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'
import Banner from '@/Components/Banner.vue'
import ProductCard from '@/Components/ProductCard.vue'
import AppButton from '@/Components/AppButton.vue'
import { ref, onMounted, onUnmounted } from 'vue'

defineOptions({ layout: StorefrontLayout })

const props = defineProps({
  heroBanners:             { type: Array,  default: () => [] },
  middleBanners:           { type: Array,  default: () => [] },
  bottomBanners:           { type: Array,  default: () => [] },
  newsletterBanner:        { type: Object, default: null },
  featuredProducts:        { type: Array,  default: () => [] },
  offerProducts:           { type: Array,  default: () => [] },
  randomCollectionName:    { type: String, default: null },
  randomCollectionSlug:    { type: String, default: null },
  randomCollectionProducts:{ type: Array,  default: () => [] },
})

// ── Hero carousel ────────────────────────────────────────────────────────────
const currentSlide = ref(0)
let intervalId = null

function showSlide(index) { currentSlide.value = index }
function changeSlide(direction) {
  showSlide((currentSlide.value + direction + props.heroBanners.length) % props.heroBanners.length)
  resetInterval()
}
function goToSlide(index) { showSlide(index); resetInterval() }
function resetInterval() {
  if (intervalId) clearInterval(intervalId)
  if (props.heroBanners.length > 1)
    intervalId = setInterval(() => showSlide((currentSlide.value + 1) % props.heroBanners.length), 8000)
}

onMounted(() => {
  if (props.heroBanners.length > 1)
    intervalId = setInterval(() => showSlide((currentSlide.value + 1) % props.heroBanners.length), 8000)
})
onUnmounted(() => { if (intervalId) clearInterval(intervalId) })

// ── Scroll helpers for product carousels ────────────────────────────────────
const featuredCarouselRef = ref(null)
const offerCarouselRef    = ref(null)

function scrollCarousel(elRef, direction) {
  if (!elRef.value) return
  const card = elRef.value.querySelector('article')
  if (!card) return
  elRef.value.scrollBy({ left: (card.offsetWidth + 16) * 2 * direction, behavior: 'smooth' })
}

// ── Middle banner layout helpers ─────────────────────────────────────────────
function middleBannerClasses() {
  const count = props.middleBanners.length
  if (count === 1)  return 'md:grid-cols-1'
  if (count === 2)  return 'md:grid-cols-2'
  return 'md:grid-cols-3'
}

// ── Bottom banner layout helpers ─────────────────────────────────────────────
function bottomBannerClasses(index) {
  const count = props.bottomBanners.length
  if (count === 1) return { col: 'md:col-span-3', aspect: 'aspect-[3/1] lg:aspect-[4/1]' }
  if (count >= 3)
    return index === 0
      ? { col: 'md:col-span-2', aspect: 'aspect-[2/1] lg:aspect-[2.5/1]' }
      : { col: 'md:col-span-1', aspect: 'aspect-[4/3] lg:aspect-[5/4]' }
  return { col: '', aspect: 'aspect-[2/1] lg:aspect-[2.5/1]' }
}
</script>

<template>
  <div>

    <!-- 1 ── Hero carousel ───────────────────────────────────────────────── -->
    <div
      v-if="heroBanners.length"
      class="relative w-full h-[500px] lg:h-[700px] overflow-hidden"
    >
      <div class="relative w-full h-full">
        <div
          v-for="(banner, index) in heroBanners"
          :key="banner.id"
          class="hero-slide absolute inset-0 w-full h-full transition-opacity duration-500"
          :style="{ opacity: index === currentSlide ? 1 : 0 }"
        >
          <a :href="banner.url" class="block w-full h-full">
            <picture class="block w-full h-full">
              <source media="(max-width: 767px)" :srcset="banner.mobile_image_url || banner.image_url">
              <img :src="banner.image_url" :alt="banner.title" class="w-full h-full object-cover">
            </picture>
          </a>
        </div>
      </div>

      <template v-if="heroBanners.length > 1">
        <button
          class="absolute left-4 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-3 rounded-full transition-colors duration-200 z-10"
          aria-label="Banner anterior"
          @click="changeSlide(-1)"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <button
          class="absolute right-4 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-3 rounded-full transition-colors duration-200 z-10"
          aria-label="Siguiente banner"
          @click="changeSlide(1)"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-10">
          <button
            v-for="(banner, index) in heroBanners"
            :key="banner.id"
            :class="['w-3 h-3 rounded-full transition-all duration-200', index === currentSlide ? 'bg-white scale-110' : 'bg-white/50 hover:bg-white/75']"
            :aria-label="`Ir al banner ${index + 1}`"
            @click="goToSlide(index)"
          />
        </div>
      </template>
    </div>

    <div class="max-w-screen-xl px-4 py-12 mx-auto space-y-16 sm:px-6 lg:px-8">

      <!-- 2 ── Destacados carousel ─────────────────────────────────────────── -->
      <section v-if="featuredProducts.length">
        <div class="flex items-end justify-between mb-8 px-2">
          <h2 class="text-3xl font-black tracking-tighter uppercase text-black">
            Destacados
          </h2>
          <div class="flex gap-2">
            <button
              class="p-2 border-2 border-black rounded-full hover:bg-black hover:text-white transition-all active:scale-90"
              aria-label="Anterior destacados"
              @click="scrollCarousel(featuredCarouselRef, -1)"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <button
              class="p-2 border-2 border-black rounded-full hover:bg-black hover:text-white transition-all active:scale-90"
              aria-label="Siguiente destacados"
              @click="scrollCarousel(featuredCarouselRef, 1)"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>

        <div class="relative group">
          <div
            ref="featuredCarouselRef"
            class="flex gap-4 pb-8 overflow-x-auto snap-x snap-mandatory scroll-smooth"
            style="-ms-overflow-style: none; scrollbar-width: none;"
          >
            <div
              v-for="product in featuredProducts"
              :key="product.id"
              class="flex-none w-[280px] sm:w-[320px] snap-start"
            >
              <ProductCard :product="product" />
            </div>
          </div>
          <div class="absolute inset-y-0 left-0 w-8 bg-gradient-to-r from-white/50 to-transparent pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity" />
          <div class="absolute inset-y-0 right-0 w-8 bg-gradient-to-l from-white/50 to-transparent pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity" />
        </div>
      </section>

      <!-- 3 ── Beneficios ──────────────────────────────────────────────────── -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 py-10 border-t border-b border-gray-100">
        <div class="flex flex-col items-center text-center gap-3">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0M3 7h2l2.4 9.6M3 7H1m2 0l1 4h13l1-4H3zm4 9.6L7 16h10" />
          </svg>
          <div>
            <p class="font-black text-sm uppercase tracking-widest text-gray-900">Envíos</p>
            <p class="text-xs text-gray-500 mt-1 max-w-[180px]">Envíos GRATIS a todo el País, retiros en sucursal Tucumán</p>
          </div>
        </div>
        <div class="flex flex-col items-center text-center gap-3">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
          </svg>
          <div>
            <p class="font-black text-sm uppercase tracking-widest text-gray-900">Cuotas sin interés</p>
            <p class="text-xs text-gray-500 mt-1 max-w-[200px]">6 Cuotas sin interés con tarjetas de crédito y 4 Cuotas débito con Go Cuotas</p>
          </div>
        </div>
        <div class="flex flex-col items-center text-center gap-3">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
          <div>
            <p class="font-black text-sm uppercase tracking-widest text-gray-900">Compra segura</p>
            <p class="text-xs text-gray-500 mt-1 max-w-[180px]">Compra segura, todos tus datos están protegidos</p>
          </div>
        </div>
      </div>

      <!-- 4 ── Ofertas carousel ────────────────────────────────────────────── -->
      <section v-if="offerProducts.length" class="border-t border-gray-50 pt-4">
        <div class="flex items-end justify-between mb-8 px-2">
          <h2 class="text-3xl font-black tracking-tighter uppercase text-black">
            ¡OFERTAS!
          </h2>
          <div class="flex gap-2">
            <button
              class="p-2 border-2 border-black rounded-full hover:bg-black hover:text-white transition-all active:scale-90"
              aria-label="Anterior ofertas"
              @click="scrollCarousel(offerCarouselRef, -1)"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
              </svg>
            </button>
            <button
              class="p-2 border-2 border-black rounded-full hover:bg-black hover:text-white transition-all active:scale-90"
              aria-label="Siguiente ofertas"
              @click="scrollCarousel(offerCarouselRef, 1)"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </button>
          </div>
        </div>

        <div class="relative group">
          <div
            ref="offerCarouselRef"
            class="flex gap-4 pb-8 overflow-x-auto snap-x snap-mandatory scroll-smooth"
            style="-ms-overflow-style: none; scrollbar-width: none;"
          >
            <div
              v-for="product in offerProducts"
              :key="product.id"
              class="flex-none w-[280px] sm:w-[320px] snap-start"
            >
              <ProductCard :product="product" />
            </div>
          </div>
          <div class="absolute inset-y-0 left-0 w-8 bg-gradient-to-r from-white/50 to-transparent pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity" />
          <div class="absolute inset-y-0 right-0 w-8 bg-gradient-to-l from-white/50 to-transparent pointer-events-none opacity-0 group-hover:opacity-100 transition-opacity" />
        </div>
      </section>

      <!-- 5 ── Banners medios ──────────────────────────────────────────────── -->
      <div
        v-if="middleBanners.length"
        :class="['grid grid-cols-1 gap-4', middleBannerClasses()]"
      >
        <a
          v-for="banner in middleBanners"
          :key="banner.id"
          :href="banner.url ?? '#'"
          class="block relative overflow-hidden aspect-[2/1]"
        >
          <picture class="block w-full h-full">
            <source media="(max-width: 767px)" :srcset="banner.mobile_image_url || banner.image_url">
            <img :src="banner.image_url" :alt="banner.title" class="w-full h-full object-cover">
          </picture>
        </a>
      </div>

      <!-- 6 ── Categoría aleatoria ─────────────────────────────────────────── -->
      <section v-if="randomCollectionProducts.length" class="pt-8 border-t border-gray-50">
        <div class="flex items-end justify-between mb-10">
          <h2 class="text-3xl font-black tracking-tighter uppercase text-black italic">
            {{ randomCollectionName }}
            <span class="block text-[10px] font-black text-primary-500 uppercase tracking-[0.3em] mt-2 not-italic">
              Selección exclusiva para vos
            </span>
          </h2>
          <AppButton
            v-if="randomCollectionSlug"
            :href="`/collections/${randomCollectionSlug}`"
            variant="outline"
            size="sm"
          >
            Ver todas
          </AppButton>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
          <ProductCard
            v-for="product in randomCollectionProducts"
            :key="product.id"
            :product="product"
          />
        </div>
      </section>

      <!-- Bottom banners (posición home_bottom — sin datos en seed base) -->
      <template v-if="bottomBanners.length">
        <div
          v-if="bottomBanners.length === 2"
          class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-12 border-t border-gray-50"
        >
          <Banner
            v-for="banner in bottomBanners"
            :key="banner.id"
            :banner="banner"
            col-span-class=""
            aspect-class="aspect-[2/1] lg:aspect-[2.5/1]"
          />
        </div>
        <div
          v-else
          class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-12 border-t border-gray-50"
        >
          <Banner
            v-for="(banner, index) in bottomBanners"
            :key="banner.id"
            :banner="banner"
            :col-span-class="bottomBannerClasses(index).col"
            :aspect-class="bottomBannerClasses(index).aspect"
          />
        </div>
      </template>

    </div>

    <!-- 7 ── Newsletter (full bleed) ─────────────────────────────────────── -->
    <div
      v-if="newsletterBanner"
      class="relative w-full h-[420px] overflow-hidden"
    >
      <div class="absolute inset-0">
        <picture class="block w-full h-full">
          <source media="(max-width: 767px)" :srcset="newsletterBanner.mobile_image_url || newsletterBanner.image_url">
          <img :src="newsletterBanner.image_url" :alt="newsletterBanner.title" class="w-full h-full object-cover">
        </picture>
      </div>
      <div class="absolute inset-0 bg-black/55" />
      <div class="relative h-full max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center justify-end">
        <div class="w-full max-w-sm text-white text-center">
          <h2 class="text-2xl font-black uppercase tracking-widest mb-2">RECIBÍ NOVEDADES</h2>
          <p class="text-sm text-white/80 mb-6 leading-relaxed">¡Suscribite al Newsletter para acceder a beneficios y lanzamientos exclusivos!</p>
          <form class="space-y-3" @submit.prevent>
            <input
              type="email"
              placeholder="Email"
              class="w-full px-4 py-3 bg-transparent border border-white/40 text-white placeholder-white/55 focus:outline-none focus:border-white transition"
            >
            <button
              type="submit"
              class="w-full py-3 bg-white text-primary-500 font-semibold rounded-full hover:bg-gray-50 transition"
            >
              Enviar
            </button>
          </form>
        </div>
      </div>
    </div>

  </div>
</template>
