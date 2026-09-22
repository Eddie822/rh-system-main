<div class="px-4 py-6 mx-auto max-w-7xl">

    <h2 class="mb-6 text-2xl font-semibold text-gray-900 dark:text-white">
        Mis Solicitudes
    </h2>

    {{-- Filtros --}}
    <div class="p-4 mb-6 bg-white rounded-lg shadow dark:bg-gray-900">

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">

            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Estado
                </label>

                <select
                    wire:model.live="status"
                    class="w-full px-3 py-2 text-gray-900 bg-white border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                >
                    <option value="">Todos</option>

                    <option value="pending_area_manager">
                        Pendiente Gerente de Área
                    </option>

                    <option value="pending_hr_manager">
                        Pendiente RH
                    </option>

                    <option value="pending_plant_manager">
                        Pendiente Gerente de Planta
                    </option>

                    <option value="approved">
                        Aprobadas
                    </option>

                    <option value="rejected">
                        Rechazadas
                    </option>
                </select>
            </div>

            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Semana
                </label>

                <input
                    type="number"
                    min="1"
                    max="53"
                    wire:model.live.debounce.500ms="week"
                    class="w-full px-3 py-2 text-gray-900 bg-white border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                >
            </div>

            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Desde
                </label>

                <input
                    type="date"
                    wire:model.live="from_date"
                    class="w-full px-3 py-2 text-gray-900 bg-white border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                >
            </div>

            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Hasta
                </label>

                <input
                    type="date"
                    wire:model.live="to_date"
                    class="w-full px-3 py-2 text-gray-900 bg-white border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                >
            </div>

        </div>

        <div class="mt-4">
            <button
                wire:click="clearFilters"
                class="px-4 py-2 text-white bg-gray-500 rounded-lg hover:bg-gray-600"
            >
                Limpiar filtros
            </button>
        </div>

    </div>

    {{-- Loading --}}
    <div wire:loading class="mb-4 text-blue-500">
        Cargando...
    </div>

    @if ($requests->count())

        <div class="overflow-x-auto bg-white rounded-lg shadow dark:bg-gray-900">

            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">

                <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">

                    <tr>
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Semana</th>
                        <th class="px-6 py-3">Grupo</th>
                        <th class="px-6 py-3">Justificación</th>
                        <th class="px-6 py-3">Fechas</th>
                        <th class="px-6 py-3">Horas</th>
                        <th class="px-6 py-3">Estado</th>
                        <th class="px-6 py-3">Creada</th>
                        <th class="px-6 py-3"></th>
                    </tr>

                </thead>

                <tbody>

                    @foreach ($requests as $request)

                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">

                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                #{{ $request->id }}
                            </td>

                            <td class="px-6 py-4">
                                {{ $request->week }}
                            </td>

                            <td class="px-6 py-4">
                                {{ $request->group }}
                            </td>

                            <td class="px-6 py-4">
                                {{ $request->reason }}
                            </td>

                            <td class="px-6 py-4">
                                @foreach ($request->days as $day)
                                    <div>
                                        {{ \Carbon\Carbon::parse($day->day_date)->format('d/m/Y') }}
                                    </div>
                                @endforeach
                            </td>

                            <td class="px-6 py-4">
                                {{ number_format($request->days->sum('hours'), 2) }}
                            </td>

                            <td class="px-6 py-4">

                                @switch($request->status)

                                    @case('pending_area_manager')
                                        <span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded">
                                            Pendiente Gerente Área
                                        </span>
                                    @break

                                    @case('pending_hr_manager')
                                        <span class="px-2 py-1 text-xs font-semibold text-purple-800 bg-purple-100 rounded">
                                            Pendiente RH
                                        </span>
                                    @break

                                    @case('pending_plant_manager')
                                        <span class="px-2 py-1 text-xs font-semibold text-orange-800 bg-orange-100 rounded">
                                           Pendiente Gerente Planta
                                        </span>
                                    @break

                                    @case('approved')
                                        <span class="px-2 py-1 text-xs font-semibold text-green-800 bg-green-100 rounded">
                                            Aprobada
                                        </span>
                                    @break

                                    @case('rejected')
                                        <span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded">
                                            Rechazada
                                        </span>
                                    @break

                                @endswitch

                            </td>

                            <td class="px-6 py-4">
                                {{ $request->created_at->format('d/m/Y') }}
                            </td>

                            <td class="px-6 py-4">

                                @if($request->status === 'pending_area_manager')

                                    <a class="p-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400" href="{{ route('requests.edit', $request) }}">
                                        Editar
                                    </a>

                                @endif

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

        <div class="mt-4">
            {{ $requests->links() }}
        </div>

    @else

        <div class="p-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400">
            No se encontraron solicitudes.
        </div>

    @endif

</div>