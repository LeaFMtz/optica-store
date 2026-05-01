<script setup>
import { computed } from 'vue'

const props = defineProps({
  variant: {
    type: String,
    default: 'info',
    validator: (v) => ['primary', 'success', 'warning', 'error', 'info'].includes(v),
  },
})

const variantClasses = {
  primary: 'bg-primary-100 text-primary-700',
  success: 'bg-green-100 text-green-700',
  warning: 'bg-yellow-100 text-yellow-700',
  error: 'bg-red-100 text-red-700',
  info: 'bg-gray-100 text-gray-700',
}

const baseClasses = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium'

const classes = computed(() => [baseClasses, variantClasses[props.variant]])
</script>

<template>
  <span :class="classes">
    <slot />
  </span>
</template>
