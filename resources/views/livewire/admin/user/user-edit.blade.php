<div class="max-w-2xl p-4 mx-auto sm:p-6">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-900">
            Editar usuario
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

    @if ($showSuccess)
        <div class="flex items-center gap-2 px-4 py-3 mb-4 text-sm text-green-700 rounded-lg bg-green-50 dark:bg-green-900/30 dark:text-green-300"
            x-data x-init="setTimeout(() => $wire.showSuccess = false, 3000)">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            Usuario actualizado correctamente.
        </div>
    @endif

    <form wire:submit="save"
        class="p-5 space-y-5 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl sm:p-6">

        {{-- Nombre + Apellido --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                <input type="text" wire:model="name"
                    class="w-full border rounded-lg px-3 py-2 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100
                           border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400
                           @error('name') border-red-400 focus:ring-red-400 @enderror">
                @error('name')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Apellido</label>
                <input type="text" wire:model="last_name"
                    class="w-full border rounded-lg px-3 py-2 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100
                           border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400
                           @error('last_name') border-red-400 focus:ring-red-400 @enderror">
                @error('last_name')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="pt-5 border-t border-gray-200 dark:border-gray-700">
            <p class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                Cambiar contraseña <span class="text-xs font-normal text-gray-400">(opcional)</span>
            </p>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Nueva contraseña</label>
                    <input type="password" wire:model="password" placeholder="Dejar en blanco para no cambiar"
                        class="w-full border rounded-lg px-3 py-2 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100
                       border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400
                       @error('password') border-red-400 focus:ring-red-400 @enderror">
                    @error('password')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block mb-1 text-xs text-gray-500 dark:text-gray-400">Confirmar contraseña</label>
                    <input type="password" wire:model="password_confirmation"
                        class="w-full px-3 py-2 text-gray-900 bg-white border border-gray-300 rounded-lg dark:bg-gray-900 dark:text-gray-100 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
                </div>
            </div>
        </div>


        {{-- No. Nómina --}}
        <div>
            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Número de nómina</label>
            <input type="text" wire:model="employee_number"
                class="w-full border rounded-lg px-3 py-2 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100
                       border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400
                       @error('employee_number') border-red-400 focus:ring-red-400 @enderror">
            @error('employee_number')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Rol + Grupo --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Rol</label>
                <select wire:model.live="role"
                    class="w-full border rounded-lg px-3 py-2 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100
                           border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400
                           @error('role') border-red-400 focus:ring-red-400 @enderror">
                    <option value="">Selecciona un rol</option>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('role')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Grupo</label>
                <input type="text" wire:model="group" placeholder="Ej. Turno A"
                    class="w-full border rounded-lg px-3 py-2 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100
                           border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400
                           @error('group') border-red-400 focus:ring-red-400 @enderror">
                @error('group')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Área --}}
        <div>
            <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Área</label>
            <select wire:model="area_id"
                class="w-full border rounded-lg px-3 py-2 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100
                       border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400
                       @error('area_id') border-red-400 focus:ring-red-400 @enderror">
                <option value="">Sin área</option>
                @foreach ($areas as $area)
                    <option value="{{ $area->id }}">{{ $area->name }}</option>
                @endforeach
            </select>
            @error('area_id')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>

        {{-- Gerente de área asignado (solo si es worker) --}}
        @if ($role === 'worker')
            <div>
                <label class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">Gerente de área
                    asignado</label>
                <select wire:model="area_manager_id"
                    class="w-full border rounded-lg px-3 py-2 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100
                           border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400
                           @error('area_manager_id') border-red-400 focus:ring-red-400 @enderror">
                    <option value="">Sin asignar</option>
                    @foreach ($areaManagers as $manager)
                        <option value="{{ $manager->id }}">{{ $manager->name }} {{ $manager->last_name }}</option>
                    @endforeach
                </select>
                @error('area_manager_id')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        @endif

        {{-- Acciones --}}
        <div class="flex items-center gap-3 pt-2">
            <button type="submit"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white transition-colors bg-blue-600 rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400 disabled:opacity-60"
                wire:loading.attr="disabled" wire:target="save">
                <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" xmlns="http://www.w3.org/2000/svg"
                    fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                        stroke-width="4">
                    </circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Guardar cambios
            </button>
            <a href="{{ route('admin.users.index') }}"
                class="px-4 py-2 text-sm font-medium text-gray-600 transition-colors rounded-lg dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                Cancelar
            </a>
        </div>
    </form>
</div>
