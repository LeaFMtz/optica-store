<script setup>
import { computed } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    required: true,
  },
  label: {
    type: String,
    default: null,
  },
  placeholder: {
    type: String,
    default: '',
  },
  type: {
    type: String,
    default: 'text',
  },
  error: {
    type: String,
    default: null,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  id: {
    type: String,
    default: () => `input-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`,
  },
})

const emit = defineEmits(['update:modelValue'])

const inputClasses = computed(() => [
  'w-full rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:border-transparent transition-colors',
  'disabled:bg-gray-50 disabled:text-gray-500 disabled:cursor-not-allowed',
  props.error
    ? 'border border-red-500 focus:ring-red-500'
    : 'border border-gray-300 focus:ring-primary-500',
])
</script>

<template>
  <div>
    <label
      v-if="label"
      :for="id"
      class="block mb-1.5 text-sm font-medium text-gray-700"
    >
      {{ label }}
    </label>
    <input
      :id="id"
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :disabled="disabled"
      :class="inputClasses"
      @input="emit('update:modelValue', $event.target.value)"
    />
    <p
      v-if="error"
      class="mt-1 text-sm text-red-600"
    >
      {{ error }}
    </p>
  </div>
</template>
