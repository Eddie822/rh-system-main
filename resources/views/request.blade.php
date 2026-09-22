<x-app-layout>
    <x-slot name="header" class="bg-white ">
        <div
            class="grid items-start grid-cols-1 gap-6 p-4 bg-white border rounded-lg shadow-sm md:grid-cols-2 dark:bg-gray-800 dark:border-gray-700 dark:text-white">
            <div class="flex items-center pl-4">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-white">
                    {{ __('Autorización Horas Extras y/o Descansos Laborados') }}
                </h2>
            </div>

            <div class="flex flex-col items-end pl-4 text-right md:border-l">
                <h2 class="font-semibold text-gray-800 text-s dark:text-white">
                    {{ __('Fecha:') }}
                </h2>
                <p class="pl-2 font-medium text-blue-600 border-t text-s dark:text-gray-400">
                    {{ date('d-m-Y') }}
                </p>
                <p class="text-xs text-gray-400 dark:text-gray-300">
                    Semana del año: {{ \Carbon\Carbon::now()->weekOfYear }}
                </p>
                <p class="text-sm">
                    Semana {{ \Carbon\Carbon::now()->weekOfYear }}:
                    del {{ \Carbon\Carbon::now()->startOfWeek()->format('d-m-Y') }}
                    al {{ \Carbon\Carbon::now()->endOfWeek()->format('d-m-Y') }}
                </p>
            </div>
        </div>
    </x-slot>

    <div x-data="{ sidebarOpen: false }" class="flex">
        <aside id="logo-sidebar"
            class="fixed inset-y-0 left-0 z-40 w-64 transition-transform duration-300 ease-in-out transform bg-white border-r dark:bg-gray-800 dark:border-gray-700 md:relative md:translate-x-0 md:block"
            :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }" aria-label="Sidebar">
            @include('layouts.partials.app.sidebar')
        </aside>

        <main class="flex-1 p-6 bg-white dark:bg-gray-900 dark:text-gray-200 ">
            <div class="flex justify-end mb-4 md:hidden">
                <button @click="sidebarOpen = !sidebarOpen"
                    class="inline-flex items-center p-2 text-sm text-gray-500 rounded-lg hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-200 dark:text-gray-400 dark:hover:bg-gray-700 dark:focus:ring-gray-600">
                    <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M3 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2zm0 5h14a1 1 0 010 2H3a1 1 0 010-2z"
                            clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>

            <livewire:requests.request-form />
            <p class="mt-6 text-center">
                1. El trabajador que cumpla con una Jornada de 48 hrs, solo podrá programar hasta 12 hrs como Descanso
                Laborado durante la misma semana de pago.
            </p>
            <p class="mt-1 text-center">
                2. El trabajador que cumpla con una Jornada de 36 hrs solo podrá programar 24 hrs como Descanso Laborado
                considerando un día de descanso intermedio durante la misma semana de pago.
            </p>
        </main>
    </div>
</x-app-layout>
