<x-guest-layout>

    <div class="max-w-md mx-auto mt-10">
        <div class="p-6 bg-white rounded-lg shadow dark:bg-gray-800">

            <h2 class="mb-2 text-2xl font-bold text-center text-gray-900 dark:text-white">
                Cambio obligatorio de contraseña
            </h2>

            <p class="mb-6 text-sm text-center text-gray-600 dark:text-gray-400">
                Debes actualizar tu contraseña antes de continuar.
            </p>

            <form action= "{{ route('password.update')  }}">
                @csrf

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Nueva contraseña
                    </label>

                    <input
                        type="password"
                        name="password"
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        required>

                    @error('password')
                        <p class="mt-1 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                        Confirmar contraseña
                    </label>

                    <input
                        type="password"
                        name="password_confirmation"
                        class="w-full px-3 py-2 border rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        required>
                </div>

                <button
                    type="submit"
                    class="w-full px-4 py-2 text-white bg-blue-600 rounded-lg hover:bg-blue-700">
                    Actualizar contraseña
                </button>

            </form>

        </div>
    </div>

</x-guest-layout>