<script setup>
const props = defineProps({
  variant: {
    type: String,
    default: 'primary',
    validator: (v) => ['primary', 'secondary', 'outline'].includes(v),
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v),
  },
  href: {
    type: String,
    default: null,
  },
  type: {
    type: String,
    default: 'button',
    validator: (v) => ['button', 'submit', 'reset'].includes(v),
  },
  disabled: {
    type: Boolean,
    default: false,
  },
})

const variantClasses = {
  primary: 'bg-primary-500 text-white hover:bg-primary-600 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
  secondary: 'bg-black text-white hover:bg-gray-800 focus:ring-2 focus:ring-black focus:ring-offset-2',
  outline: 'border border-primary-500 text-primary-500 hover:bg-primary-50 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
}

const sizeClasses = {
  sm: 'px-3 py-1.5 text-sm',
  md: 'px-5 py-2.5 text-sm',
  lg: 'px-8 py-3 text-base',
}

const baseClasses = 'inline-flex items-center justify-center font-medium rounded-lg transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed'
</script>

<template>
  <a
    v-if="href"
    :href="href"
    :class="[baseClasses, variantClasses[variant], sizeClasses[size]]"
  >
    <slot />
  </a>
  <button
    v-else
    :type="type"
    :disabled="disabled"
    :class="[baseClasses, variantClasses[variant], sizeClasses[size]]"
  >
    <slot />
  </button>
</template>
