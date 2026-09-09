<div x-data="{ sidebarOpen: true }" class="flex h-screen">
    <!-- Sidebar -->
    <aside id="logo-sidebar"
        class="fixed inset-y-0 left-0 z-40 w-64 transform bg-white dark:bg-gray-800 border-r dark:border-gray-700
               transition-transform duration-300 ease-in-out
               md:relative md:translate-x-0 md:block"
        :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }" aria-label="Sidebar">

        <!-- Header del sidebar -->
        <div class="flex justify-between items-center px-3 border-b border-black dark:border-white">
            <h5 class="text-lg font-semibold text-gray-900 dark:text-white">
                Datos del empleado
            </h5>

            {{-- <!-- Botón hamburguesa dentro del sidebar -->
            <button @click="sidebarOpen = sidebarOpen"
                class="md:hidden inline-flex items-center p-2 text-sm text-gray-500 rounded-lg hover:bg-gray-100
           focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700
           dark:focus:ring-gray-600">
                <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M3 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z"
                        clip-rule="evenodd"></path>
                </svg>
            </button> --}}
        </div>

        <!-- Contenido del Sidebar -->
        <div class="flex-1 overflow-y-auto px-3 pb-4 pt-6">
            <div class="mb-4">
                <x-label for="employee_number" value="{{ __('N° de Nómina') }}" />
                <x-input id="employee_number" type="text" class="mt-1 block w-full sm:text-sm"
                    value="{{ Auth::user()->employee_number }}" readonly />
            </div>

            <div class="mb-4">
                <x-label for="name" value="{{ __('Name') }}" />
                <x-input id="name" type="text" class="mt-1 block w-full sm:text-sm"
                    value="{{ Auth::user()->name }}" readonly />
            </div>

            <div class="mb-4">
                <x-label for="last_name" value="{{ __('Apellidos') }}" />
                <x-input id="last_name" type="text" class="mt-1 block w-full sm:text-sm"
                    value="{{ Auth::user()->last_name }}" readonly />
            </div>

            <div class="mb-4">
                <x-label for="group" value="{{ __('Grupo') }}" />
                <x-input id="group" type="text" class="mt-1 block w-full sm:text-sm"
                    value="{{ Auth::user()->group ?? 'Sin grupo' }}" readonly />
            </div>

            <div class="mb-4">
                <x-label for="area" value="{{ __('Área') }}" />
                <x-input id="area" type="text" class="mt-1 block w-full sm:text-sm"
                    value="{{ Auth::user()->area?->name }}" readonly />
            </div>

            <div class="mb-4">
                <x-label for="supervisor_name" value="{{ __('Supervisor') }}" />
                <x-input id="supervisor_name" type="text" class="mt-1 block w-full sm:text-sm"
                    value="{{ Auth::user()->supervisor ? Auth::user()->supervisor->name : 'Supervisor no asignado' }}"
                    readonly />
            </div>
        </div>
    </aside>


</div>
