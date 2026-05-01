<template>
  <div class="min-h-screen flex flex-col justify-center items-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
      <a href="/">
        <img :src="'/images/logo.webp'" alt="Óptica Guzmán" class="h-16 w-auto">
      </a>
    </div>

    <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
      <slot />
    </div>
  </div>
</template>
