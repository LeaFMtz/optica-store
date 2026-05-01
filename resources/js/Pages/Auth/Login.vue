<script setup>
import { useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

defineOptions({ layout: GuestLayout })

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

function submit() {
  form.post(route('login.store'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-900 mb-1 text-center">Iniciá sesión</h2>
    <p class="text-sm text-gray-500 text-center mb-8">
      ¿No tenés cuenta?
      <a :href="route('register')" class="text-[#71C229] hover:underline font-medium">Creá una</a>
    </p>

    <form @submit.prevent="submit" class="space-y-5">
      <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          required
          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#71C229] focus:border-transparent"
          :class="{ 'border-red-500': form.errors.email }"
        >
        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          autocomplete="current-password"
          required
          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#71C229] focus:border-transparent"
          :class="{ 'border-red-500': form.errors.password }"
        >
        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
      </div>

      <div class="flex items-center">
        <input
          id="remember"
          v-model="form.remember"
          type="checkbox"
          class="w-4 h-4 text-[#71C229] border-gray-300 rounded focus:ring-[#71C229]"
        >
        <label for="remember" class="ml-2 text-sm text-gray-600">Recordarme</label>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full bg-black text-white font-semibold py-2.5 rounded-lg hover:bg-[#71C229] transition-colors duration-300 text-sm disabled:opacity-60 disabled:cursor-not-allowed"
      >
        {{ form.processing ? 'Iniciando...' : 'Iniciar sesión' }}
      </button>
    </form>
  </div>
</template>
