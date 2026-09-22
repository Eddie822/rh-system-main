<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                Solicitud #{{ $request->id }}
            </h1>

            <p class="text-sm text-gray-500 dark:text-gray-400">
                Creada el {{ $request->created_at->format('d/m/Y H:i') }}
            </p>
        </div>

        <a href="{{ route('approvals.index') }}"
            class="px-4 py-2 text-white bg-gray-600 rounded-lg shadow hover:bg-gray-700 dark:bgbg-white dark:bg-gray-800">
            Volver
        </a>

        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
            Información del Empleado
        </h2>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">

            <div class="mr-8">
                <span class="text-sm text-gray-500">
                    Nombre
                </span>
                <p class="font-medium text-gray-900 dark:text-white">
                    {{ $request->employee->name }}
                    {{ $request->employee->last_name }}
                </p>
            </div>

            <div>
                <span class="text-sm text-gray-500">
                    Nómina
                </span>
                <p class="font-medium text-gray-900 dark:text-white">
                    {{ $request->employee->employee_number }}
                </p>
            </div>

            <div>
                <span class="text-sm text-gray-500">
                    Área
                </span>
                <p class="font-medium text-gray-900 dark:text-white">
                    {{ $request->area->name }}
                </p>
            </div>

            <div>
                <span class="text-sm text-gray-500">
                    Semana
                </span>
                <p class="font-medium text-gray-900 dark:text-white">
                    {{ $request->week }}
                </p>
            </div>

        </div>

    </div>

    {{-- Información Solicitud --}}
    <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800">

        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
            Información de la Solicitud
        </h2>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

            <div>
                <span class="text-sm text-gray-500">
                    Grupo / Línea
                </span>

                <p class="font-medium text-gray-900 dark:text-white">
                    {{ $request->group }}
                </p>
            </div>

            <div>
                <span class="text-sm text-gray-500">
                    Estado Actual
                </span>

                <div class="mt-2">

                    @switch($request->status)
                        @case('pending_area_manager')
                            <span
                                class="px-3 py-1 text-sm font-semibold text-blue-800 bg-blue-100 rounded-full dark:bg-blue-900 dark:text-blue-200">
                                Pendiente Gerente de Área
                            </span>
                        @break

                        @case('pending_hr_manager')
                            <span
                                class="px-3 py-1 text-sm font-semibold text-purple-800 bg-purple-100 rounded-full dark:bg-purple-900 dark:text-purple-200">
                                Pendiente RH
                            </span>
                        @break

                        @case('pending_plant_manager')
                            <span
                                class="px-3 py-1 text-sm font-semibold text-orange-800 bg-orange-100 rounded-full dark:bg-orange-900 dark:text-orange-200">
                                Pendiente Gerente de Planta
                            </span>
                        @break

                        @case('approved')
                            <span
                                class="px-3 py-1 text-sm font-semibold text-green-800 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-200">
                                Aprobada
                            </span>
                        @break

                        @case('rejected')
                            <span
                                class="px-3 py-1 text-sm font-semibold text-red-800 bg-red-100 rounded-full dark:bg-red-900 dark:text-red-200">
                                Rechazada
                            </span>
                        @break

                        @default
                            <span
                                class="px-3 py-1 text-sm font-semibold text-gray-800 bg-gray-100 rounded-full dark:bg-gray-700 dark:text-gray-200">
                                {{ $request->status }}
                            </span>
                    @endswitch

                </div>
            </div>

            <div class="md:col-span-2">

                <span class="text-sm text-gray-500">
                    Justificación
                </span>

                <p class="mt-1 font-medium text-gray-900 dark:text-white">
                    {{ $request->reason }}
                </p>

            </div>

        </div>

    </div>

    {{-- Fechas --}}
    <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800">

        <div class="flex items-center justify-between mb-4">

            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                Fechas Solicitadas
            </h2>

            <span
                class="px-3 py-1 font-medium text-blue-800 bg-blue-100 rounded-full dark:bg-blue-900 dark:text-blue-200">
                Total: {{ number_format($request->days->sum('hours'), 2) }} hrs
            </span>

        </div>

        <div class="overflow-x-auto">

            <table class="w-full text-sm">

                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700">

                        <th class="py-3 text-left text-gray-700 dark:text-gray-300">
                            Día
                        </th>

                        <th class="py-3 text-left text-gray-700 dark:text-gray-300">
                            Fecha
                        </th>

                        <th class="py-3 text-left text-gray-700 dark:text-gray-300">
                            Horas
                        </th>

                    </tr>
                </thead>

                <tbody>

                    @foreach ($request->days as $day)
                        <tr class="border-b border-gray-200 dark:border-gray-700">

                            <td class="py-3 text-gray-900 dark:text-gray-200">
                                {{ $day->day_name }}
                            </td>

                            <td class="py-3 text-gray-900 dark:text-gray-200">
                                {{ \Carbon\Carbon::parse($day->day_date)->format('d/m/Y') }}
                            </td>

                            <td class="py-3 text-gray-900 dark:text-gray-200">
                                {{ $day->hours }}
                            </td>

                        </tr>
                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

    {{-- Historial --}}
    <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800">

        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
            Historial de Aprobaciones
        </h2>

        @forelse($request->authorizations as $authorization)
            <div class="flex items-center justify-between py-3 border-b border-gray-200 dark:border-gray-700">

                <div>

                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ $authorization->user->name }}
                    </p>

                    <p class="text-sm text-gray-500">
                        @switch($authorization->authorization_role)
                            @case('area_manager')
                                Gerente de Área
                            @break

                            @case('hr_manager')
                                Gerente de RH
                            @break

                            @case('plant_manager')
                                Gerente de Planta
                            @break

                            @default
                                {{ $authorization->authorization_role }}
                        @endswitch
                    </p>

                    @if ($authorization->reason)
                        <p class="mt-1 text-sm text-red-500">
                            {{ $authorization->reason }}
                        </p>
                    @endif

                </div>

                <div class="text-right">

                    <p class="font-medium text-gray-900 dark:text-white">
                        @switch($authorization->action)
                            @case('approved')
                                Aprobado
                            @break

                            @case('rejected')
                                Rechazado
                            @break

                            @case('pending')
                                Pendiente
                            @break

                            @default
                                {{ ucfirst($authorization->action) }}
                        @endswitch
                    </p>

                    <p class="text-xs text-gray-500">
                        {{ $authorization->timestamp }}
                    </p>

                </div>

            </div>

            @empty

                <p class="text-gray-500">
                    Sin autorizaciones registradas.
                </p>
            @endforelse
        </div>

        {{-- Acciones --}}
        @if (
            ($request->status === 'pending_area_manager' && auth()->user()->role === 'area_manager') ||
                ($request->status === 'pending_hr_manager' && auth()->user()->role === 'hr_manager') ||
                ($request->status === 'pending_plant_manager' && auth()->user()->role === 'plant_manager'))
            <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800">

                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                    Acciones
                </h2>

                <div class="flex flex-col gap-3 sm:flex-row">

                    <button wire:click="approve" wire:loading.attr="disabled"
                        class="px-5 py-3 text-white bg-green-600 rounded-lg hover:bg-green-700">
                        Aprobar Solicitud
                    </button>

                    <button wire:click="openRejectModal" wire:loading.attr="disabled"
                        class="px-5 py-3 text-white bg-red-600 rounded-lg hover:bg-red-700">
                        Rechazar Solicitud
                    </button>

                </div>

            </div>
        @endif

        {{-- Modal Rechazo --}}
        @if ($showRejectModal)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">

                <div class="w-full max-w-lg p-6 mx-4 bg-white shadow-xl rounded-xl dark:bg-gray-800">

                    <h2 class="mb-4 text-xl font-bold text-gray-900 dark:text-white">
                        Rechazar Solicitud
                    </h2>

                    <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
                        Es obligatorio capturar el motivo del rechazo.
                    </p>

                    <textarea wire:model.defer="rejectReason" rows="5"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="Escribe el motivo del rechazo">
                </textarea>

                    @error('rejectReason')
                        <p class="mt-2 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

                    <div class="flex justify-end gap-3 mt-6">

                        <button wire:click="closeRejectModal"
                            class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 dark:bg-gray-700 dark:text-white dark:hover:bg-gray-600">
                            Cancelar
                        </button>

                        <button wire:click="reject" class="px-4 py-2 text-white bg-red-600 rounded-lg hover:bg-red-700">
                            Confirmar Rechazo
                        </button>

                    </div>

                </div>

            </div>
        @endif

    </div>
