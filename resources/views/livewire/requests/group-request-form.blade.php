<div class="p-6 space-y-6 bg-white rounded-lg shadow dark:bg-gray-800 dark:text-gray-100">
    <div>
        <h1 class="text-2xl font-semibold">{{ $request ? 'Editar solicitud grupal #'.$request->id : 'Nueva solicitud grupal' }}</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Incluye empleados asignados a ti, con sus fechas y horas extra. Todas las fechas deben corresponder a la misma semana.</p>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">La solicitud completa pasa por Gerente de Área → RH → Gerente de Planta. Un rechazo aplica a todo el grupo.</p>
    </div>

    @if ($availableEmployees->isEmpty())
        <p role="status" class="p-4 text-amber-800 rounded-lg bg-amber-50">No tienes trabajadores asignados. Solicita a administración que asigne tus empleados antes de crear una solicitud.</p>
    @endif

    <p class="text-sm text-amber-800 dark:text-amber-200">El plazo vence al iniciar la primera fecha de horas extra. Se avisa desde cinco días antes del vencimiento; una solicitud vencida aún puede aprobarse.</p>

    <form wire:submit="save" class="space-y-6">
        <x-validation-errors />
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <x-label for="group-line" value="Grupo / Línea" />
                <x-input id="group-line" wire:model="group" class="w-full mt-1" maxlength="255" />
            </div>
            <div>
                <x-label for="group-reason" value="Justificación" />
                <x-input id="group-reason" wire:model="reason" class="w-full mt-1" maxlength="255" />
            </div>
        </div>

        @foreach ($employees as $index => $employee)
            <fieldset wire:key="employee-{{ $index }}" class="p-4 space-y-4 border rounded-lg dark:border-gray-600">
                <legend class="px-2 font-semibold">Empleado {{ $index + 1 }}</legend>
                <div class="flex flex-wrap items-end gap-3">
                    <div class="flex-1">
                        <x-label for="employee-{{ $index }}" value="Nómina — Empleado" />
                        <select id="employee-{{ $index }}" wire:model="employees.{{ $index }}.employee_number" class="w-full mt-1 border-gray-300 rounded-md dark:bg-gray-900 dark:text-white">
                            <option value="">Selecciona un empleado</option>
                            @foreach ($availableEmployees as $available)
                                <option value="{{ $available->employee_number }}">{{ $available->employee_number }} — {{ $available->name }} {{ $available->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if (count($employees) > 1)
                        <button type="button" data-delete-action="Quitar" data-delete-label="este empleado" onclick="confirmLivewireDeletion(event, this, 'removeEmployee', {{ $index }})" class="px-3 py-2 text-red-600">Quitar empleado</button>
                    @endif
                </div>

                @foreach ($employee['days'] as $dayIndex => $day)
                    <div wire:key="employee-{{ $index }}-day-{{ $dayIndex }}" class="grid items-end gap-3 sm:grid-cols-3">
                        <div>
                            <x-label for="date-{{ $index }}-{{ $dayIndex }}" value="Fecha" />
                            <x-input id="date-{{ $index }}-{{ $dayIndex }}" type="date" min="{{ now()->addDay()->format('Y-m-d') }}" wire:model="employees.{{ $index }}.days.{{ $dayIndex }}.day_date" class="w-full mt-1" />
                        </div>
                        <div>
                            <x-label for="hours-{{ $index }}-{{ $dayIndex }}" value="Horas extra" />
                            <x-input id="hours-{{ $index }}-{{ $dayIndex }}" type="number" min="0.01" max="12" step="0.01" wire:model="employees.{{ $index }}.days.{{ $dayIndex }}.hours" class="w-full mt-1" />
                        </div>
                        @if (count($employee['days']) > 1)
                            <button type="button" data-delete-action="Quitar" data-delete-label="esta fecha" onclick="confirmLivewireDeletion(event, this, 'removeDay', {{ $index }}, {{ $dayIndex }})" class="px-3 py-2 text-red-600">Quitar fecha</button>
                        @endif
                    </div>
                @endforeach
                @if (count($employee['days']) < 7)
                    <button type="button" wire:click="addDay({{ $index }})" class="px-3 py-2 text-blue-700 bg-blue-50 rounded-lg">Agregar fecha</button>
                @endif
            </fieldset>
        @endforeach

        <div class="flex flex-wrap gap-3">
            @if (count($employees) < 100)
                <button type="button" wire:click="addEmployee" class="px-4 py-2 border rounded-lg">Agregar empleado</button>
            @endif
            <button type="submit" wire:loading.attr="disabled" @disabled($availableEmployees->isEmpty()) class="px-4 py-2 text-white bg-blue-600 rounded-lg disabled:opacity-50">{{ $request ? 'Guardar cambios' : 'Enviar solicitud grupal' }}</button>
            <a href="{{ route('requests.index') }}" class="px-4 py-2 text-gray-600 dark:text-gray-300">Volver a mis solicitudes</a>
        </div>
    </form>
</div>
