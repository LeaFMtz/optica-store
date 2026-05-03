<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'
import ProductCard from '@/Components/ProductCard.vue'
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'

defineOptions({ layout: StorefrontLayout })

const props = defineProps({
  results: { type: Array, default: () => [] },
  query: { type: String, default: '' },
})

const searchInput = ref(props.query)

function submitSearch() {
  router.get(route('search.view'), { q: searchInput.value }, { preserveState: true })
}
</script>

<template>
  <section class="bg-gray-50 min-h-screen">
    <div class="max-w-screen-xl px-4 py-12 mx-auto sm:px-6 lg:px-8">

      <!-- Header -->
      <div class="mb-10">
        <nav class="text-[10px] text-gray-400 uppercase tracking-widest flex items-center gap-2 mb-4">
          <a :href="route('home')" class="hover:text-primary-500">Inicio</a>
          <span>/</span>
          <span class="text-gray-900 font-bold">Búsqueda</span>
        </nav>

        <h1 class="text-3xl font-black text-gray-900 uppercase tracking-tight mb-6">
          Resultados de búsqueda
          <span v-if="query" class="text-primary-500">"{{ query }}"</span>
        </h1>

        <!-- Search bar -->
        <form class="flex gap-3 max-w-lg" @submit.prevent="submitSearch">
          <input
            v-model="searchInput"
            type="search"
            placeholder="Buscar productos..."
            class="flex-1 px-4 py-3 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none transition bg-white"
          >
          <button
            type="submit"
            class="px-6 py-3 bg-black text-white text-xs font-bold uppercase tracking-widest rounded-xl hover:bg-primary-500 transition"
          >
            Buscar
          </button>
        </form>
      </div>

      <!-- Results count -->
      <div
        v-if="query"
        class="mb-8 text-sm text-gray-500 pb-6 border-b border-gray-100"
      >
        <span v-if="results.length">
          <span class="text-gray-900 font-bold">{{ results.length }}</span> resultados para
          <span class="font-bold text-gray-900">"{{ query }}"</span>
        </span>
        <span v-else>
          No encontramos resultados para
          <span class="font-bold text-gray-900">"{{ query }}"</span>
        </span>
      </div>

      <!-- Product grid -->
      <div
        v-if="results.length"
        class="grid grid-cols-2 gap-x-4 gap-y-12 lg:grid-cols-4 lg:gap-x-8 lg:gap-y-16"
      >
        <ProductCard
          v-for="product in results"
          :key="product.id"
          :product="product"
        />
      </div>

      <!-- Empty state (only if a query was entered) -->
      <div
        v-else-if="query"
        class="py-20 text-center bg-white rounded-3xl border border-dashed border-gray-200 shadow-sm"
      >
        <p class="text-gray-500 font-medium italic">No hay productos que coincidan con tu búsqueda.</p>
        <a
          :href="route('catalog.view')"
          class="mt-6 inline-block px-6 py-3 bg-black text-white text-xs font-bold uppercase tracking-widest rounded-full hover:bg-primary-500 transition"
        >
          Ver todo el catálogo
        </a>
      </div>

      <!-- No query yet -->
      <div
        v-else
        class="py-20 text-center"
      >
        <p class="text-gray-400 font-medium">Ingresá un término para buscar productos.</p>
      </div>

    </div>
  </section>
</template>
