# Solicitudes y autorizaciones

## Asignación de gerentes

En Administración → Usuarios → Crear o Editar, selecciona Supervisor y captura la nómina del gerente asignado. Puede ser un gerente de área, de RH o de planta de cualquier área. La pantalla muestra nombre completo, nómina y área antes de guardar. El supervisor conserva su propia área; `area_manager_id` y `direct_manager_id` apuntan al gerente elegido.

Los empleados no tienen supervisor fijo. Un supervisor puede incluir empleados de cualquier área en una solicitud grupal, con fechas de la misma semana ISO, hasta siete días por empleado y 12 horas diarias (dos decimales). Se permiten hasta 100 empleados por solicitud.

## Autorizaciones en cualquier orden

Cada solicitud requiere estas tres responsabilidades:

- Gerente de área asignado al supervisor.
- Gerente de Recursos Humanos.
- Gerente de planta.

Cualquiera puede autorizar primero. Cada quien ve sus solicitudes pendientes aunque falten otras firmas. Si una persona cubre dos responsabilidades (por ejemplo, planta y gerente asignado al supervisor), una aceptación registra ambas, con dos entradas de auditoría. Solo queda aprobada cuando están las tres firmas; un rechazo con motivo termina la solicitud completa.

Los estados `pending_area_manager`, `pending_hr_manager` y `pending_plant_manager` indican la primera firma que falta, pero no limitan el orden. El seguimiento muestra todas las firmas pendientes y cuáles registrará el usuario al aceptar. Una persona no puede repetir su decisión. No se puede editar una solicitud después de la primera firma, incluso si continúa pendiente de gerente de área.

`requests.assigned_area_manager_id` conserva el gerente responsable al crear la solicitud, para que reasignar un supervisor no cambie solicitudes anteriores. Para registros históricos, la migración conserva primero a quien ya autorizó como gerente de área; en su defecto usa la asignación del supervisor o el gerente del área original. Las solicitudes antiguas sin gerente disponible usan el gerente del área cuando se registra.

El supervisor ve a todos los participantes; cada empleado solo ve sus propias fechas y horas. La aprobación y el rechazo afectan al grupo completo y se registran dentro de una transacción con bloqueo de la solicitud.

## Eliminación de usuarios

En Administración → Usuarios, marca las casillas o usa Seleccionar esta página. La selección puede incluir usuarios de varias páginas (máximo 500). Eliminar seleccionados pide confirmación y muestra cuántos se eliminaron y qué nóminas se conservaron. Se protege la propia cuenta, el historial de solicitudes y autorizaciones, y los responsables con personal o solicitudes asignadas. Los mismos criterios se usan al eliminar individualmente.

## Verificación

```bash
./vendor/bin/sail artisan test tests/Feature/FlexibleApprovalsTest.php tests/Feature/GroupRequestTest.php tests/Feature/UserAdministrationTest.php tests/Feature/RequestApproverMigrationTest.php
```

Las pruebas usan SQLite en memoria. Cubren los seis órdenes de autorización, firmas dobles, rechazo completo, privacidad, edición posterior a firmas, asignación entre áreas, eliminación múltiple y conservación del historial.
