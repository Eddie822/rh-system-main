<div class="space-y-6">

    <h2 class="text-lg font-semibold text-align-center">
        Bienvenido al panel de adminstrador, {{ auth()->user()->name }}
    </h2>
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-700">
        Panel de control
    </h1>

    {{-- Indicadores --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <div class="p-4 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
            <p class="text-xs text-gray-500 dark:text-gray-400">Aprobadas</p>
            <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">
                {{ $indicators['approved'] }}
            </p>
        </div>

        <div class="p-4 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
            <p class="text-xs text-gray-500 dark:text-gray-400">Rechazadas</p>
            <p class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">
                {{ $indicators['rejected'] }}
            </p>
        </div>

        <div class="p-4 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
            <p class="text-xs text-gray-500 dark:text-gray-400">Pendiente de autorización gerente de área</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">
                {{ $indicators['pending_area_manager'] }}
            </p>
        </div>

        <div class="p-4 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
            <p class="text-xs text-gray-500 dark:text-gray-400">Pendiente de autorización de RH</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">
                {{ $indicators['pending_hr_manager'] }}
            </p>
        </div>

        <div class="p-4 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
            <p class="text-xs text-gray-500 dark:text-gray-400">Pendiente de autorización <br> de gerente de planta</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">
                {{ $indicators['pending_plant_manager'] }}
            </p>
        </div>
    </div>

    {{-- Gráfico: solicitudes por área --}}
    <div class="p-5 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
        <h2 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">
            Solicitudes por área
        </h2>
        {{ $requestsByAreaChart->container() }}
    </div>

    {{-- Gráficos por rol de autorización --}}
    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
        <div class="p-5 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
            {{ $areaManagerChart->container() }}
        </div>
        <div class="p-5 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
            {{ $hrManagerChart->container() }}
        </div>
        <div class="p-5 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
            {{ $plantManagerChart->container() }}
        </div>
    </div>

    @push('scripts')
        <script src="{{ $requestsByAreaChart->cdn() }}"></script>

        {{ $requestsByAreaChart->script() }}
        {{ $areaManagerChart->script() }}
        {{ $hrManagerChart->script() }}
        {{ $plantManagerChart->script() }}
    @endpush
</div>
