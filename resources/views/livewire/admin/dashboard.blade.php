<div class="space-y-6">

    <h2 class="text-lg font-semibold text-align-center">
        Bienvenido al panel de adminstrador, {{ auth()->user()->name }}
    </h2>
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-700">
        Panel de control
    </h1>

    {{-- Indicadores --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400">Aprobadas</p>
            <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">
                {{ $indicators['approved'] }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400">Rechazadas</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                {{ $indicators['rejected'] }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400">Pend. Gerente de Área</p>
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                {{ $indicators['pending_area_manager'] }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400">Pend. RH</p>
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                {{ $indicators['pending_hr_manager'] }}
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400">Pend. Planta</p>
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">
                {{ $indicators['pending_plant_manager'] }}
            </p>
        </div>
    </div>

    {{-- Gráfico: solicitudes por área --}}
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
            Solicitudes por área
        </h2>
        {{ $requestsByAreaChart->container() }}
    </div>

    {{-- Gráficos por rol de autorización --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm">
            {{ $areaManagerChart->container() }}
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm">
            {{ $hrManagerChart->container() }}
        </div>
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 shadow-sm">
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
