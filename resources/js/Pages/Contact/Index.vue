<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'
import { useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

defineOptions({ layout: StorefrontLayout })

const page = usePage()
const successMessage = computed(() => page.props.flash?.success ?? null)

const form = useForm({
  name: '',
  email: '',
  phone: '',
  message: '',
})

function submit() {
  form.post(route('contact.send'), {
    onSuccess: () => form.reset(),
  })
}
</script>

<template>
  <div class="max-w-2xl mx-auto my-4 px-4">
    <!-- Breadcrumb -->
    <nav class="text-[10px] text-gray-400 mb-2 flex justify-center items-center gap-2 uppercase tracking-widest">
      <a href="/" class="hover:text-primary-500">Inicio</a>
      <span>/</span>
      <span class="text-gray-600 font-bold">Contacto</span>
    </nav>

    <div class="bg-white shadow-xl rounded-xl border border-gray-100 overflow-hidden">
      <!-- Header -->
      <div class="p-4 border-b border-gray-50 bg-gray-50/50 text-center">
        <h1 class="text-2xl font-bold text-gray-900 leading-tight">Contacto</h1>
        <p class="text-[11px] text-gray-500 uppercase tracking-tighter">Estamos a un click de distancia</p>
      </div>

      <div class="p-6">
        <!-- Quick action buttons -->
        <div class="flex justify-center items-center gap-6 mb-6">
          <!-- WhatsApp -->
          <a
            href="https://wa.me/5493814301312"
            target="_blank"
            title="WhatsApp"
            class="w-12 h-12 bg-white text-primary-500 rounded-full flex items-center justify-center transition duration-300 transform hover:scale-110 shadow-sm border border-green-100 hover:bg-primary-500 hover:text-white"
          >
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
              <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
            </svg>
          </a>

          <!-- Email -->
          <a
            href="mailto:opticaguzmantuc@gmail.com"
            title="Enviar Email"
            class="w-12 h-12 bg-white text-primary-500 rounded-full flex items-center justify-center transition duration-300 transform hover:scale-110 shadow-sm border border-green-100 hover:bg-primary-500 hover:text-white"
          >
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
              <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z" />
            </svg>
          </a>

          <!-- Location -->
          <a
            href="https://www.google.com/maps/search/?api=1&query=San+Martin+333+San+Miguel+de+Tucuman+Tucuman"
            target="_blank"
            title="Ver Ubicación"
            class="w-12 h-12 bg-white text-primary-500 rounded-full flex items-center justify-center transition duration-300 transform hover:scale-110 shadow-sm border border-green-100 hover:bg-primary-500 hover:text-white"
          >
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" />
            </svg>
          </a>
        </div>

        <!-- Success message -->
        <div
          v-if="successMessage"
          class="bg-green-50 text-green-700 p-2 rounded text-xs mb-4 text-center font-bold"
        >
          {{ successMessage }}
        </div>

        <!-- Validation errors -->
        <div
          v-if="Object.keys(form.errors).length"
          class="bg-red-50 text-red-700 p-2 rounded text-xs mb-4 space-y-1"
        >
          <p v-for="(error, field) in form.errors" :key="field">{{ error }}</p>
        </div>

        <!-- Contact form -->
        <form @submit.prevent="submit" class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Nombre</label>
              <input
                v-model="form.name"
                type="text"
                class="w-full px-3 py-2 bg-gray-50 border border-gray-100 rounded focus:ring-1 focus:ring-primary-500 outline-none text-sm transition"
              >
            </div>
            <div>
              <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Email</label>
              <input
                v-model="form.email"
                type="email"
                class="w-full px-3 py-2 bg-gray-50 border border-gray-100 rounded focus:ring-1 focus:ring-primary-500 outline-none text-sm transition"
              >
            </div>
          </div>

          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Teléfono</label>
            <input
              v-model="form.phone"
              type="tel"
              class="w-full px-3 py-2 bg-gray-50 border border-gray-100 rounded focus:ring-1 focus:ring-primary-500 outline-none text-sm transition"
            >
          </div>

          <div>
            <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Mensaje</label>
            <textarea
              v-model="form.message"
              rows="2"
              class="w-full px-3 py-2 bg-gray-50 border border-gray-100 rounded focus:ring-1 focus:ring-primary-500 outline-none text-sm transition resize-none"
            ></textarea>
          </div>

          <div class="pt-2">
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full bg-black text-white py-3 rounded-full font-bold text-xs uppercase tracking-widest hover:bg-primary-500 transition shadow-md disabled:opacity-50"
            >
              {{ form.processing ? 'Enviando...' : 'Enviar' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
