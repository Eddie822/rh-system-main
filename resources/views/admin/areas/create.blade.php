<x-admin-layout>
    <div class="max-w-xl p-6 bg-white rounded-lg dark:bg-gray-800 dark:text-white">
        <h1 class="mb-4 text-xl font-semibold">Agregar área</h1>
        <form method="POST" action="{{ route('admin.areas.store') }}" class="space-y-4">
            @csrf
            <x-label for="name" value="Nombre del área" />
            <x-input id="name" name="name" value="{{ old('name') }}" required maxlength="255" class="w-full" />
            @error('name') <p class="text-red-600">{{ $message }}</p> @enderror
            <div class="flex gap-3"><button class="px-4 py-2 text-white bg-blue-600 rounded-lg">Guardar</button><a href="{{ route('admin.areas.index') }}" class="px-4 py-2">Cancelar</a></div>
        </form>
    </div>
</x-admin-layout>
