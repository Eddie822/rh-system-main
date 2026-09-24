<div class="max-w-3xl p-4 mx-auto sm:p-6">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
            Detalle de usuario
        </h1>
        <a href="{{ route('admin.users.index') }}"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium
                  bg-gray-100 text-gray-700 hover:bg-gray-200
                  dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600
                  transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Volver al listado
        </a>
    </div>

    {{-- Tarjeta de datos del usuario --}}
    <div
        class="p-5 mb-6 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl sm:p-6">
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    {{ $user->name }} {{ $user->last_name }}
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    No. Nómina: {{ $user->employee_number }}
                </p>
            </div>
            <a href="{{ route('admin.users.edit', $user) }}"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium
                      bg-amber-50 text-amber-700 hover:bg-amber-100
                      dark:bg-amber-900/30 dark:text-amber-300 dark:hover:bg-amber-900/50
                      transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Editar
            </a>
        </div>

        <dl class="grid grid-cols-2 gap-4 mt-5 text-sm sm:grid-cols-4">
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Rol</dt>
                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $roles[$user->role] ?? $user->role }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Área</dt>
                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $user->area->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Grupo</dt>
                <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $user->group ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500 dark:text-gray-400">Gerente de área</dt>
                <dd class="font-medium text-gray-900 dark:text-gray-100">
                    {{ $user->areaManager ? $user->areaManager->name . ' ' . $user->areaManager->last_name : '—' }}
                </dd>
            </div>
        </dl>
    </div>

    {{-- Historial de solicitudes --}}
    <div class="p-5 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl sm:p-6">
        <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">
            Solicitudes de horas extra
        </h3>

        {{-- Tabla (sm y arriba) --}}
        <div class="hidden overflow-x-auto sm:block">
            <table class="min-w-full text-sm">
                <thead>
                    <tr
                        class="text-left text-gray-500 border-b border-gray-200 dark:text-gray-400 dark:border-gray-700">
                        <th class="py-2 pr-4">Semana</th>
                        <th class="py-2 pr-4">Fechas</th>
                        <th class="py-2 pr-4">Motivo</th>
                        <th class="py-2 pr-4">Días</th>
                        <th class="py-2 pr-4">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($requests as $request)
                        <tr wire:key="req-{{ $request->id }}" class="text-gray-800 dark:text-gray-100">
                            <td class="py-2 pr-4">{{ $request->week }}</td>
                            <td class="py-2 pr-4 whitespace-nowrap">
                                @if ($request->days->isNotEmpty())
                                    {{ $request->days->first()->day_date->format('d/m') }} -
                                    {{ $request->days->last()->day_date->format('d/m/Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-2 pr-4">{{ \Illuminate\Support\Str::limit($request->reason, 40) }}</td>
                            <td class="py-2 pr-4">{{ $request->days_count }}</td>
                            <td class="py-2 pr-4">
                                <span @class([
                                    'inline-flex px-2 py-1 rounded-full text-xs font-medium',
                                    'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300' =>
                                        $request->status === 'approved',
                                    'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300' =>
                                        $request->status === 'rejected',
                                    'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' => str_starts_with(
                                        $request->status,
                                        'pending'),
                                ])>
                                    {{ $statusLabels[$request->status] ?? $request->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-gray-500 dark:text-gray-400">
                                Este usuario no tiene solicitudes registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Tarjetas (móvil) --}}
        <div class="space-y-3 sm:hidden">
            @forelse ($requests as $request)
                <div wire:key="req-card-{{ $request->id }}"
                    class="p-3 border border-gray-200 rounded-lg dark:border-gray-700">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Semana {{ $request->week }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                @if ($request->days->isNotEmpty())
                                    {{ $request->days->first()->day_date->format('d/m') }} -
                                    {{ $request->days->last()->day_date->format('d/m/Y') }}
                                @else
                                    Sin fechas
                                @endif
                            </p>
                        </div>
                        <span @class([
                            'inline-flex px-2 py-1 rounded-full text-xs font-medium',
                            'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300' =>
                                $request->status === 'approved',
                            'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300' =>
                                $request->status === 'rejected',
                            'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' => str_starts_with(
                                $request->status,
                                'pending'),
                        ])>
                            {{ $statusLabels[$request->status] ?? $request->status }}
                        </span>
                    </div>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        {{ \Illuminate\Support\Str::limit($request->reason, 60) }}
                    </p>
                    <p class="mt-1 text-xs text-gray-400">{{ $request->days_count }} día(s) reportado(s)</p>
                </div>
            @empty
                <div class="py-6 text-center text-gray-500 dark:text-gray-400">
                    Este usuario no tiene solicitudes registradas.
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $requests->links() }}
        </div>
    </div>
</div>
