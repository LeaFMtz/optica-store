<script setup>
import { useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

defineOptions({ layout: GuestLayout })

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

function submit() {
  form.post(route('register.store'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <div>
    <h2 class="text-2xl font-bold text-gray-900 mb-1 text-center">Creá tu cuenta</h2>
    <p class="text-sm text-gray-500 text-center mb-8">
      ¿Ya tenés cuenta?
      <a :href="route('login')" class="text-primary-500 hover:underline font-medium">Iniciá sesión</a>
    </p>

    <form @submit.prevent="submit" class="space-y-5">
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre</label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          autocomplete="name"
          required
          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          :class="{ 'border-red-500': form.errors.name }"
        >
        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">{{ form.errors.name }}</p>
      </div>

      <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          autocomplete="email"
          required
          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
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
          autocomplete="new-password"
          required
          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          :class="{ 'border-red-500': form.errors.password }"
        >
        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600">{{ form.errors.password }}</p>
      </div>

      <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmá la contraseña</label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          autocomplete="new-password"
          required
          class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          :class="{ 'border-red-500': form.errors.password_confirmation }"
        >
        <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-600">{{ form.errors.password_confirmation }}</p>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full bg-black text-white font-semibold py-2.5 rounded-lg hover:bg-primary-500 transition-colors duration-300 text-sm disabled:opacity-60 disabled:cursor-not-allowed"
      >
        {{ form.processing ? 'Creando cuenta...' : 'Crear cuenta' }}
      </button>
    </form>
  </div>
</template>
