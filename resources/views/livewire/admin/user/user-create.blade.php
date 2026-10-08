<div class="max-w-3xl p-6 mx-auto bg-white rounded-lg dark:bg-gray-800 dark:text-white">
    <h1 class="mb-2 text-xl font-semibold">Agregar usuario</h1>
    <p class="mb-5 text-sm text-gray-600 dark:text-gray-300">El usuario cambiará la contraseña inicial al iniciar sesión.</p>
    <form wire:submit="save" class="space-y-4">
        <x-validation-errors />
        <div class="grid gap-4 sm:grid-cols-2">
            <div><x-label for="employee_number" value="Número de nómina" /><x-input id="employee_number" wire:model="employee_number" class="w-full mt-1" required /></div>
            <div><x-label for="name" value="Nombre" /><x-input id="name" wire:model="name" class="w-full mt-1" required /></div>
            <div><x-label for="last_name" value="Apellidos" /><x-input id="last_name" wire:model="last_name" class="w-full mt-1" required /></div>
            <div><x-label for="role" value="Rol" /><select id="role" wire:model.live="role" class="w-full mt-1 border-gray-300 rounded-md dark:bg-gray-900">
                @foreach ($roles as $key => $label) <option value="{{ $key }}">{{ $label }}</option> @endforeach
            </select></div>
            <div><x-label for="area_id" value="Área" /><select id="area_id" wire:model.live="area_id" class="w-full mt-1 border-gray-300 rounded-md dark:bg-gray-900">
                <option value="">Sin área</option>@foreach ($areas as $area) <option value="{{ $area->id }}">{{ $area->name }}</option> @endforeach
            </select></div>
            <div><x-label for="group" value="Grupo / Línea" /><x-input id="group" wire:model="group" class="w-full mt-1" /></div>
            @if ($role !== 'plant_manager')
                <div><x-label for="direct_manager_number" value="Nómina del jefe directo" /><input type="text" id="direct_manager_number" wire:model="direct_manager_number" list="direct-manager-options" maxlength="50" placeholder="Ej. 0356" class="w-full mt-1 border-gray-300 rounded-md dark:bg-gray-900" @required(in_array($role, ['worker', 'supervisor'], true)) />
                    <datalist id="direct-manager-options">
                    @foreach ($directManagers as $manager) <option value="{{ $manager->employee_number }}">{{ $manager->employee_number }} — {{ $manager->name }}</option> @endforeach
                </datalist><x-input-error for="direct_manager_number" /></div>
            @endif
            <div><x-label for="password" value="Contraseña temporal" /><x-input id="password" type="password" wire:model="password" class="w-full mt-1" required minlength="8" /></div>
            <div><x-label for="password_confirmation" value="Confirmar contraseña" /><x-input id="password_confirmation" type="password" wire:model="password_confirmation" class="w-full mt-1" required minlength="8" /></div>
        </div>
        <div class="flex gap-3"><button class="px-4 py-2 text-white bg-blue-600 rounded-lg" wire:loading.attr="disabled">Crear usuario</button><a href="{{ route('admin.users.index') }}" class="px-4 py-2">Cancelar</a></div>
    </form>
</div>
