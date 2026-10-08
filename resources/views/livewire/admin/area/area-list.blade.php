<div class="space-y-4 dark:text-white">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold">Áreas</h1>
        <a href="{{ route('admin.areas.create') }}" class="px-4 py-2 text-white bg-blue-600 rounded-lg">Agregar área</a>
    </div>
    @if (session('success')) <p role="status" class="p-3 text-green-800 bg-green-50 rounded">{{ session('success') }}</p> @endif
    @if (session('error')) <p role="alert" class="p-3 text-red-800 bg-red-50 rounded">{{ session('error') }}</p> @endif
    <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar área" class="w-full px-3 py-2 border rounded-lg dark:bg-gray-800 sm:max-w-md" />
    <div class="overflow-x-auto bg-white rounded-lg dark:bg-gray-800">
        <table class="w-full text-left">
            <thead><tr class="border-b dark:border-gray-600"><th class="px-4 py-3">Nombre</th><th class="px-4 py-3">Usuarios</th><th class="px-4 py-3">Solicitudes</th><th class="px-4 py-3">Acciones</th></tr></thead>
            <tbody>
                @forelse ($areas as $area)
                    <tr wire:key="area-{{ $area->id }}" class="border-b dark:border-gray-700">
                        <td class="px-4 py-3">{{ $area->name }}</td><td class="px-4 py-3">{{ $area->users_count }}</td><td class="px-4 py-3">{{ $area->requests_count }}</td>
                        <td class="px-4 py-3"><div class="flex gap-3">
                            <a href="{{ route('admin.areas.edit', $area) }}" class="text-blue-700 dark:text-blue-300">Editar</a>
                            <form method="POST" action="{{ route('admin.areas.destroy', $area) }}" data-delete-label="el área" data-delete-name="{{ $area->name }}" onsubmit="confirmDeleteForm(event, this)">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-700 dark:text-red-300">Eliminar</button>
                            </form>
                        </div></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-5 text-center">No se encontraron áreas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $areas->links() }}
</div>
