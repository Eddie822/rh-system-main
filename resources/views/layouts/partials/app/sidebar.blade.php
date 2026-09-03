<aside id="logo-sidebar"
    class="w-64 min-h-screen bg-white border-r border-gray-200 dark:bg-gray-800 dark:border-gray-700
           transition-all duration-300"
    :class="{ 'hidden': !sidebarOpen }" aria-label="Sidebar">

    <!-- Header del sidebar -->
    <div class="flex justify-between items-center px-3">
        <h5 class="text-lg font-semibold text-gray-900 dark:text-white">
            Datos del empleado
        </h5>
        <button @click="sidebarOpen = !sidebarOpen"
            class="inline-flex items-center p-2 text-sm text-gray-500 rounded-lg hover:bg-gray-100
                   focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700
                   dark:focus:ring-gray-600">
            <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M3 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z"
                    clip-rule="evenodd"></path>
            </svg>
        </button>
    </div>

    <!-- Contenido del Sidebar -->
    <div class="flex-1 overflow-y-auto px-3 pb-4 pt-6">
        <!-- Inputs -->
        <div class="mb-4">
            <x-label for="employee_number" value="{{ __('N° de Nómina') }}" />
            <x-input id="employee_number" type="text" class="mt-1 block w-full sm:text-sm"
                value="{{ Auth::user()->employee_number }}" readonly />
        </div>

        <div class="mb-4">
            <x-label for="name" value="{{ __('Name') }}" />
            <x-input id="name" type="text" class="mt-1 block w-full sm:text-sm" value="{{ Auth::user()->name }}"
                readonly />
        </div>

        <div class="mb-4">
            <x-label for="last_name" value="{{ __('Apellidos') }}" />
            <x-input id="last_name" type="text" class="mt-1 block w-full sm:text-sm"
                value="{{ Auth::user()->last_name }}" readonly />
        </div>

        <div class="mb-4">
            <x-label for="groyp" value="{{ __('Grupo') }}" />
            <x-input id="group" type="text" class="mt-1 block w-full sm:text-sm"
                value="{{ Auth::user()->group }}" readonly />
        </div>

        <div class="mb-4">
            <x-label for="area" value="{{ __('Area') }}" />
            <x-input id="area" type="text" class="mt-1 block w-full sm:text-sm"
                value="{{ Auth::user()->area_id }}" readonly />
        </div>

        <div class="mb-4">
            <x-label for="supervisor_name" value="{{ __('Supervisor') }}" />
            <x-input id="supervisor_name" type="text" class="mt-1 block w-full sm:text-sm"
                value="{{ Auth::user()->supervisor_id }}" readonly />
        </div>
</aside>
