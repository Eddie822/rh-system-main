<x-app-layout>
    <x-slot name="header" class="bg-white ">
        <div class="grid grid-cols-2 gap-6 items-start border p-4 rounded-lg shadow-sm bg-white dark:bg-gray-800 dark:border-gray-700 dark:text-white">
            <!-- LEFT side: Sentence -->
            <div class="flex items-center pl-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white">
                    {{ __('Autorización Horas Extras y/o Descansos Laborados') }}
                </h2>
            </div>

            <!-- RIGHT side: DATE + Date -->
            <div class="flex flex-col items-end pl-4 text-right border-l">
                <h2 class="text-s font-semibold text-gray-800 dark:text-white">
                    {{ __('Fecha:') }}
                </h2>
                <p class="text-s font-medium text-blue-600 border-t pl-2 dark:text-gray-400">
                    {{ date('d-m-Y') }}
                </p>
            </div>
        </div>
    </x-slot>

    <!-- Contenedor principal -->
    <div class="flex">
        <!-- Sidebar -->
        @include('layouts.partials.app.sidebar')

        <!-- Contenido principal -->
        <div class="flex-1 p-6 bg-white dark:bg-gray-900 dark:text-gray-200">
            <h3 class="text-lg font-semibold mb-4">Contenido principal</h3>

            <!-- Grupo y línea -->
            <div class="mb-4">
                <x-label for="group_line" value="{{ __('Grupo y línea que apoya') }}" />
                <x-input id="group_line" type="text" class="mt-1 block w-full sm:text-sm"
                    value="{{ Auth::user()->supervisor_id }}" readonly />
            </div>

            <!-- Justificación con dropdown -->
            <div class="mb-4" x-data="{ open: false, options: ['Vacación', 'No. nom a cubrir', 'Vacante', 'Sanción', 'Permiso sin goce', 'Incapacidad', 'Otros'], search: '' }">
                <x-label for="reason" value="{{ __('Justificación') }}" />
                <div class="relative w-64">
                    <input type="text" x-model="search" @focus="open = true"
                           @blur="setTimeout(() => open = false, 200)"
                           class="w-full border rounded-md px-3 py-2 dark:bg-gray-700 dark:text-gray-200"
                           placeholder="Buscar...">

                    <!-- Dropdown -->
                    <div x-show="open" class="absolute mt-1 w-full bg-white dark:bg-gray-800 border rounded-md shadow-lg z-10">
                        <template x-for="option in options.filter(o => o.toLowerCase().includes(search.toLowerCase()))" :key="option">
                            <div @click="search = option; open = false"
                                 class="px-3 py-2 cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-gray-200">
                                <span x-text="option"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <p class="mt-6">Aquí va la información o formulario que quieras mostrar al lado del sidebar.</p>
        </div>
    </div>
</x-app-layout>
