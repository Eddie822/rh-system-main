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
                    <tr>
                        <td class="p-2">A</td>
                        <td class="p-2">Número de nómina</td>
                        <td class="p-2">Nómina única, obligatoria; texto solo para conservar ceros iniciales o más de 15 dígitos</td>
                    </tr>
                    <tr>
                        <td class="p-2">B</td>
                        <td class="p-2">Nombre</td>
                        <td class="p-2">Nombre, obligatorio</td>
                    </tr>
                    <tr>
                        <td class="p-2">C</td>
                        <td class="p-2">Apellidos</td>
                        <td class="p-2">Apellidos, obligatorios</td>
                    </tr>
                    <tr>
                        <td class="p-2">D</td>
                        <td class="p-2">Rol</td>
                        <td class="p-2">Empleado, Supervisor, Gerente de área, Gerente de RH, Gerente de planta</td>
                    </tr>
                    <tr>
                        <td class="p-2">E</td>
                        <td class="p-2">Área</td>
                        <td class="p-2">Nombre del área; si no existe se crea en la tabla de áreas. Obligatorio para
                            Empleado, Supervisor y Gerente de área</td>
                    </tr>
                    <tr>
                        <td class="p-2">F</td>
                        <td class="p-2">Grupo</td>
                        <td class="p-2">Grupo o línea, opcional</td>
                    </tr>
                    <tr>
                        <td class="p-2">G</td>
                        <td class="p-2">Nómina del jefe directo</td>
                        <td class="p-2">Obligatoria para Empleado y Supervisor; opcional para gerentes de área y RH;
                            vacía para gerente de planta. El jefe puede estar en cualquier fila de este archivo</td>
                    </tr>
                </tbody>
            </table>
            <p class="mt-4 text-sm">El Supervisor y el gerente de área se asignan automáticamente a partir del jefe directo y del área. El orden de las filas no importa: los jefes pueden aparecer después de sus equipos.
                Los trabajadores también pueden reportar directamente al gerente de su área y quedar sin Supervisor.
                La jerarquía también puede ser trabajador → Supervisor → gerente de área → gerente de planta; el gerente de RH también reporta al gerente de planta. Los gerentes de área y RH pueden dejar el jefe directo vacío;
                el gerente de área asignado queda vacío para los tres roles de gerente. Las nóminas ya existentes y las repetidas en el
                archivo se omiten; se conserva la primera fila nueva de cada nómina. Una fila nueva inválida cancela la
                importación completa y muestra el error. El archivo admite hasta 5,000 usuarios y 5 MB.</p>
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
