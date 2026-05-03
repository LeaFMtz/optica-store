<script setup>
/**
 * Banner component for bento-grid sections (middle and bottom banners).
 * Accepts a single banner object and layout hints.
 */
defineProps({
  banner: {
    type: Object,
    required: true,
  },
  /** Tailwind col-span class applied to the wrapper, e.g. 'md:col-span-2' */
  colSpanClass: {
    type: String,
    default: 'md:col-span-1',
  },
  /** Tailwind aspect-ratio class applied to the wrapper, e.g. 'aspect-[4/3]' */
  aspectClass: {
    type: String,
    default: 'aspect-[4/3]',
  },
})
</script>

<template>
  <a
    :href="banner.url ?? '#'"
    :class="['block group relative rounded-2xl overflow-hidden shadow-sm hover:shadow-2xl hover:-translate-y-1 transition-all duration-500 bg-gray-50 border border-gray-100', colSpanClass, aspectClass]"
  >
    <picture class="block w-full h-full">
      <source
        media="(max-width: 767px)"
        :srcset="banner.mobile_image_url || banner.image_url"
      >
      <img
        :src="banner.image_url"
        :alt="banner.title"
        class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
      >
    </picture>
    <div class="absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-colors duration-500" />
  </a>
</template>
