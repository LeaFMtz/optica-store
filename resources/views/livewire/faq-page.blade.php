<div class="max-w-2xl mx-auto my-8 px-4">
    {{-- Breadcrumb minimalista --}}
    <nav class="text-[10px] text-gray-400 mb-2 flex justify-center items-center gap-2 uppercase tracking-widest">
        <a href="{{ url('/') }}" class="hover:text-[#71C229]">Inicio</a>
        <span>/</span>
        <span class="text-gray-600 font-bold">Preguntas Frecuentes</span>
    </nav>

    <div class="bg-white shadow-xl rounded-xl border border-gray-100 overflow-hidden">
        {{-- Cabecera --}}
        <div class="p-6 border-b border-gray-50 bg-gray-50/50 text-center">
            <h1 class="text-2xl font-bold text-gray-900 leading-tight">Preguntas Frecuentes</h1>
            <p class="text-[11px] text-gray-500 uppercase tracking-widest mt-1">Resolvemos tus dudas principales</p>
        </div>

        <div class="p-8 space-y-10">
            {{-- Pregunta 1: ¿Cómo comprar? --}}
            <section>
                <h2 class="text-xl font-bold text-[#71C229] mb-4">¿Cómo comprar?</h2>
                <div class="space-y-4 text-sm text-gray-700 leading-relaxed">
                    <div>
                        <span class="font-bold text-gray-900">Paso 1:</span> Elegir lo que querés.
                        <p class="mt-1 text-gray-500">Agrega los productos que desees a tu carrito de compra haciendo click en "Añadir al carrito". Una vez que hayas seleccionado todos los productos, hace click en "Finalizar Compra" para avanzar en tu pedido.</p>
                    </div>
                    <div>
                        <span class="font-bold text-gray-900">Paso 2:</span> El siguiente paso es completar tu dirección y seleccionar el modo de envío.
                    </div>
                    <div>
                        <span class="font-bold text-gray-900">Paso 3:</span> Apretá el botón "Siguiente" que te llevará a la finalización del pago.
                        <p class="mt-1 text-gray-500 italic">Todas las transacciones son seguras y están cifradas a través de MercadoPago (El mismo sistema que utiliza MercadoLibre).</p>
                    </div>
                    <div>
                        <span class="font-bold text-gray-900">Paso 4:</span> Confirmación de compra.
                        <p class="mt-1 text-gray-500">Una vez confirmado el pago, vas a estar recibiendo un mail con la confirmación de tu compra.</p>
                    </div>
                </div>
            </section>

            <hr class="border-gray-50">

            {{-- Pregunta 2: ¿Quiero mis lentes con graduación? --}}
            <section>
                <h2 class="text-xl font-bold text-[#71C229] mb-3">¿Quiero mis lentes con graduación?</h2>
                <p class="text-sm text-gray-600 leading-relaxed">
                    ¡Es muy fácil! Sólo tenés que enviarnos una foto de la receta y nuestros asesores se comunicarán con vos.
                </p>
                <div class="mt-4 inline-flex items-center gap-2 text-sm font-bold text-gray-900 bg-gray-50 px-4 py-2 rounded-full border border-gray-100">
                    <svg class="w-4 h-4 text-[#71C229]" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    WhatsApp: 381 430-1312
                </div>
            </section>

            <hr class="border-gray-50">

            {{-- Pregunta 3: ¿Realizan envíos? --}}
            <section>
                <h2 class="text-xl font-bold text-[#71C229] mb-3">¿Realizan envíos?</h2>
                <p class="text-sm text-gray-600 leading-relaxed">
                    Sí, realizamos envíos a todo el país.
                </p>
            </section>

            <hr class="border-gray-50">

            {{-- Pregunta 4: ¿El precio incluye lentes? --}}
            <section>
                <h2 class="text-xl font-bold text-[#71C229] mb-3">¿El precio publicado de los armazones incluye lentes?</h2>
                <p class="text-sm text-gray-600 leading-relaxed">
                    No, el precio publicado es por el armazón, las lentes se cotizan a parte según tus necesidades.
                </p>
            </section>
        </div>

        {{-- Pie de tarjeta decorativo --}}
        <div class="p-4 bg-[#71C229]/5 text-center">
            <p class="text-[10px] text-[#71C229] font-bold uppercase tracking-widest">Óptica Guzmán &bull; Calidad a tu medida</p>
        </div>
    </div>
</div>
