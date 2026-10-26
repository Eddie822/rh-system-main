<x-admin-layout>
    <div class="max-w-5xl space-y-6 text-gray-900 dark:text-white">
        <div>
            <h1 class="text-2xl font-semibold dark:text-gray-900">Importar usuarios</h1>
            <p class="mt-2 text-sm dark:text-gray-700">Descarga la plantilla o la lista completa de usuarios y pide a RH que llene las
                mismas columnas, una fila por persona. No incluyas contraseñas: el sistema genera una diferente para
                cada usuario nuevo.</p>
        </div>
        @if (session('success'))
            <p role="status" class="p-4 text-green-800 rounded bg-green-50">{{ session('success') }}</p>
        @endif
        <div class="p-5 bg-white rounded-lg dark:bg-gray-800">
            <a href="{{ route('admin.users.import.template') }}"
                class="inline-block px-4 py-2 text-white bg-blue-600 rounded-lg">Descargar plantilla Excel</a>
            <a href="{{ route('admin.users.import.roster') }}"
                class="inline-block px-4 py-2 ml-2 text-white bg-blue-600 rounded-lg">Descargar lista completa de
                usuarios</a>
            <p class="mt-3 text-sm">La plantilla incluye una hoja con las áreas disponibles en este momento. Los números de nómina pueden comenzar con cualquier dígito y pueden ser celdas numéricas. Usa celdas de texto si necesitas conservar ceros iniciales o más de 15 dígitos.</p>
        </div>
        <div class="p-5 overflow-x-auto bg-white rounded-lg dark:bg-gray-800">
            <h2 class="mb-3 font-semibold">Columnas de la primera hoja, en este orden</h2>
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b">
                        <th class="p-2">Columna</th>
                        <th class="p-2">Encabezado exacto</th>
                        <th class="p-2">Cómo llenarla</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ([
                        ['A', 'Area', 'Nombre del área, obligatorio. Si no existe, se crea.'],
                        ['B', 'num_emp', 'Número de nómina obligatorio. Usa texto para conservar ceros iniciales.'],
                        ['C', 'apell_pat', 'Apellido paterno obligatorio.'],
                        ['D', 'apell_mat', 'Apellido materno opcional; puede quedar vacío.'],
                        ['E', 'nombre', 'Nombre o nombres obligatorios.'],
                        ['F', 'Puesto', 'Puesto laboral obligatorio. No determina el rol de acceso.'],
                    ] as $column)
                        <tr>@foreach ($column as $value)<td class="p-2">{{ $value }}</td>@endforeach</tr>
                    @endforeach
                </tbody>
            </table>
            <p class="mt-4 text-sm">Los usuarios se crean como empleados sin supervisor ni jefe directo asignado. Los supervisores pueden incluir empleados de cualquier área en sus solicitudes. Los roles de autorización se configuran desde administración.</p>
            <p class="mt-3 text-sm">Las nóminas existentes o repetidas se omiten sin modificar sus datos. Una fila nueva inválida cancela toda la importación. Hasta 5,000 usuarios y 5 MB por archivo .xlsx. No uses fórmulas.</p>
            <p class="mt-3 text-sm">Los archivos grandes pueden tardar varios minutos. Espera a que se descarguen las credenciales antes de volver a enviar el archivo.</p>
        </div>
        <div class="p-5 bg-white rounded-lg dark:bg-gray-800">
            <h2 class="mb-2 font-semibold">Áreas disponibles en la base de datos</h2>
            <p class="text-sm">{{ $areas->pluck('name')->join(', ') ?: 'No hay áreas registradas.' }}</p>
        </div>
        <form method="POST" action="{{ route('admin.users.import.store') }}" enctype="multipart/form-data"
            class="p-5 bg-white rounded-lg dark:bg-gray-800">
            @csrf
            <label for="file" class="block mb-2 font-semibold">Archivo .xlsx de RH</label>
            <input id="file" name="file" type="file" accept=".xlsx" required class="block w-full" />
            @if ($errors->any())
                <div role="alert" class="p-4 mt-4 text-red-800 rounded bg-red-50">
                    <p class="font-semibold">No se importó ningún usuario:</p>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <button class="px-4 py-2 mt-4 text-white bg-green-700 rounded-lg">Validar e importar usuarios</button>
        </form>
    </div>
</x-admin-layout>
