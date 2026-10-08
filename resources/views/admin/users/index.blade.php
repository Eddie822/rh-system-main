<x-admin-layout>
    <div class="flex flex-wrap justify-end gap-3 mb-4">
        @can('importUsers')
            <a href="{{ route('admin.users.import.index') }}" class="px-4 py-2 text-blue-700 border border-blue-300 rounded-lg">Importar Excel</a>
        @endcan
        <a href="{{ route('admin.users.create') }}" class="px-4 py-2 text-white bg-blue-600 rounded-lg">Agregar usuario</a>
    </div>
    <livewire:admin.user.user-list />
</x-admin-layout>
