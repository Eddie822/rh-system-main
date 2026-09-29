<div class="space-y-6 text-gray-900 dark:text-gray-100">
    <div>
        <h1 class="text-2xl font-semibold">Reportes</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Solicitudes aprobadas por fecha de horas extra. Los resultados se actualizan al cambiar filtros.</p>
    </div>
    <div class="p-5 space-y-4 bg-white rounded-lg shadow dark:bg-gray-800">
        @if ($filterErrors)
            <div role="alert" class="p-3 text-red-700 bg-red-50 rounded-lg">
                @foreach ($filterErrors as $error) <p>{{ $error }}</p> @endforeach
            </div>
        @endif
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <x-label for="period" value="Periodo" />
                <select id="period" wire:model.live="period" class="w-full mt-1 border-gray-300 rounded-md dark:bg-gray-900">
                    <option value="day">Día</option><option value="week">Semana</option><option value="range">Rango de fechas</option>
                </select>
            </div>
            @if ($period === 'day')
                <div>
                    <x-label for="date" value="Fecha" />
                    <x-input id="date" type="date" wire:model.live="date" class="w-full mt-1" />
                </div>
            @elseif ($period === 'week')
                <div>
                    <x-label for="year" value="Año ISO" />
                    <x-input id="year" type="number" min="2000" max="2100" wire:model.live.debounce.400ms="year" class="w-full mt-1" />
                </div>
                <div>
                    <x-label for="week" value="Semana ISO (lunes a domingo)" />
                    <x-input id="week" type="number" min="1" max="53" wire:model.live.debounce.400ms="week" class="w-full mt-1" />
                </div>
            @elseif ($period === 'range')
                <div>
                    <x-label for="from" value="Desde" />
                    <x-input id="from" type="date" wire:model.live="from" class="w-full mt-1" />
                </div>
                <div>
                    <x-label for="to" value="Hasta" />
                    <x-input id="to" type="date" wire:model.live="to" class="w-full mt-1" />
                </div>
            @endif
            <div>
                <x-label for="area_id" value="Área de la solicitud" />
                <select id="area_id" wire:model.live="area_id" class="w-full mt-1 border-gray-300 rounded-md dark:bg-gray-900">
                    <option value="">Todas las áreas</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}">{{ $area->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-label for="employee_number" value="Nómina del empleado" />
                <x-input id="employee_number" wire:model.live.debounce.400ms="employee_number" placeholder="Todas" class="w-full mt-1" />
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if ($exportUrl)
                <a href="{{ $exportUrl }}" class="px-4 py-2 text-white bg-green-700 rounded-lg">Descargar Excel</a>
            @else
                <span class="px-4 py-2 text-gray-500 bg-gray-100 rounded-lg">Corrige los filtros para descargar</span>
            @endif
            <button type="button" wire:click="clearFilters" class="px-4 py-2 text-blue-700 border border-blue-300 rounded-lg">Restablecer</button>
            <span wire:loading.delay class="text-sm text-gray-500">Actualizando…</span>
        </div>
    </div>
        @if ($dates)
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800"><p class="text-sm">Periodo consultado</p><p class="font-semibold">{{ $dates[0] }} — {{ $dates[1] }}</p></div>
            <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800"><p class="text-sm">Solicitudes aprobadas</p><p class="text-2xl font-semibold">{{ $requestCount }}</p></div>
            <div class="p-4 bg-white rounded-lg shadow dark:bg-gray-800"><p class="text-sm">Horas extra</p><p class="text-2xl font-semibold">{{ number_format($hours, 2) }}</p></div>
        </div>
        <div class="overflow-x-auto bg-white rounded-lg shadow dark:bg-gray-800">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 dark:bg-gray-700"><tr>
                    @foreach (['Solicitud', 'Tipo', 'Nómina', 'Empleado', 'Área', 'Grupo', 'Fecha', 'Horas'] as $heading)
                        <th class="px-4 py-3">{{ $heading }}</th>
                    @endforeach
                </tr></thead>
                <tbody>
                    @forelse ($rows as $row)
                        @php($employee = $row->request->is_group ? $row->employee : $row->request->employee)
                        <tr class="border-t dark:border-gray-700">
                            <td class="px-4 py-3">#{{ $row->request_id }}</td>
                            <td class="px-4 py-3">{{ $row->request->is_group ? 'Grupal' : 'Individual' }}</td>
                            <td class="px-4 py-3">{{ $employee?->employee_number }}</td>
                            <td class="px-4 py-3">{{ $employee?->name }} {{ $employee?->last_name }}</td>
                            <td class="px-4 py-3">{{ $row->request->area?->name ?? 'Sin área' }}</td>
                            <td class="px-4 py-3">{{ $row->request->group }}</td>
                            <td class="px-4 py-3">{{ $row->day_date->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">{{ number_format($row->hours, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-6 text-center">No hay horas extra aprobadas para estos filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $rows->links() }}
        @endif
        <p class="text-sm text-gray-500 dark:text-gray-400">Cada fila corresponde a un empleado y una fecha. El Excel incluye todos los resultados filtrados, además de la justificación, solicitante y fecha de aprobación final.</p>
    </div>
</div>
