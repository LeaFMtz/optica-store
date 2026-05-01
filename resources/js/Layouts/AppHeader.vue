<script setup>
import { usePage, router } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
  cartCount: {
    type: Number,
    default: 0,
  },
})

const emit = defineEmits(['open-cart'])

const page = usePage()
const auth = computed(() => page.props.auth)

function logout() {
  router.post('/logout')
}
</script>

<template>
  <!-- Top bar -->
  <div class="bg-primary-500 text-white text-xs py-1.5 px-6 flex justify-end items-center space-x-2 w-full relative z-[60]">
    <template v-if="auth?.user">
      <span class="flex items-center gap-1">Hola, {{ auth.user.name }}</span>
      <span class="text-white/50 mx-1">|</span>
      <button type="button" class="hover:underline" @click="logout">Cerrar sesión</button>
    </template>
    <template v-else>
      <a :href="route('login')" class="hover:underline flex items-center gap-1">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
        </svg>
        Iniciar sesión
      </a>
      <span class="text-white/50 mx-1">|</span>
      <a :href="route('register')" class="hover:underline">Crear cuenta</a>
    </template>
  </div>

  <!-- Header -->
  <header class="w-full bg-black text-white sticky top-0 z-50">
    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">

      <!-- Desktop: search | logo | cart -->
      <div class="hidden lg:grid grid-cols-3 items-center py-4">

        <!-- Search pill -->
        <div class="flex items-center">
          <form action="/search" method="GET" class="w-full max-w-xs">
            <div class="flex items-center border border-white/30 rounded-full px-4 py-2 gap-2 hover:border-white/60 transition">
              <input
                type="search"
                name="q"
                placeholder="¿Qué estás buscando?"
                class="bg-transparent text-white text-sm placeholder-white/50 focus:outline-none w-full"
              >
              <button type="submit" class="text-white/70 hover:text-white transition flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z" />
                </svg>
              </button>
            </div>
          </form>
        </div>

        <!-- Logo centrado -->
        <div class="flex justify-center">
          <a href="/" class="block hover:opacity-80 transition">
            <img :src="'/images/logo.webp'" alt="Óptica Guzmán" class="h-14 w-auto">
          </a>
        </div>

        <!-- Cart -->
        <div class="flex justify-end">
          <button
            type="button"
            class="flex items-center gap-2 border border-white/30 rounded-full px-4 py-2 text-white hover:border-white/60 transition"
            @click="$emit('open-cart')"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.4 5M17 13l1.4 5M9 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" />
            </svg>
            <span class="text-sm">({{ cartCount }})</span>
          </button>
        </div>
      </div>

      <!-- Desktop nav -->
      <nav class="hidden lg:flex justify-center gap-8 text-sm font-medium text-white/80 border-t border-white/10 py-3">
        <a href="/" class="hover:text-primary-500 transition">Inicio</a>
        <a :href="route('catalog.view')" class="hover:text-primary-500 transition">Productos</a>
        <a :href="route('contact.view')" class="hover:text-primary-500 transition">Contacto</a>
        <a :href="route('refund-policy.view')" class="hover:text-primary-500 transition">Política de Devolución</a>
        <a :href="route('faq.view')" class="hover:text-primary-500 transition">Preguntas Frecuentes</a>
      </nav>

      <!-- Mobile: hamburger | logo | cart -->
      <div class="lg:hidden flex items-center justify-between py-3">

        <!-- Hamburger -->
        <div class="relative">
          <button
            id="mobile-menu-toggle"
            class="flex items-center justify-center w-10 h-10 text-white hover:text-primary-500 transition"
            onclick="document.getElementById('mobile-menu').classList.toggle('hidden')"
          >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <div id="mobile-menu" class="hidden absolute left-0 top-[52px] z-50 w-64">
            <ul class="p-5 space-y-4 bg-[#111] border border-gray-800 shadow-xl rounded-xl text-white text-sm font-medium">
              <li><a href="/" class="hover:text-primary-500 transition">Inicio</a></li>
              <li><a :href="route('catalog.view')" class="hover:text-primary-500 transition">Productos</a></li>
              <li><a :href="route('contact.view')" class="hover:text-primary-500 transition">Contacto</a></li>
              <li><a :href="route('refund-policy.view')" class="hover:text-primary-500 transition">Política de Devolución</a></li>
              <li><a :href="route('faq.view')" class="hover:text-primary-500 transition">Preguntas Frecuentes</a></li>
              <li class="border-t border-gray-700 pt-4">
                <template v-if="auth?.user">
                  <span class="text-gray-400 block mb-2">Hola, {{ auth.user.name }}</span>
                  <button type="button" class="hover:text-primary-500 transition" @click="logout">Cerrar sesión</button>
                </template>
                <template v-else>
                  <a :href="route('login')" class="hover:text-primary-500 transition block mb-2">Iniciar sesión</a>
                  <a :href="route('register')" class="hover:text-primary-500 transition block">Crear cuenta</a>
                </template>
              </li>
            </ul>
          </div>
        </div>

        <!-- Logo centrado -->
        <a href="/" class="block hover:opacity-80 transition">
          <img :src="'/images/logo.webp'" alt="Óptica Guzmán" class="h-10 w-auto">
        </a>

        <!-- Cart -->
        <button
          type="button"
          class="relative flex items-center justify-center w-10 h-10 text-white hover:text-primary-500 transition"
          @click="$emit('open-cart')"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.4 5M17 13l1.4 5M9 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" />
          </svg>
          <span
            v-if="cartCount > 0"
            class="absolute -top-1 -right-1 bg-primary-500 text-white text-[10px] font-bold rounded-full w-4 h-4 flex items-center justify-center"
          >{{ cartCount }}</span>
        </button>
      </div>

      <!-- Mobile search -->
      <div class="lg:hidden pb-3">
        <form action="/search" method="GET">
          <div class="flex items-center border border-white/30 rounded-full px-4 py-2 gap-2">
            <input
              type="search"
              name="q"
              placeholder="¿Qué estás buscando?"
              class="bg-transparent text-white text-sm placeholder-white/50 focus:outline-none w-full"
            >
            <button type="submit" class="text-white/70 hover:text-white transition flex-shrink-0">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z" />
              </svg>
            </button>
          </div>
        </form>
      </div>

    </div>
  </header>
</template>
