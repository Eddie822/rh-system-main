<x-admin-layout>
    <div class="max-w-xl p-6 bg-white rounded-lg dark:bg-gray-800 dark:text-white">
        <h1 class="mb-4 text-xl font-semibold">Editar área</h1>
        <form method="POST" action="{{ route('admin.areas.update', $area) }}" class="space-y-4" onsubmit="confirmAreaEdit(event, this)">
            @csrf @method('PUT')
            <x-label for="name" value="Nombre del área" />
            <x-input id="name" name="name" value="{{ old('name', $area->name) }}" required maxlength="255" class="w-full" />
            @error('name') <p class="text-red-600">{{ $message }}</p> @enderror
            <div class="flex gap-3"><button class="px-4 py-2 text-white bg-blue-600 rounded-lg">Guardar cambios</button><a href="{{ route('admin.areas.index') }}" class="px-4 py-2">Cancelar</a></div>
        </form>
    </div>
    @push('scripts')
        <script>
            function confirmAreaEdit(event, form) {
                event.preventDefault();
                Swal.fire({ title: '¿Guardar cambios del área?', icon: 'question', showCancelButton: true,
                    confirmButtonText: 'Guardar', cancelButtonText: 'Cancelar' }).then(result => { if (result.isConfirmed) form.submit(); });
            }
        </script>
    @endpush
</x-admin-layout>
