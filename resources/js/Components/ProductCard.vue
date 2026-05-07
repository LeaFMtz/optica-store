<script setup>
defineProps({
  product: {
    type: Object,
    required: true,
  },
})

const colorHex = {
  'Negro': '#1a1a1a',
  'Negro Mate': '#3a3a3a',
  'Blanco': '#f0f0f0',
  'Gris': '#9ca3af',
  'Dorado': '#c9a84c',
  'Plateado': '#b0b7c3',
  'Marrón Havana': '#7a4a2a',
  'Borgoña': '#7d1a27',
  'Azul': '#2563eb',
  'Rosa': '#f472b6',
  'Lila': '#a78bfa',
  'Rojo': '#ef4444',
  'Verde': '#22c55e',
  'Naranja': '#f97316',
  'Beige': '#d4b183',
}
</script>

<template>
  <article
    class="group relative bg-white rounded-2xl border border-gray-200 shadow-md hover:shadow-xl hover:shadow-primary-500/10 hover:-translate-y-1 transition-all duration-500 flex flex-col h-full overflow-hidden"
    itemscope
    itemtype="http://schema.org/Product"
  >
    <!-- Discount badge -->
    <div v-if="product.discount_percentage > 0" class="absolute top-3 left-3 z-20 flex flex-col gap-1">
      <span class="px-2.5 py-1 text-[10px] font-black tracking-widest text-white uppercase bg-primary-500 rounded-lg shadow-lg shadow-primary-500/30">
        −{{ product.discount_percentage }}%
      </span>
      <span class="px-2 py-0.5 text-[8px] font-black tracking-widest text-primary-600 uppercase bg-primary-50 rounded-md">
        OFERTA
      </span>
    </div>

    <!-- Out of stock badge -->
    <div v-if="product.in_stock === false" class="absolute top-4 right-4 z-20">
      <span class="px-2.5 py-1 text-[9px] font-black tracking-widest text-white uppercase bg-gray-700 rounded-lg shadow-lg">
        Sin Stock
      </span>
    </div>

    <!-- Product link -->
    <a
      :href="product.slug ? `/products/${product.slug}` : '#'"
      class="block flex-1 flex flex-col group/card"
    >
      <!-- Image -->
      <div class="aspect-[4/5] overflow-hidden bg-gray-50 relative">
        <img
          v-if="product.thumbnail_url"
          :src="product.thumbnail_url"
          :alt="product.name"
          class="w-full h-full object-cover transition-transform duration-700 group-hover/card:scale-105"
          itemprop="image"
        >
        <div
          v-else
          class="w-full h-full flex items-center justify-center text-gray-200"
        >
          <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
        </div>

        <!-- Hover overlay -->
        <div class="absolute inset-0 bg-black/0 group-hover/card:bg-black/5 transition-colors duration-500" />
      </div>

      <!-- Product info -->
      <div class="p-6 flex flex-col flex-1">
        <div class="mb-4 flex-1">
          <div class="flex items-center gap-1.5 mb-2">
            <span class="text-[9px] font-black text-primary-500 uppercase tracking-[0.2em]">Premium Eyewear</span>
          </div>
          <h3
            class="text-xs font-bold text-gray-900 line-clamp-2 tracking-tight leading-relaxed min-h-[2.5rem]"
            itemprop="name"
          >
            {{ product.name }}
          </h3>
        </div>

        <!-- Color swatches -->
        <div v-if="product.colors && product.colors.length" class="flex items-center gap-1.5 mb-4">
          <template v-for="(color, i) in product.colors.slice(0, 6)" :key="i">
            <span
              v-if="color !== 'Transparente'"
              :style="{ backgroundColor: colorHex[color] ?? '#ccc' }"
              :title="color"
              class="w-3.5 h-3.5 rounded-full border border-gray-200 shadow-sm flex-shrink-0"
            />
            <span
              v-else
              :title="color"
              class="w-3.5 h-3.5 rounded-full border-2 border-dashed border-gray-300 flex-shrink-0"
            />
          </template>
          <span v-if="product.colors.length > 6" class="text-[8px] font-black text-gray-400 uppercase tracking-widest">
            +{{ product.colors.length - 6 }}
          </span>
        </div>

        <div
          class="flex flex-col pt-4 border-t border-gray-50"
          itemprop="offers"
          itemscope
          itemtype="http://schema.org/Offer"
        >
          <div class="flex flex-col gap-1">
            <div class="flex items-baseline gap-2">
              <span class="text-lg font-black text-primary-500">{{ product.price_formatted }}</span>
              <span v-if="product.base_price_formatted" class="text-[10px] text-gray-400 line-through">
                {{ product.base_price_formatted }}
              </span>
            </div>
            <span v-if="product.savings_formatted" class="text-[8px] font-black text-primary-600 uppercase tracking-widest">
              Ahorrás {{ product.savings_formatted }}
            </span>
          </div>
          <meta itemprop="priceCurrency" content="ARS">
        </div>
      </div>
    </a>
  </article>
</template>
