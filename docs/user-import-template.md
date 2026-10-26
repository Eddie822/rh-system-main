# Importación masiva de empleados

Descarga la plantilla en Administración → Usuarios → Importar usuarios. Llena la primera hoja con una fila por persona y conserva estos encabezados y su orden:

| Area | num_emp | apell_pat | apell_mat | nombre | Puesto |
| --- | --- | --- | --- | --- | --- |
| Producción | 0007 | De la Cruz | López | Ana María | Operador |
| Almacén | 0008 | Pérez | | José | Montacarguista |

- `Area`: nombre del área, obligatorio. Se conserva la relación con la tabla de áreas. Si no existe se crea; las mayúsculas no crean otra área.
- `num_emp`: número de nómina obligatorio (máximo 50 caracteres). Configura la celda como texto antes de capturar ceros iniciales o más de 15 dígitos. Una nómina numérica debe ser un entero positivo o cero, de hasta 15 dígitos.
- `apell_pat`: apellido paterno obligatorio; se guarda en `apellidos_pat`.
- `apell_mat`: apellido materno opcional; se guarda en `apellido_mat`, como `null` si está vacío.
- `nombre`: nombre o nombres obligatorios.
- `Puesto`: puesto laboral obligatorio; sustituye al grupo del empleado. No asigna un rol de autorización. Los campos de texto excepto nómina admiten hasta 255 caracteres.

Todos los usuarios nuevos se crean con rol Empleado, sin supervisor ni jefe directo. Configura los roles Supervisor, Gerente de área, Gerente de RH y Gerente de planta desde la administración de usuarios. Los supervisores pueden incluir empleados de cualquier área; la aprobación corresponde al área del supervisor que registra la solicitud.

Sube el archivo como `.xlsx`, hasta 5 MB y 5,000 usuarios. No incluyas fórmulas. Las nóminas existentes o repetidas se omiten y sus datos no se modifican. Una fila nueva inválida cancela toda la importación, incluidas las áreas nuevas, y muestra su número de fila.

Al importar se descarga un Excel de credenciales con nómina, nombres, los dos apellidos y una contraseña temporal distinta por usuario. Guarda el archivo: solo se descarga una vez. Cada usuario debe cambiar su contraseña al ingresar. Los archivos grandes pueden tardar varios minutos por la generación de contraseñas individuales; espera la descarga antes de volver a enviar el archivo.

La lista completa se descarga con estas mismas seis columnas. Volver a importarla omite las nóminas existentes; no sirve para actualizar usuarios.

## Migración de datos existentes

La migración `2026_10_22_000001_restructure_employee_details` conserva el apellido anterior completo en `apellidos_pat`, sin intentar dividir apellidos compuestos; `apellido_mat` queda vacío. RH puede corregir los apellidos desde Editar usuario. `group` del usuario se renombra a `puesto` conservando sus valores, que RH debe revisar para sustituir los grupos anteriores por puestos reales. El grupo/línea de cada solicitud se conserva como dato de la operación.

Se elimina `users.supervisor_id` y se limpia `direct_manager_id` de los empleados. Se conservan las solicitudes, participantes y autorizaciones históricas. Al revertir la migración los apellidos se unen, pero las antiguas asignaciones a supervisores no se reconstruyen.

## Reportes

El reporte y su Excel incluyen todos los estados por fecha de trabajo (semana ISO de lunes a domingo, día o rango), respetando filtros de área de la solicitud y nómina del empleado. Cada fila corresponde a un empleado y una fecha; las solicitudes grupales conservan el mismo folio para sus participantes. Las horas totales incluyen pendientes, aprobadas y rechazadas.

El Excel agrega estado, nombres separados, puesto, área del empleado, área de la solicitud, fechas de registro y vencimiento, y decisión, responsable, fecha y motivo de rechazo de cada etapa de autorización. Los datos laborales reflejan el registro actual del empleado; las decisiones conservan el historial de la solicitud.
