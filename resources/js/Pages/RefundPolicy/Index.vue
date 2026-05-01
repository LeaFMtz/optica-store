<script setup>
import StorefrontLayout from '@/Layouts/StorefrontLayout.vue'

defineOptions({ layout: StorefrontLayout })
</script>

<template>
  <div class="max-w-2xl mx-auto my-8 px-4">
    <!-- Breadcrumb -->
    <nav class="text-[10px] text-gray-400 mb-2 flex justify-center items-center gap-2 uppercase tracking-widest">
      <a href="/" class="hover:text-[#71C229]">Inicio</a>
      <span>/</span>
      <span class="text-gray-600 font-bold">Política de Devolución</span>
    </nav>

    <div class="bg-white shadow-xl rounded-xl border border-gray-100 overflow-hidden">
      <!-- Header -->
      <div class="p-6 border-b border-gray-50 bg-gray-50/50 text-center">
        <h1 class="text-2xl font-bold text-gray-900 leading-tight">Política de Devolución</h1>
        <p class="text-[11px] text-gray-500 uppercase tracking-widest mt-1">Garantizamos tu satisfacción</p>
      </div>

      <div class="p-8 space-y-8 text-sm text-gray-600 leading-relaxed">

        <section>
          <h2 class="text-lg font-bold text-[#71C229] mb-3">Condiciones para Devoluciones y Cambios</h2>
          <p class="mb-3">Podrás solicitar la devolución o cambio de un producto en los siguientes casos:</p>
          <ul class="list-disc pl-5 space-y-2">
            <li><span class="font-bold text-gray-800">Producto defectuoso:</span> Si el artículo llegó con fallas de fabricación o daños.</li>
            <li><span class="font-bold text-gray-800">Error en el pedido:</span> Si recibiste un producto diferente al solicitado, podrás solicitar el cambio sin costo adicional.</li>
            <li><span class="font-bold text-gray-800">Daño en el envío:</span> Si el producto sufrió daños durante el transporte.</li>
            <li><span class="font-bold text-gray-800">Accesorios faltantes:</span> Si el producto llegó incompleto o sin sus complementos.</li>
          </ul>
        </section>

        <section class="bg-gray-50 p-4 rounded-lg border border-gray-100">
          <h3 class="font-bold text-gray-800 mb-2">Producto agotado:</h3>
          <p class="mb-2 text-xs">Si el producto que seleccionaste no está disponible, podrás optar por:</p>
          <ul class="list-disc pl-5 space-y-1 text-xs italic">
            <li>Esperar su entrega.</li>
            <li>Solicitar la cancelación y devolución del importe.</li>
            <li>Optar por un producto alternativo con características equivalentes.</li>
            <li>Emitir una nueva orden de compra por el mismo importe.</li>
          </ul>
        </section>

        <p class="text-xs font-bold text-red-500 bg-red-50 p-3 rounded border border-red-100 italic">
          Importante: No se aceptan devoluciones por cambio de opinión ni en productos personalizados sin fallas.
        </p>

        <section>
          <h2 class="text-lg font-bold text-[#71C229] mb-2">Plazo para Solicitar una Devolución</h2>
          <p>Tendrás un plazo de <span class="font-bold text-gray-900">24 horas</span> una vez recibido el producto para solicitar la devolución o elegir una alternativa en caso de que el producto no esté disponible.</p>
        </section>

        <section>
          <h2 class="text-lg font-bold text-[#71C229] mb-3">Procedimiento para Realizar un Cambio o Devolución</h2>
          <p class="mb-3 font-medium">Puedes gestionar tu devolución o cambio de dos maneras:</p>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="p-3 bg-gray-50 rounded border border-gray-100 text-xs">
              <span class="font-bold block mb-1">En nuestra tienda física:</span>
              Acércate con el ticket de compra y el producto en su empaque original.
            </div>
            <div class="p-3 bg-gray-50 rounded border border-gray-100 text-xs">
              <span class="font-bold block mb-1">Desde tu domicilio:</span>
              Escríbenos a nuestro contacto de WhatsApp para coordinar la devolución.
            </div>
          </div>

          <div class="mt-4 space-y-2">
            <p class="flex items-start gap-2">
              <span class="text-[#71C229]">&bull;</span>
              <span>Si el cambio se debe a un error nuestro (producto defectuoso, error en el pedido, etc.), cubrimos los costos de envío.</span>
            </p>
            <p class="flex items-start gap-2">
              <span class="text-[#71C229]">&bull;</span>
              <span>Si el cliente desea cambiar el producto por razones personales (por ejemplo, porque no le gustó), el cambio se realizará, pero los costos de envío correrán por cuenta del comprador.</span>
            </p>
          </div>
        </section>

        <section>
          <h2 class="text-lg font-bold text-[#71C229] mb-2">Reembolso del Importe Abonado</h2>
          <p>La devolución del dinero se realizará a través del mismo método de pago utilizado en la compra. En caso de reembolso vía transferencia bancaria, la cuenta deberá estar a nombre del titular de la cuenta de compra. El proceso puede demorar hasta <span class="font-bold text-gray-900">10 días hábiles</span> por cuestiones administrativas.</p>
        </section>

        <div class="pt-6 border-t border-gray-50 text-center italic text-gray-500 text-xs">
          Para cualquier consulta, escríbenos a nuestro contacto de WhatsApp.<br>
          <span class="font-bold text-gray-900 mt-2 block">¡Gracias por confiar en Óptica Guzmán!</span>
        </div>
      </div>

      <!-- Card footer -->
      <div class="p-4 bg-[#71C229]/5 text-center">
        <p class="text-[10px] text-[#71C229] font-bold uppercase tracking-widest">Comprometidos con tu visión</p>
      </div>
    </div>
  </div>
</template>
