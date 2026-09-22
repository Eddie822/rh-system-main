<div class="p-4 bg-white rounded-lg shadow-md dark:bg-gray-900">
    @if (session()->has('success'))
        <div class="p-2 mb-4 text-green-800 bg-green-100 rounded dark:bg-green-800 dark:text-green-100">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="submit" class="space-y-4">
        @csrf

        <!-- Grupo -->
        <div>
            <x-label for="group" value="Grupo y línea que apoya" class="dark:text-gray-200" />
            <x-input id="group" wire:model.defer="group" name="group"
                class="w-full dark:bg-gray-800 dark:text-white dark:border-gray-600" required />
            @error('group')
                <span class="text-sm text-red-500">{{ $message }}</span>
            @enderror
        </div>

        <!-- Justificación con dropdown -->
        <div x-data="{ reason: '', showOther: false }">
            <x-label for="reason" value="Justificación" class="dark:text-white" />

            <select id="reason" wire:model="reason" wire:change="updateReason"
                class="w-full px-3 py-2 border rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-600"
                required>
                <option value="">Selecciona justificación</option>
                <option value="Vacación">Vacación</option>
                <option value="No. nom a cubrir">No. nom a cubrir</option>
                <option value="Vacante">Vacante</option>
                <option value="Sanción">Sanción</option>
                <option value="Permiso sin goce">Permiso sin goce</option>
                <option value="Incapacidad">Incapacidad</option>
                <option value="Otros">Otros</option>
            </select>
            @if ($reason === 'Otros')
                <x-input id="reason_other" type="text" placeholder="Especifica la justificación"
                    wire:model="reason_other"
                    class="w-full mt-2 dark:bg-gray-800 dark:text-white dark:border-gray-600" />
            @endif

            @if ($reason === 'No. nom a cubrir')
                <x-input id="employee_number" type="text" placeholder="Número de nómina" wire:model="employee_number"
                    class="w-full mt-2 dark:bg-gray-800 dark:text-white dark:border-gray-600" />
            @endif
        </div>

        <!-- Tabla responsive -->
        <h3 class="mb-2 font-semibold dark:text-white">Fecha y horas</h3>

        <div class="overflow-x-auto">
            <table class="min-w-full mb-3 border-collapse table-auto">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-700">
                        <th class="p-2 text-left dark:text-gray-200">Fecha</th>
                        <th class="p-2 text-left dark:text-gray-200">Horas (H.h)</th>
                        <th class="p-2"></th>
                    </tr>
                </thead>


                <tbody>
                    @error('rows')
                        <div class="p-2 mb-3 text-red-600 bg-red-100 rounded">
                            {{ $message }}
                        </div>
                    @enderror
                    @foreach ($rows as $index => $row)
                        <tr class="border-t dark:border-gray-700">
                            <td class="p-2">
                                <input type="date" wire:model="rows.{{ $index }}.day_date"
                                    min="{{ now()->addDay()->format('Y-m-d') }}"
                                    class="w-full px-2 py-1 border rounded dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" />

                                @error("rows.$index.day_date")
                                    <span class="text-sm text-red-500">
                                        {{ $message }}
                                    </span>
                                @enderror
                            </td>
                            <td class="p-2">
                                <input 2 type="number" 3 step="0.5" 4 min="0" 5 max="12" 6
                                    wire:model="rows.{{ $index }}.hours" 7 placeholder="Ej. 1.5" 8
                                    class="w-full px-2 py-1 border rounded dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600"
                                    9 />
                                @error("rows.$index.hours")
                                    <span class="text-sm text-red-500">{{ $message }}</span>
                                @enderror
                            </td>

                            <td class="p-2 text-center">
                                <button type="button" wire:click="removeRow({{ $index }})"
                                    class="text-red-600 dark:text-red-400 hover:underline">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Botones -->
        <div class="flex flex-col gap-2 mb-4 sm:flex-row">
            <button type="button" wire:click.prevent="addRow"
                class="w-full px-3 py-2 text-white bg-green-500 rounded hover:bg-green-600 sm:w-auto">
                + Agregar día
            </button>
            <button type="submit" class="w-full px-4 py-2 text-white bg-blue-500 rounded hover:bg-blue-600 sm:w-auto">
                Enviar Solicitud
            </button>
        </div>
    </form>
</div>
