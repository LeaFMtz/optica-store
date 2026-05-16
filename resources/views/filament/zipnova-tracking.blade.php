<div class="space-y-4 p-4">
    @isset($error)
        <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-700">
            Error al obtener el tracking: {{ $error }}
        </div>
    @endisset

    <div class="flex items-center gap-4 text-sm">
        <span class="font-semibold text-gray-600">ID:</span>
        <span class="text-gray-900">{{ $tracking['id'] ?? '—' }}</span>
        <span class="font-semibold text-gray-600 ml-4">Estado:</span>
        <span class="text-gray-900 font-bold uppercase">{{ $tracking['status'] ?? '—' }}</span>
    </div>

    @if(count($events) > 0)
        <div class="border border-gray-100 rounded-xl overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-gray-500">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-gray-500">Descripción</th>
                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-widest text-gray-500">Ubicación</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($events as $event)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 text-xs text-gray-600 whitespace-nowrap">{{ $event['date'] ?? '' }}</td>
                            <td class="px-4 py-3 text-xs text-gray-900">{{ $event['description'] ?? '' }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600">{{ $event['location'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-6 space-y-2">
            <p class="text-sm text-gray-400">Aún no hay eventos de tracking.</p>
            <p class="text-xs text-gray-300">Los eventos aparecen cuando el carrier comienza a procesar el envío. Revisá más tarde.</p>
        </div>
    @endif
</div>
