<script setup>
import { ref, computed, watch, provide } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppHeader from './AppHeader.vue'
import AppFooter from './AppFooter.vue'
import CartSidebar from '@/Components/CartSidebar.vue'

const page = usePage()
const cart = computed(() => page.props.cart)

// Keep local cart count in sync after sidebar mutations
const localCartCount = ref(cart.value?.count ?? 0)

watch(() => cart.value?.count, (val) => {
  if (val !== undefined) localCartCount.value = val
}, { immediate: true })

function onCartUpdated(newCount) {
  localCartCount.value = newCount
}

// Cart sidebar open state — provided to child pages via inject('openCartSidebar')
const cartSidebarOpen = ref(false)

function openCartSidebar() { cartSidebarOpen.value = true }
function closeCartSidebar() { cartSidebarOpen.value = false }

provide('openCartSidebar', openCartSidebar)
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <AppHeader :cart-count="localCartCount" @open-cart="openCartSidebar" />
    <main class="flex-1 pt-[112px] lg:pt-0">
      <slot />
    </main>
    <AppFooter />
    <CartSidebar
      :is-open="cartSidebarOpen"
      @close="closeCartSidebar"
      @cart-updated="onCartUpdated"
    />
  </div>
</template>
