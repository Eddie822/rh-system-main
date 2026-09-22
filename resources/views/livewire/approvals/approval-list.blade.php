<div class="px-4 py-6 mx-auto max-w-7xl">

    <h2 class="mb-6 text-2xl font-semibold text-black dark:text-white">
        Bandeja de Aprobaciones
    </h2>

    {{-- Filters --}}
    <div class="p-4 mb-6 bg-white rounded-lg shadow dark:bg-gray-900">

        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

            {{-- Search employee --}}
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Empleado
                </label>

                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Nombre o nómina..."
                    class="w-full px-3 py-2 text-gray-900 bg-white border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-800 dark:text-white">
            </div>

            {{-- Área --}}
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Área
                </label>

                <select wire:model.live="area"
                    class="w-full px-3 py-2 text-gray-900 bg-white border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    <option value="">Todas</option>

                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}">
                            {{ $area->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Semana --}}
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Semana
                </label>
                <input type="number" min="1" max="53" wire:model.live="week"
                    class="w-full px-3 py-2 text-gray-900 bg-white border border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-800 dark:text-white">
            </div>
        </div>
    </div>

    @if ($requests->count())
        <div class="overflow-x-auto bg-white rounded-lg shadow dark:bg-gray-900">

            <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">

                <thead class="text-xs uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">

                    <tr>
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Empleado</th>
                        <th class="px-6 py-3">Nómina</th>
                        <th class="px-6 py-3">Área</th>
                        <th class="px-6 py-3">Semana</th>
                        <th class="px-6 py-3">Grupo</th>
                        <th class="px-6 py-3">Justificación</th>
                        <th class="px-6 py-3">Horas</th>
                        <th class="px-6 py-3">Creada</th>
                        <th class="px-6 py-3">Acciones</th>
                    </tr>

                </thead>

                <tbody>

                    @foreach ($requests as $request)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">

                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                #{{ $request->id }}
                            </td>

                            <td class="px-6 py-4">
                                {{ $request->employee->name }}
                                {{ $request->employee->last_name }}
                            </td>

                            <td class="px-6 py-4">
                                {{ $request->employee->employee_number }}
                            </td>

                            <td class="px-6 py-4">
                                {{ $request->area->name }}
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
                                {{ number_format($request->days->sum('hours'), 2) }}
                            </td>

                            <td class="px-6 py-4">
                                {{ $request->created_at->format('d/m/Y') }}
                            </td>

                            <td class="px-6 py-4">

                                <a href="{{ route('approvals.show', $request) }}" class="w-full p-6 px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-600 dark:bg-gray-200 dark:text-gray-700 sm:w-auto">
                                    Ver
                                </a>
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
        <div class="p-4 text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400">
            No hay solicitudes pendientes de aprobación.
        </div>

    @endif

</div>
