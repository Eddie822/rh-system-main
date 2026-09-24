<div class="p-3 sm:p-4">

    {{-- Filtros --}}
    <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:flex-wrap">
        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o número de nómina..."
            class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 w-full sm:flex-1 sm:min-w-[220px]
                   bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                   placeholder-gray-400 dark:placeholder-gray-500
                   focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">

        <select wire:model.live="roleFilter"
            class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 w-full sm:w-auto sm:min-w-[180px]
                   bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                   focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
            <option value="">Todos los roles</option>
            @foreach ($roles as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>

        <select wire:model.live="areaFilter"
            class="border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 w-full sm:w-auto sm:min-w-[180px]
                   bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100
                   focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
            <option value="">Todas las áreas</option>
            @foreach ($areas as $area)
                <option value="{{ $area->id }}">{{ $area->name }}</option>
            @endforeach
        </select>

        @if ($search || $roleFilter || $areaFilter)
            <button wire:click="clearFilters"
                class="w-full px-3 py-2 text-gray-600 border border-gray-300 rounded-lg dark:border-gray-600 sm:w-auto dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                Limpiar filtros
            </button>
        @endif
    </div>

    {{-- Orden (visible solo en móvil) --}}
    <div class="flex items-center gap-2 mb-3 text-sm sm:hidden">
        <span class="text-gray-500 dark:text-gray-400">Ordenar por:</span>
        <button wire:click="sortBy('name')"
            class="font-medium {{ $sortField === 'name' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300' }}">
            Nombre {{ $sortField === 'name' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}
        </button>
        <span class="text-gray-300 dark:text-gray-600">|</span>
        <button wire:click="sortBy('employee_number')"
            class="font-medium {{ $sortField === 'employee_number' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300' }}">
            Nómina {{ $sortField === 'employee_number' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' }}
        </button>
    </div>

    {{-- Vista tabla (sm y arriba) --}}
    <div class="hidden overflow-x-auto border border-gray-200 rounded-lg sm:block dark:border-gray-700">
        <table class="min-w-full bg-white dark:bg-gray-800">
            <thead>
                <tr class="text-sm text-left text-gray-700 bg-gray-100 dark:bg-gray-900 dark:text-gray-300">
                    <th class="px-4 py-2 cursor-pointer select-none whitespace-nowrap"
                        wire:click="sortBy('employee_number')">
                        No. Nómina
                        @if ($sortField === 'employee_number')
                            {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                        @endif
                    </th>
                    <th class="px-4 py-2 cursor-pointer select-none" wire:click="sortBy('name')">
                        Nombre
                        @if ($sortField === 'name')
                            {{ $sortDirection === 'asc' ? '↑' : '↓' }}
                        @endif
                    </th>
                    <th class="px-2 PX-4">Apellidos</th>
                    <th class="px-4 py-2">Rol</th>
                    <th class="px-4 py-2">Área</th>
                    <th class="px-4 py-2">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($users as $user)
                    <tr wire:key="user-row-{{ $user->id }}"
                        class="text-sm text-gray-800 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                        <td class="px-4 py-2 whitespace-nowrap">{{ $user->employee_number }}</td>
                        <td class="px-4 py-2">{{ $user->name }}</td>
                        <td class="px-4 py-2">{{ $user->last_name }}</td>
                        <td class="px-4 py-2">{{ $roles[$user->role] ?? $user->role }}</td>
                        <td class="px-4 py-2">{{ $user->area->name ?? '—' }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('admin.users.show', $user) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium
                  bg-blue-50 text-blue-700 hover:bg-blue-100
                  dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50
                  transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Ver solicitudes
                                </a>

                                <a href="{{ route('admin.users.edit', $user) }}"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium
                  bg-amber-50 text-amber-700 hover:bg-amber-100
                  dark:bg-amber-900/30 dark:text-amber-300 dark:hover:bg-amber-900/50
                  transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Editar
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-gray-500 dark:text-gray-400">
                            No se encontraron usuarios.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Vista tarjetas (menor a sm) --}}
    <div class="space-y-3 sm:hidden">
        @forelse ($users as $user)
            <div wire:key="user-card-{{ $user->id }}"
                class="p-3 bg-white border border-gray-200 rounded-lg shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $user->last_name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">No. Nómina: {{ $user->employee_number }}
                        </p>
                    </div>
                    <span
                        class="px-2 py-1 text-xs text-gray-700 bg-gray-100 rounded-full dark:bg-gray-700 dark:text-gray-200">
                        {{ $roles[$user->role] ?? $user->role }}
                    </span>
                </div>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">{{ $user->area->name ?? 'Sin área' }}</p>
                <div class="flex gap-2 mt-3">
                    <a href="{{ route('admin.users.show', $user) }}"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium
              bg-blue-50 text-blue-700 hover:bg-blue-100
              dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50
              transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Ver solicitudes
                    </a>

                    <a href="{{ route('admin.users.edit', $user) }}"
                        class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium
              bg-amber-50 text-amber-700 hover:bg-amber-100
              dark:bg-amber-900/30 dark:text-amber-300 dark:hover:bg-amber-900/50
              transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Editar
                    </a>
                </div>
            </div>
        @empty
            <div class="py-6 text-center text-gray-500 dark:text-gray-400">
                No se encontraron usuarios.
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>
</div>
