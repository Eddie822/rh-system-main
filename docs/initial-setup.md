# Primer acceso y datos de ejemplo

En una base vacía, ejecuta:

```bash
./vendor/bin/sail artisan migrate --seed
```

Con PHP directamente: `php artisan migrate --seed`.

`DatabaseSeeder` ejecuta `InitialAccessSeeder` y crea el área Sistemas y una cuenta inicial:

- Número de nómina: `0356`.
- Nombre: Administrador Sistemas.
- Puesto: Responsable de Sistemas.
- Rol: gerente de área. Si Sistemas ya tiene gerente, se crea como empleado. Ambos tienen acceso al panel por pertenecer a Sistemas.
- Contraseña aleatoria de 20 caracteres, mostrada en la consola solo al crear la cuenta.

Ingresa en `/login` con esa nómina y la contraseña del comando. Cambia la contraseña cuando el sistema lo solicite. Abre **Panel de administrador → Usuarios → Importar usuarios** para cargar al personal. Los roles de supervisores y gerentes se asignan desde Editar usuario.

Si ya ejecutaste las migraciones pero falta el acceso inicial:

```bash
./vendor/bin/sail artisan db:seed
```

El seeder conserva los datos y la contraseña de cualquier cuenta existente. Si la nómina inicial ya pertenece a otro empleado, configura otra en `.env` y repite el comando:

```dotenv
RH_INITIAL_EMPLOYEE_NUMBER=RH-INICIAL
# Opcional: si se omite, se genera y muestra una contraseña aleatoria.
# RH_INITIAL_PASSWORD=tu-contraseña-temporal
```

Si hay configuración en caché, ejecuta `./vendor/bin/sail artisan config:clear` antes del seeder para tomar estos valores.

## Datos de ejemplo

En una instalación de desarrollo, puedes agregar empleados y solicitudes ficticios:

```bash
./vendor/bin/sail artisan db:seed --class=DemoDataSeeder
```

La demostración genera 104 usuarios y 141 solicitudes con todos los estados. Usa los dos apellidos, incluye apellidos maternos vacíos y asigna puestos. Los trabajadores no tienen supervisor ni jefe fijo. Las solicitudes grupales incluyen empleados de otras áreas y siguen el flujo del área del supervisor.

Incluye seis solicitudes grupales para probar alertas: una vencida y una por vencer en cada etapa de aprobación. Las fechas se calculan al crear los ejemplos; repetir el comando conserva esas fechas. Al terminar se muestran las nóminas de referencia: empleado `0001`, supervisor `0002`, gerente de Finanzas `0003`, gerente de RH `0004`, gerente de planta `0005`, gerente de Producción `0101` y Sistemas `DEMO-SIS-001`.

Las cuentas nuevas de demostración tienen contraseña `password` y cambio obligatorio al ingresar. La nómina `0356` queda excluida del demo y conserva todos sus datos y su contraseña. Si ya existe el acceso inicial, el total será de 105 usuarios. Repetir el seeder conserva usuarios, contraseñas, fechas y decisiones existentes. Si una nómina coincide con una cuenta de otro rol o área, se cancela toda la demostración para evitar mezclarla con personal real.

Los datos ficticios son opcionales. Ninguno de estos comandos borra tablas ni solicitudes existentes.
