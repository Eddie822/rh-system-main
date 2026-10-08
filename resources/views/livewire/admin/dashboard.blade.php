<div id="admin-dashboard" class="space-y-6">

    <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-900">
        Bienvenido al panel de adminstrador, {{ auth()->user()->name }}
    </h2>
    <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-900">
        Panel de control
    </h1>

    <div class="p-5 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label for="dashboard-area" class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200">Área</label>
                <select id="dashboard-area" wire:model.change="areaId" class="w-full border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">Todas las áreas</option>
                    @foreach ($areas as $area)
                        <option value="{{ $area->id }}">{{ $area->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="dashboard-from" class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200">Solicitud desde</label>
                <input id="dashboard-from" type="date" wire:model.change="dateFrom" class="w-full border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            </div>
            <div>
                <label for="dashboard-to" class="block mb-1 text-sm font-medium text-gray-700 dark:text-gray-200">Solicitud hasta</label>
                <input id="dashboard-to" type="date" wire:model.change="dateTo" class="w-full border-gray-300 rounded-lg dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            </div>
            <div class="flex items-end">
                <button type="button" wire:click="resetFilters" class="w-full px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-gray-700">Limpiar filtros</button>
            </div>
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Los filtros usan la fecha de creación de la solicitud y se aplican a indicadores y gráficas.</p>
        @if ($invalidDateRange)
            <p role="alert" class="mt-2 text-sm text-red-700 dark:text-red-300">La fecha inicial debe ser anterior o igual a la fecha final.</p>
        @endif
    </div>

    @if ($expiringSoon || $overdue)
        <div role="status" class="p-4 border rounded-lg text-amber-900 border-amber-200 bg-amber-50 dark:bg-gray-800 dark:text-amber-200">
            <h2 class="font-semibold">Atención a solicitudes pendientes (sin filtros)</h2>
            <p>{{ $expiringSoon }} por vencer en las próximos {{ config('requests.expiration_notice_hours') / 24 }} días · {{ $overdue }} vencidas.</p>
            <p class="mt-1 text-sm">El vencimiento no bloquea las aprobaciones.</p>
            @if (auth()->user()->canApprove())
                <a href="{{ route('approvals.index') }}" class="inline-block mt-2 underline">Revisar aprobaciones</a>
            @endif
        </div>
    @endif
    {{-- Indicadores --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-5">
        <div class="p-4 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
            <p class="text-xs text-gray-500 dark:text-gray-400">Aprobadas</p>
            <p class="mt-1 text-2xl font-bold text-green-600 dark:text-green-400">
                {{ $dashboardData['indicators']['approved'] }}
            </p>
        </div>

        <div class="p-4 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
            <p class="text-xs text-gray-500 dark:text-gray-400">Rechazadas</p>
            <p class="mt-1 text-2xl font-bold text-red-600 dark:text-red-400">
                {{ $dashboardData['indicators']['rejected'] }}
            </p>
        </div>

        <div class="p-4 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
            <p class="text-xs text-gray-500 dark:text-gray-400">Pendiente de autorización gerente de área</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">
                {{ $dashboardData['indicators']['pending_area_manager'] }}
            </p>
        </div>

        <div class="p-4 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
            <p class="text-xs text-gray-500 dark:text-gray-400">Pendiente de autorización de RH</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">
                {{ $dashboardData['indicators']['pending_hr_manager'] }}
            </p>
        </div>

        <div class="p-4 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
            <p class="text-xs text-gray-500 dark:text-gray-400">Pendiente de autorización <br> de gerente de planta</p>
            <p class="mt-1 text-2xl font-bold text-amber-600 dark:text-amber-400">
                {{ $dashboardData['indicators']['pending_plant_manager'] }}
            </p>
        </div>
    </div>

    {{-- Gráfico: solicitudes por área --}}
    <div class="p-5 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
        <h2 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">
            Solicitudes por área
        </h2>
        <div id="dashboard-requests-by-area" wire:ignore role="img" aria-label="Número de solicitudes por área"></div>
    </div>

    {{-- Gráficos por rol de autorización --}}
    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
        <div class="p-5 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Gerentes de área</h2>
            <div id="dashboard-area-manager" wire:ignore role="img" aria-label="Autorizaciones de gerentes de área"></div>
            <p id="dashboard-area-manager-empty" class="hidden text-sm text-center text-gray-500 dark:text-gray-400">Sin autorizaciones en este periodo.</p>
        </div>
        <div class="p-5 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Gerente de RH</h2>
            <div id="dashboard-hr-manager" wire:ignore role="img" aria-label="Autorizaciones del gerente de RH"></div>
            <p id="dashboard-hr-manager-empty" class="hidden text-sm text-center text-gray-500 dark:text-gray-400">Sin autorizaciones en este periodo.</p>
        </div>
        <div class="p-5 bg-white border border-gray-200 shadow-sm dark:bg-gray-800 dark:border-gray-700 rounded-xl">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Gerente de planta</h2>
            <div id="dashboard-plant-manager" wire:ignore role="img" aria-label="Autorizaciones del gerente de planta"></div>
            <p id="dashboard-plant-manager-empty" class="hidden text-sm text-center text-gray-500 dark:text-gray-400">Sin autorizaciones en este periodo.</p>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
            (() => {
                if (!window.ApexCharts) return;

                const initialData = @js($dashboardData);
                const charts = {};
                let currentData = initialData;
                let chartsReady = false;
                const renderJobs = [];
                const roles = ['area_manager', 'hr_manager', 'plant_manager'];
                const ids = {
                    area_manager: 'dashboard-area-manager',
                    hr_manager: 'dashboard-hr-manager',
                    plant_manager: 'dashboard-plant-manager',
                };

                const isDark = () => document.documentElement.classList.contains('dark');
                const themeOptions = () => {
                    const dark = isDark();
                    const text = dark ? '#e2e8f0' : '#334155';
                    return {
                        theme: { mode: dark ? 'dark' : 'light' },
                        chart: { background: 'transparent', foreColor: text },
                        grid: { borderColor: dark ? '#475569' : '#e2e8f0' },
                        tooltip: { theme: dark ? 'dark' : 'light' },
                        legend: { labels: { colors: text } },
                        xaxis: { labels: { style: { colors: text } } },
                        yaxis: { labels: { style: { colors: text } } },
                        noData: { text: 'Sin solicitudes en este periodo', style: { color: text } },
                    };
                };

                charts.areas = new ApexCharts(document.getElementById('dashboard-requests-by-area'), {
                    ...themeOptions(),
                    chart: { ...themeOptions().chart, type: 'bar', height: 320, toolbar: { show: false } },
                    colors: [isDark() ? '#60a5fa' : '#2563eb'],
                    plotOptions: { bar: { horizontal: true, borderRadius: 3 } },
                    dataLabels: { enabled: false },
                    series: [{ name: 'Solicitudes', data: initialData.areas.values }],
                    xaxis: { ...themeOptions().xaxis, categories: initialData.areas.labels },
                });
                renderJobs.push(charts.areas.render());

                roles.forEach(role => {
                    const decision = initialData.decisions[role];
                    charts[role] = new ApexCharts(document.getElementById(ids[role]), {
                        ...themeOptions(),
                        chart: { ...themeOptions().chart, type: 'donut', height: 280 },
                        colors: isDark() ? ['#34d399', '#f87171'] : ['#15803d', '#dc2626'],
                        labels: ['Aceptadas', 'Rechazadas'],
                        legend: { ...themeOptions().legend, position: 'bottom' },
                        dataLabels: { enabled: false },
                        series: [decision.approved, decision.rejected],
                    });
                    renderJobs.push(charts[role].render());
                });

                const updateCharts = data => {
                    currentData = data;
                    if (!chartsReady) return;
                    const colors = isDark() ? ['#34d399', '#f87171'] : ['#15803d', '#dc2626'];
                    charts.areas.updateOptions({
                        ...themeOptions(),
                        chart: { ...themeOptions().chart, height: Math.max(320, data.areas.labels.length * 38 + 90) },
                        colors: [isDark() ? '#60a5fa' : '#2563eb'],
                        xaxis: { ...themeOptions().xaxis, categories: data.areas.labels },
                        series: [{ name: 'Solicitudes', data: data.areas.values }],
                    }, false, false);
                    roles.forEach(role => {
                        const decision = data.decisions[role];
                        const empty = decision.approved + decision.rejected === 0;
                        document.getElementById(ids[role] + '-empty').classList.toggle('hidden', !empty);
                        charts[role].updateOptions({ ...themeOptions(), colors, legend: { ...themeOptions().legend, position: 'bottom' } }, false, false);
                        charts[role].updateSeries([decision.approved, decision.rejected], false);
                    });
                };

                window.addEventListener('dashboard-data-updated', event => updateCharts(event.detail.data));
                new MutationObserver(() => updateCharts(currentData)).observe(document.documentElement, {
                    attributes: true,
                    attributeFilter: ['class'],
                });
                Promise.all(renderJobs).then(() => {
                    chartsReady = true;
                    updateCharts(currentData);
                });
            })();
        </script>
    @endpush
</div>
