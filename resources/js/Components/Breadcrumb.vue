<script setup>
defineProps({
  items: {
    type: Array,
    required: true,
  },
})
</script>

<template>
  <nav aria-label="Breadcrumb">
    <ol class="flex items-center gap-2 text-sm">
      <li
        v-for="(item, index) in items"
        :key="index"
        class="flex items-center gap-2"
      >
        <span v-if="index > 0" class="text-gray-400">›</span>
        <a
          v-if="item.href"
          :href="item.href"
          class="text-gray-500 hover:text-gray-700"
        >{{ item.label }}</a>
        <span
          v-else
          class="text-gray-900 font-medium"
        >{{ item.label }}</span>
      </li>
    </ol>
  </nav>
</template>
