<x-admin-layout>
    <div class= "grid grid-cols-1 gap-6">
        <div class= "p-8 bg-white rounded-lg shadow-lg">
            <div class="flex items-center">
                <div class="flex-1 ml-4">
                    <h2 class="text-lg font-semibold text-align-center">
                        Bienvenido al panel de adminstrador, {{ auth()->user()->name }}
                    </h2>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
