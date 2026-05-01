<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'

defineOptions({ layout: StorefrontLayout })

defineProps({
  order: { type: Object, required: true },
})
</script>

<template>
  <section class="bg-white min-h-[70vh] flex items-center">
    <div class="max-w-screen-xl px-4 py-24 mx-auto sm:px-6 lg:px-8 lg:py-32">
      <div class="max-w-xl mx-auto text-center">

        <span class="text-[10px] font-black uppercase tracking-widest text-center bg-[#71C229]/10 text-[#71C229] px-4 py-2 rounded-xl border border-[#71C229]/20">
          Pedido finalizado
        </span>

        <h1 class="mt-12 text-4xl font-black sm:text-6xl text-black uppercase tracking-tighter italic">
          <span class="block text-[#71C229]">¡Éxito!</span>
          Pedido Recibido
        </h1>

        <div class="mt-8 p-8 bg-gray-50 rounded-2xl border border-gray-100 shadow-sm inline-block w-full max-w-sm">
          <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">
            Referencia del pedido
          </p>
          <strong class="text-2xl font-black text-black">
            #{{ order.reference }}
          </strong>

          <div v-if="order.total" class="mt-4 pt-4 border-t border-gray-100">
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Total</p>
            <p class="text-lg font-black text-gray-900">{{ order.total }}</p>
          </div>
        </div>

        <p class="mt-8 text-sm font-bold text-gray-600 max-w-md mx-auto leading-relaxed uppercase tracking-tight">
          Gracias por confiar en Óptica Guzmán. Nos pondremos en contacto con vos a la brevedad para coordinar la entrega.
        </p>

        <a
          :href="route('home')"
          class="inline-flex items-center gap-2 px-10 py-4 mt-12 text-[10px] font-black uppercase tracking-widest text-center text-white bg-black rounded-xl hover:bg-[#71C229] transition-all duration-300 shadow-lg shadow-black/10 group"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
          </svg>
          Volver al inicio
        </a>
      </div>
    </div>
  </section>
</template>
