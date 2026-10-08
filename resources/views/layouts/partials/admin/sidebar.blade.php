
@php
    // Sidebar partial for admin layout
    // Menú lateral del panel administrativo (rutas temporales excepto Dashboard)

    $links = [
        [
            'icon' => 'fa-solid fa-gauge',
            'name' => 'Dashboard',
            'route' => route('admin.dashboard'),
            'active' => request()->routeIs('admin.dashboard'),
        ],
        // --- GESTIÓN DE PRODUCTOS ---
        // [
        //     //Familias de productos
        //     'icon' => 'fa-solid fa-box-open',
        //     'name' => 'Familias',
        //     'route' => route('admin.families.index'),
        //     'active' => request()->routeIs('admin.families.*'),
        // ],

        [
            'icon' => 'fa-solid fa-users',
            'name' => 'Usuarios',
            'route' => route('admin.users.index'),
            'active' => request()->routeIs('admin.users.*'),
        ],
    ];
    if (auth()->user()->can('manageAreas')) {
        $links[] = [
            'icon' => 'fa-solid fa-map-marker-alt',
            'name' => 'Áreas',
            'route' => route('admin.areas.index'),
            'active' => request()->routeIs('admin.areas.*'),
        ];
    }
    if (auth()->user()->can('importUsers')) {
        $links[] = [
            'icon' => 'fa-solid fa-file-import',
            'name' => 'Importar usuarios',
            'route' => route('admin.users.import.index'),
            'active' => request()->routeIs('admin.users.import.*'),
        ];
    }
    if (auth()->user()->can('viewReports')) {
        $links[] = [
            'icon' => 'fa-solid fa-file-excel',
            'name' => 'Reportes',
            'route' => route('admin.reports.index'),
            'active' => request()->routeIs('admin.reports.*'),
        ];
    }

@endphp

<aside id="logo-sidebar"
    class="fixed top-0 left-0 z-40 flex flex-col w-64 h-screen transition-transform -translate-x-full bg-white border-r border-gray-200 sm:translate-x-0 dark:bg-gray-800 dark:border-gray-700"
    :class="{
        'translate-x-0': sidebarOpen,
        '-translate-x-full': !sidebarOpen
    }"
    aria-label="Sidebar">

    <!-- Contenedor scroll interno -->
    <div class="flex-1 px-3 pt-20 pb-4 overflow-y-auto">
        <ul class="space-y-2 font-medium">
            @foreach ($links as $link)
                <li>
                    <a href="{{ $link['route'] }}"
                        class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white
                                hover:bg-gray-100 dark:hover:bg-gray-700 group
                                {{ $link['active'] ? 'bg-gray-100 dark:bg-gray-700' : '' }}">
                        <span class="inline-flex items-center justify-center w-6 h-6">
                            <i class="{{ $link['icon'] }}"></i>
                        </span>
                        <span class="ml-2">{{ $link['name'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</aside>
