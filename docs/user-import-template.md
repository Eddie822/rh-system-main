# Archivo para solicitar a RH

Descargue **Plantilla Excel** o **Lista completa de usuarios** desde **Panel de administrador → Importar usuarios**. Ambos archivos usan las mismas columnas para importar. La lista completa incluye los datos actuales de usuarios y áreas, sin contraseñas. Una fila por usuario; los encabezados de la fila 1 deben quedar exactamente así y en este orden:

| Columna | Encabezado | Obligatorio | Contenido |
| --- | --- | --- | --- |
| A | `Número de nómina` | Sí | Puede comenzar con cualquier dígito. Se aceptan celdas numéricas enteras; usa texto para conservar ceros iniciales (`0001`) o más de 15 dígitos. |
| B | `Nombre` | Sí | Nombre. |
| C | `Apellidos` | Sí | Apellidos. |
| D | `Rol` | Sí | `Empleado`, `Supervisor`, `Gerente de área`, `Gerente de RH`, `Gerente de planta`. |
| E | `Área` | Para Empleado, Supervisor y Gerente de área | Nombre del área. Si no existe, se crea en la tabla de áreas; la plantilla muestra las actuales en `Guía y áreas`. |
| F | `Grupo` | No | Grupo o línea. |
| G | `Nómina del jefe directo` | Para Empleado y Supervisor | Nómina del jefe, existente o incluido en cualquier fila de la misma hoja. Acepta número o texto. |

No pida contraseñas a RH. El sistema genera una contraseña distinta de 16 caracteres para cada usuario nuevo, la entrega en un Excel tras la importación y exige cambio al primer inicio de sesión. Quien hace la importación debe guardar el archivo de credenciales y distribuir las contraseñas de forma privada. El sistema no conserva una copia en texto de ese archivo.

Los empleados pueden reportar directamente al gerente de su área; en ese caso no se les asigna Supervisor. La jerarquía de jefe directo también puede ser `Empleado → Supervisor → Gerente de área → Gerente de planta`. `Gerente de RH` también reporta al gerente de planta. Los gerentes de área y RH pueden dejar vacía la columna G; si indican jefe, debe ser el gerente de planta. El gerente de planta deja esa columna vacía. Para los tres roles de gerente, el gerente de área asignado queda vacío. El Supervisor y el gerente de área se asignan automáticamente a partir del jefe directo y del área. El orden de las filas no importa: el jefe puede aparecer después de sus empleados. Debe haber un gerente de área por área, un gerente de RH y un gerente de planta.

Las nóminas ya existentes se omiten sin modificar esas cuentas. Si una nómina nueva aparece varias veces en el archivo, se importa solo su primera fila y se omiten las demás. Las áreas nuevas se guardan en la tabla de áreas y se asignan a sus usuarios. Las referencias a jefe directo y el resto de los datos de las filas nuevas se validan antes de guardar. Una fila nueva inválida cancela la importación completa; no se crean usuarios ni áreas. Límite: 5,000 usuarios y 5 MB por archivo `.xlsx`.

El acceso al panel de administrador depende del área: todo el personal de Sistemas y RH (Recursos Humanos) tiene acceso, independientemente de su puesto. El rol `admin` ya no se admite; use el rol real del puesto.

Los nombres de rol en español se traducen internamente: Empleado → `worker`, Supervisor → `supervisor`, Gerente de área → `area_manager`, Gerente de RH → `hr_manager`, Gerente de planta → `plant_manager`. Se aceptan mayúsculas y nombres sin acentos; los códigos anteriores siguen siendo compatibles.
