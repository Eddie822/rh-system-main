<div wire:poll.60s class="px-4 py-6 mx-auto space-y-6 max-w-7xl">

    <div>
        <h2 class="text-2xl font-semibold text-gray-900 dark:text-white">
            Consultar Solicitudes
        </h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Ingresa el número de folio (#ID) de la solicitud para consultar su estado actual, gerentes responsables y seguimiento de firmas.
        </p>
    </div>

    {{-- Buscador por ID/Folio --}}
    <div class="p-5 bg-white rounded-lg shadow dark:bg-gray-900">
        <form wire:submit="searchRequest" class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="flex-1">
                <label for="request-id-search" class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                    Folio / ID de la solicitud
                </label>
                <input
                    id="request-id-search"
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Ej. 15 o #15"
                    class="w-full px-4 py-2 text-gray-900 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                >
            </div>

            <div class="flex gap-2">
                <button
                    type="submit"
                    class="px-5 py-2 font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"
                >
                    Buscar
                </button>

                @if ($hasSearched)
                    <button
                        type="button"
                        wire:click="clearSearch"
                        class="px-4 py-2 font-medium text-white bg-gray-500 rounded-lg hover:bg-gray-600"
                    >
                        Limpiar
                    </button>
                @endif
            </div>
        </form>
    </div>

    @if ($invalidFormat)
        <div role="alert" class="p-4 text-sm rounded-lg text-amber-800 bg-amber-50 dark:bg-gray-800 dark:text-amber-300">
            Ingresa un número de folio válido (por ejemplo: <strong>1</strong> o <strong>#1</strong>).
        </div>
    @elseif ($hasSearched && ! $foundRequest)
        <div role="status" class="p-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400">
            No se encontró ninguna solicitud con el folio <strong>#{{ ltrim(trim($search), '#') }}</strong>.
        </div>
    @elseif ($foundRequest)
        {{-- Resumen de la Solicitud --}}
        <div class="p-6 space-y-6 bg-white rounded-lg shadow dark:bg-gray-800">

            <div class="flex flex-col gap-4 pb-4 border-b border-gray-200 dark:border-gray-700 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            Solicitud #{{ $foundRequest->id }}
                        </h3>
                        <span class="px-2.5 py-0.5 text-xs font-medium text-gray-700 bg-gray-100 rounded dark:bg-gray-700 dark:text-gray-200">
                            {{ $foundRequest->is_group ? 'Grupal' : 'Individual' }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Creada el {{ $foundRequest->created_at->format('d/m/Y H:i') }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    @switch($foundRequest->status)
                        @case('pending_area_manager')
                            <span class="px-3 py-1 text-sm font-semibold text-blue-800 bg-blue-100 rounded-full dark:bg-blue-900 dark:text-blue-200">
                                Pendiente Gerente de Área{{ $foundRequest->managerSuffixForRole('area_manager') }}
                            </span>
                        @break

                        @case('pending_hr_manager')
                            <span class="px-3 py-1 text-sm font-semibold text-purple-800 bg-purple-100 rounded-full dark:bg-purple-900 dark:text-purple-200">
                                Pendiente RH{{ $foundRequest->managerSuffixForRole('hr_manager') }}
                            </span>
                        @break

                        @case('pending_plant_manager')
                            <span class="px-3 py-1 text-sm font-semibold text-orange-800 bg-orange-100 rounded-full dark:bg-orange-900 dark:text-orange-200">
                                Pendiente Gerente de Planta{{ $foundRequest->managerSuffixForRole('plant_manager') }}
                            </span>
                        @break

                        @case('approved')
                            <span class="px-3 py-1 text-sm font-semibold text-green-800 bg-green-100 rounded-full dark:bg-green-900 dark:text-green-200">
                                Aprobada
                            </span>
                        @break

                        @case('rejected')
                            <span class="px-3 py-1 text-sm font-semibold text-red-800 bg-red-100 rounded-full dark:bg-red-900 dark:text-red-200">
                                Rechazada
                            </span>
                        @break
                    @endswitch

                    <a href="{{ route('requests.search.show', $foundRequest) }}"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                        Ver detalle de la solicitud
                    </a>
                </div>
            </div>

            {{-- Datos Generales --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $foundRequest->is_group ? 'Supervisor solicitante' : 'Solicitante' }}
                    </span>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ $foundRequest->employee?->full_name }} ({{ $foundRequest->employee?->employee_number }})
                    </p>
                </div>

                <div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Área</span>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ $foundRequest->area?->name ?? 'Sin área' }}
                    </p>
                </div>

                <div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Grupo / Línea · Semana ISO</span>
                    <p class="font-medium text-gray-900 dark:text-white">
                        {{ $foundRequest->group }} · Semana {{ $foundRequest->week }}
                    </p>
                </div>

                <div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">Vencimiento</span>
                    <div class="mt-1 dark:text-white">
                        <x-request-expiration :request="$foundRequest"  />
                    </div>
                </div>

                <div class="sm:col-span-2 lg:col-span-4">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Justificación</span>
                    <p class="mt-1 font-medium text-gray-900 dark:text-white">
                        {{ $foundRequest->reason }}
                    </p>
                </div>
            </div>

            {{-- Seguimiento de Aprobaciones por Nivel --}}
            <div>
                <h4 class="mb-3 text-base font-semibold text-gray-900 dark:text-white">
                    Seguimiento de Aprobaciones y Responsables
                </h4>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    @foreach ($stageDetails as $role => $stage)
                        <div class="p-4 border border-gray-200 rounded-lg dark:border-gray-700 dark:bg-gray-900">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $stage['label'] }}
                                </p>

                                @if ($stage['status'] === 'approved')
                                    <span class="px-2 py-0.5 text-xs font-semibold text-green-800 bg-green-100 rounded">
                                        Aprobado
                                    </span>
                                @elseif ($stage['status'] === 'rejected')
                                    <span class="px-2 py-0.5 text-xs font-semibold text-red-800 bg-red-100 rounded">
                                        Rechazado
                                    </span>
                                @elseif ($stage['status'] === 'pending')
                                    <span class="px-2 py-0.5 text-xs font-semibold text-amber-800 bg-amber-100 rounded">
                                        Pendiente
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 text-xs font-semibold text-gray-700 bg-gray-100 rounded">
                                        Cancelado
                                    </span>
                                @endif
                            </div>

                            @if ($stage['authorization'])
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Firmado por: <span class="font-medium text-gray-800 dark:text-gray-200">{{ $stage['authorization']->user?->full_name }}</span>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-100">
                                    Fecha: {{ $stage['authorization']->created_at->format('d/m/Y H:i') }}
                                </p>
                                @if ($stage['authorization']->reason)
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">
                                        Motivo: {{ $stage['authorization']->reason }}
                                    </p>
                                @endif
                            @else
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Asignado a: <span class="font-medium text-gray-800 dark:text-gray-200">{{ $stage['manager_name'] }}</span>
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if (! in_array($foundRequest->status, ['approved', 'rejected'], true))
                    <p class="mt-3 text-sm text-gray-600 dark:text-gray-300">
                        Faltan: {{ collect($missingApprovals)->map(fn ($role) => $foundRequest->roleLabelWithManager($role, ['area_manager' => 'Gerente de área', 'hr_manager' => 'RH', 'plant_manager' => 'Gerente de planta'][$role]))->join(', ') }}.
                    </p>
                @endif
            </div>

        </div>
    @endif

</div>
