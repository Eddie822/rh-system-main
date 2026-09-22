<div class="max-w-6xl px-4 py-6 mx-auto sm:px-6 lg:px-8">

    <div class="p-4 bg-white rounded-lg shadow-md dark:bg-gray-900 sm:p-6">

        <h2 class="mb-6 text-xl font-semibold text-gray-900 sm:text-2xl dark:text-white">
            Editar Solicitud #{{ $request->id }}
        </h2>

        @if (session()->has('success'))
            <div class="p-3 mb-4 text-green-800 bg-green-100 rounded-lg dark:bg-green-800 dark:text-green-100">
                {{ session('success') }}
            </div>
        @endif

        @error('rows')
            <div class="p-3 mb-4 text-red-800 bg-red-100 rounded-lg dark:bg-red-800 dark:text-red-100">
                {{ $message }}
            </div>
        @enderror

        <form wire:submit="save" class="space-y-6">

            {{-- Grupo --}}
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                    Grupo y línea que apoya
                </label>

                <input type="text" wire:model="group"
                    class="w-full px-3 py-2 text-gray-900 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-600">

                @error('group')
                    <span class="text-sm text-red-500">
                        {{ $message }}
                    </span>
                @enderror
            </div>

            {{-- Justificación --}}
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                    Justificación
                </label>

                <input type="text" wire:model="reason"
                    class="w-full px-3 py-2 text-gray-900 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-600">

                @error('reason')
                    <span class="text-sm text-red-500">
                        {{ $message }}
                    </span>
                @enderror
            </div>

            {{-- Fechas y horas --}}
            <div>

                <div class="flex flex-col gap-3 mb-4 sm:flex-row sm:items-center sm:justify-between">

                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        Fechas y Horas
                    </h3>

                    <button type="button" wire:click="addRow"
                        class="w-full px-4 py-2 text-white bg-green-500 rounded-md sm:w-auto hover:bg-green-600">
                        + Agregar día
                    </button>

                </div>

                <div class="overflow-x-auto">

                    <table class="min-w-full border-collapse">

                        <thead>

                            <tr class="bg-gray-100 dark:bg-gray-700">

                                <th class="p-3 text-sm font-medium text-left text-gray-700 dark:text-gray-200">
                                    Fecha
                                </th>

                                <th class="p-3 text-sm font-medium text-left text-gray-700 dark:text-gray-200">
                                    Horas
                                </th>

                                <th class="p-3">
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @foreach ($rows as $index => $row)
                                <tr class="border-t border-gray-200 dark:border-gray-700">

                                    {{-- Fecha --}}
                                    <td class="p-2">

                                        <input type="date" wire:model="rows.{{ $index }}.day_date"
                                            min="{{ now()->addDay()->format('Y-m-d') }}"
                                            class="w-full px-2 py-2 text-gray-900 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-600">

                                        @error("rows.$index.day_date")
                                            <span class="text-sm text-red-500">
                                                {{ $message }}
                                            </span>
                                        @enderror

                                    </td>

                                    {{-- Horas --}}
                                    <td class="p-2">

                                        <input type="number" step="0.5" min="0" max="12"
                                            placeholder="Ej. 1.5" wire:model="rows.{{ $index }}.hours"
                                            class="w-full px-2 py-2 text-gray-900 bg-white border border-gray-300 rounded-md dark:bg-gray-800 dark:text-white dark:border-gray-600">

                                        @error("rows.$index.hours")
                                            <span class="text-sm text-red-500">
                                                {{ $message }}
                                            </span>
                                        @enderror

                                    </td>

                                    {{-- Acciones --}}
                                    <td class="p-2 text-center">

                                        @if (count($rows) > 1)
                                            <button type="button" wire:click="removeRow({{ $index }})"
                                                class="text-red-600 dark:text-red-400 hover:underline">
                                                Eliminar
                                            </button>
                                        @endif

                                    </td>

                                </tr>
                            @endforeach

                        </tbody>

                    </table>

                </div>

            </div>

            {{-- Botones --}}
            <div class="flex flex-col-reverse gap-3 pt-4 sm:flex-row">

                <a class="w-full px-4 py-2 font-medium text-white bg-red-600 rounded-md sm:w-auto hover:bg-red-700" href="{{ route('requests.index') }}">
                
                Cancelar
                </a>

                <button type="submit"
                    class="w-full px-4 py-2 font-medium text-white bg-blue-600 rounded-md sm:w-auto hover:bg-blue-700">
                    Guardar cambios
                </button>

            </div>

        </form>

    </div>

</div>
