<x-app-layout>

    <div class="px-4 py-6 mx-auto max-w-7xl">

        <h2 class="mb-4 text-2xl font-semibold dark:text-gray-600">
            Mis Solicitudes
        </h2>

        @if ($requests->count())

            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">

                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-300">
                        <tr>
                            <th class="px-6 py-3">
                                ID
                            </th>

                            <th class="px-6 py-3">
                                Semana
                            </th>

                            <th class="px-6 py-3">
                                Grupo
                            </th>

                            <th class="px-6 py-3">
                                Justificación
                            </th>

                            <th class="px-6 py-3">
                                Fechas
                            </th>

                            <th class="px-6 py-3">
                                Horas
                            </th>

                            <th class="px-6 py-3">
                                Estado
                            </th>

                            <th class="px-6 py-3">
                                Creada
                            </th>

                            <th class="px-6 py-3">
                                
                            </th>
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

                                        @case('pending_supervisor')
                                            <span class="px-2 py-1 text-xs font-semibold text-yellow-800 bg-yellow-100 rounded">
                                                Pendiente Supervisor
                                            </span>
                                        @break

                                        @case('pending_area_manager')
                                            <span class="px-2 py-1 text-xs font-semibold text-blue-800 bg-blue-100 rounded">
                                                Pendiente Gerente
                                            </span>
                                        @break

                                        @case('pending_hr')
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

                                        @default
                                            <span class="px-2 py-1 text-xs font-semibold text-gray-800 bg-gray-100 rounded">
                                                {{ $request->status }}
                                            </span>

                                    @endswitch

                                </td>

                                <td class="px-6 py-4">
                                    {{ $request->created_at->format('d/m/Y') }}
                                </td>
                                
                                <td class="px-6 py-4">
                                    Ver detalle
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

            <div
                class="p-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400">

                Aún no has creado solicitudes.

            </div>

        @endif

    </div>

</x-app-layout>