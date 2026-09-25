<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{-- Font Awesome --}}
    <script src="https://kit.fontawesome.com/6a3264df77.js" crossorigin="anonymous"></script>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('scripts')

    <!-- Styles -->
    @livewireStyles
</head>

<body class="font-sans antialiased" x-data="{ sidebarOpen: false }" :class="{ 'overflow-y-hidden': sidebarOpen }">

    <!-- Fondo oscuro móvil -->
    <div class="fixed inset-0 z-20 dark:bg-opacity-50 dark:bg-gray-900 sm:hidden " style="display: none"
        x-show="sidebarOpen" x-on:click="sidebarOpen = false">
    </div>

    @include('layouts.partials.admin.navigation')
    @include('layouts.partials.admin.sidebar')

    <!-- Contenedor principal -->
    <div class="p-4 sm:ml-64 ">
        <div class="mt-14">

            {{-- Botón (slot action) arriba izquierda --}}
            @isset($action)
                <div class="flex justify-end mb-4">
                    {{ $action }}
                </div>
            @endisset

            {{-- Contenido principal --}}
            <div class="p-4 border-2 border-gray-200 border-dashed rounded-lg dark:border-gray-700 ">
                {{ $slot }}
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @livewireScripts


    {{-- Save dark mode --}}
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Function to toogle and save
        function toggleDarkMode() {
            const html = document.documentElement;
            html.classList.toggle('dark');
            if (html.classList.contains('dark')) {
                localStorage.setItem('theme', 'dark');
            } else {
                localStorage.setItem('theme', 'light');
            }
        }
    </script>
    <script>
        // Parchea ApexCharts para capturar cada instancia creada
        (function() {
            window.__apexInstances = window.__apexInstances || [];
            const OriginalApexCharts = window.ApexCharts;

            if (OriginalApexCharts && !OriginalApexCharts.__patched) {
                window.ApexCharts = function(...args) {
                    const instance = new OriginalApexCharts(...args);
                    window.__apexInstances.push(instance);
                    return instance;
                };
                window.ApexCharts.prototype = OriginalApexCharts.prototype;
                window.ApexCharts.__patched = true;
            }
        })();

        function isDarkMode() {
            return document.documentElement.classList.contains('dark');
        }

        function applyChartTheme() {
            const dark = isDarkMode();
            (window.__apexInstances || []).forEach(chart => {
                chart.updateOptions({
                    theme: {
                        mode: dark ? 'dark' : 'light'
                    },
                    chart: {
                        foreColor: dark ? '#e5e7eb' : '#374151'
                    },
                    grid: {
                        borderColor: dark ? '#374151' : '#e5e7eb'
                    },
                }, false, false);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(applyChartTheme, 200);

            new MutationObserver(applyChartTheme).observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['class'],
            });
        });
    </script>

</body>

</html>
