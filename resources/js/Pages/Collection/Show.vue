<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'
import ProductCard from '@/Components/ProductCard.vue'

defineOptions({ layout: StorefrontLayout })

defineProps({
  collection: { type: Object, required: true },
  products: { type: Array, default: () => [] },
})
</script>

<template>
  <section class="bg-gray-50/50 min-h-screen">
    <div class="max-w-screen-2xl px-4 py-12 mx-auto sm:px-6 lg:px-8">

      <!-- Collection header -->
      <div class="flex flex-col md:flex-row md:items-end md:justify-between mb-12 gap-6">
        <div class="space-y-2">
          <nav class="text-[10px] text-gray-400 uppercase tracking-widest flex items-center gap-2 mb-4">
            <a :href="route('home')" class="hover:text-[#71C229]">Inicio</a>
            <span>/</span>
            <span class="text-gray-900 font-bold">Colecciones</span>
          </nav>
          <h1 class="text-4xl font-black text-gray-900 leading-tight uppercase tracking-tighter">
            {{ collection.name }}
          </h1>
          <p class="text-gray-500 text-sm max-w-2xl">
            Descubrí nuestra exclusiva selección de productos diseñados para tu salud visual y estilo.
          </p>
        </div>

        <div class="bg-white px-6 py-3 rounded-full border border-gray-100 shadow-sm">
          <span class="text-sm text-gray-400 font-medium">Mostrando</span>
          <span class="text-sm text-gray-900 font-black ml-1">{{ collection.product_count }} Productos</span>
        </div>
      </div>

      <!-- Product grid -->
      <div class="grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        <template v-if="products.length">
          <ProductCard
            v-for="product in products"
            :key="product.id"
            :product="product"
          />
        </template>

        <!-- Empty state -->
        <div
          v-else
          class="col-span-full py-20 text-center"
        >
          <div class="w-20 h-20 bg-gray-100 text-gray-300 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-gray-900">No encontramos productos</h3>
          <p class="text-gray-500 mt-2">Estamos trabajando para traer más opciones a esta colección pronto.</p>
        </div>
      </div>
    </div>
  </section>
</template>
