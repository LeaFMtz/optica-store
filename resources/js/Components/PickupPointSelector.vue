<script setup>
/**
 * PickupPointSelector — v-model compatible radio list for Zipnova pickup points.
 *
 * Props:
 *   points      — array of pickup point objects from the Zipnova quote response
 *   modelValue  — currently selected point_id (Number|null)
 *
 * Emits:
 *   update:modelValue — emitted with the selected point_id when the user picks a point
 */
const props = defineProps({
  points: {
    type: Array,
    default: () => [],
  },
  modelValue: {
    type: Number,
    default: null,
  },
})

const emit = defineEmits(['update:modelValue'])

function select(pointId) {
  emit('update:modelValue', pointId)
}
</script>

<template>
  <div v-if="points.length > 0">
    <p class="text-[10px] font-bold text-gray-600 mb-2 uppercase tracking-widest">
      Seleccioná un punto de retiro
    </p>
    <div class="space-y-2">
      <label
        v-for="point in points"
        :key="point.point_id"
        class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-all duration-200"
        :class="modelValue === point.point_id ? 'border-primary-500 bg-primary-50' : 'border-gray-200'"
      >
        <input
          type="radio"
          :value="point.point_id"
          :checked="modelValue === point.point_id"
          class="mt-0.5 accent-primary-500"
          @change="select(point.point_id)"
        />
        <div>
          <p class="text-[10px] font-bold text-gray-800">{{ point.description }}</p>
          <p class="text-[9px] text-gray-500">
            {{ point.location.street }} {{ point.location.street_number }},
            {{ point.location.city }}
            <span v-if="point.location.geolocation?.distance">({{ point.location.geolocation.distance }}m)</span>
          </p>
        </div>
      </label>
    </div>
  </div>
</template>
