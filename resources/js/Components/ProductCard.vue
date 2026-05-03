<script setup>
defineProps({
  product: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <article
    class="group relative bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-2xl hover:-translate-y-1 transition-all duration-500 flex flex-col h-full overflow-hidden"
    itemscope
    itemtype="http://schema.org/Product"
  >
    <!-- Discount badge -->
    <div v-if="product.discount_percentage > 0" class="absolute top-4 left-4 z-20">
      <span class="px-2.5 py-1 text-[9px] font-black tracking-widest text-white uppercase bg-primary-500 rounded-lg shadow-lg">
        {{ product.discount_percentage }}% OFF
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
          <span class="text-[9px] font-black text-primary-500 uppercase tracking-[0.2em] mb-2 block">
            Premium Eyewear
          </span>
          <h3
            class="text-xs font-bold text-gray-900 line-clamp-2 tracking-tight leading-relaxed min-h-[2.5rem]"
            itemprop="name"
          >
            {{ product.name }}
          </h3>
        </div>

        <div
          class="flex flex-col pt-4 border-t border-gray-50"
          itemprop="offers"
          itemscope
          itemtype="http://schema.org/Offer"
        >
          <div class="flex sm:flex-row sm:items-baseline gap-1 sm:gap-2">
            <span class="text-lg font-black text-primary-500">{{ product.price_formatted }}</span>
            <span v-if="product.base_price_formatted" class="text-xs text-gray-400 line-through">
              {{ product.base_price_formatted }}
            </span>
          </div>
          <meta itemprop="priceCurrency" content="ARS">
        </div>
      </div>
    </a>
  </article>
</template>
