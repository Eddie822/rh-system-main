<div class="bg-white dark:bg-gray-900 p-4 rounded-lg shadow-md">
    @if (session()->has('success'))
        <div class="bg-green-100 dark:bg-green-800 text-green-800 dark:text-green-100 p-2 rounded mb-4">
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
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <!-- Justificación con dropdown -->
        <div x-data="{ reason: '', showOther: false }">
            <x-label for="reason" value="Justificación" class="dark:text-white" />

            <select id="reason" wire:model="reason" wire:change="updateReason"
                class="w-full border rounded-md px-3 py-2 dark:bg-gray-800 dark:text-white dark:border-gray-600"
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
                    class="w-full dark:bg-gray-800 dark:text-white dark:border-gray-600 mt-2" />
            @endif

            @if ($reason === 'No. nom a cubrir')
                <x-input id="employee_number" type="text" placeholder="Número de nómina" wire:model="employee_number"
                    class="w-full dark:bg-gray-800 dark:text-white dark:border-gray-600 mt-2" />
            @endif
        </div>

        <!-- Tabla responsive -->
        <h3 class="font-semibold mb-2 dark:text-white">Días y horas</h3>

        <div class="overflow-x-auto">
            <table class="min-w-full table-auto border-collapse mb-3">
                <thead>
                    <tr class="bg-gray-100 dark:bg-gray-700">
                        <th class="p-2 text-left dark:text-gray-200">Día</th>
                        <th class="p-2 text-left dark:text-gray-200">Horas (HH:mm)</th>
                        <th class="p-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $index => $row)
                        <tr class="border-t dark:border-gray-700">
                            <td class="p-2">
                                <select wire:model="rows.{{ $index }}.day_name"
                                    wire:change="updateDayDate({{ $index }})"
                                    class="border rounded px-2 py-1 w-full dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600">

                                    <option value="">Selecciona día</option>
                                    <option value="Lunes">Lunes</option>
                                    <option value="Martes">Martes</option>
                                    <option value="Miércoles">Miércoles</option>
                                    <option value="Jueves">Jueves</option>
                                    <option value="Viernes">Viernes</option>
                                    <option value="Sábado">Sábado</option>
                                    <option value="Domingo">Domingo</option>
                                </select>

                                @error("rows.$index.day_name")
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror

                                <!-- Mostrar fecha inmediatamente -->
                                @if (!empty($row['day_name']))
                                    <p class="text-xs text-gray-500 mt-1">
                                        Fecha: {{ $this->getDateForDay($row['day_name']) }}
                                    </p>
                                @endif
                            </td>
                            <td class="p-2">
                                <input type="number" x-data="{ val: '' }"
                                    x-on:input="
           // solo números
           let v = $el.value.replace(/[^0-9]/g,'');
           if(v.length >= 4){
               let hh = parseInt(v.slice(0,2));
               let mm = parseInt(v.slice(2,4));

               // limitar a 12 horas máximo
               if(hh > 12) hh = 12;
               if(mm > 59) mm = 59;

               $el.value = String(hh).padStart(2,'0') + ':' + String(mm).padStart(2,'0');
           }
       "
                                    maxlength="5" placeholder="HH:mm" wire:model="rows.{{ $index }}.hours"
                                    class="border rounded px-2 py-1 w-full dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600" />

                                @error("rows.$index.hours")
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
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
        <div class="flex flex-col sm:flex-row gap-2 mb-4">
            <button type="button" wire:click.prevent="addRow"
                class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded w-full sm:w-auto">
                + Agregar día
            </button>
            <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded w-full sm:w-auto">
                Enviar Solicitud
            </button>
        </div>
    </form>
</div>
