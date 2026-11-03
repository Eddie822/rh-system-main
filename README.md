# Sistema de Recursos Humanos — Control de Horas Extra y Descansos Laborados

Sistema web interno para el registro, seguimiento, autorización y reporte de solicitudes grupales de horas extra y descansos laborados.

Consulta [Primer acceso y datos de ejemplo](docs/initial-setup.md) para más detalles sobre la cuenta inicial de Sistemas.

---

## Características principales

- **Solicitudes grupales de tiempo extra:** Creación de solicitudes por supervisores y gerentes incluyendo múltiples trabajadores, días y horas por semana ISO.
- **Flujo de autorización multinivel:**
  - **Gerente de Área** (con soporte para múltiples gerentes por área y asignación directa).
  - **Gerente de Recursos Humanos (RH)**.
  - **Gerente de Planta**.
  - Si RH o Gerente de Planta crean una solicitud, su aprobación cubre automáticamente la firma de área.
- **Control de vencimientos:** Cálculo automático de fecha límite (martes de la semana siguiente) con indicadores de solicitudes por vencer y vencidas.
- **Panel de Administración (Sistemas y RH):**
  - Dashboard con indicadores y gráficas por estado y área.
  - Gestión de usuarios y áreas (alta, edición, baja individual o masiva).
  - **Importación y actualización masiva por Excel (`.xlsx`):** Alta de nuevos empleados con generación de contraseñas temporales y actualización automática de Área y Puesto para empleados existentes.
  - **Reportes en Excel:** Exportación de horas extra aprobadas filtradas por semana, área y fechas.

---

## Tecnologías

- **Backend:** PHP 8.4 / Laravel 12 / Livewire 3 / Jetstream
- **Base de datos:** MySQL 8.4
- **Frontend:** Tailwind CSS / Alpine.js / Vite (con soporte Dark Mode)
- **Infraestructura:** Docker & Docker Compose (Nginx + PHP-FPM + MySQL)

---

## Roles del sistema

| Rol | Permisos principales |
| :--- | :--- |
| **Trabajador (`worker`)** | Consulta el historial y estado de las solicitudes donde está incluido. |
| **Supervisor (`supervisor`)** | Crea y edita solicitudes grupales de horas extra (mientras no tengan firmas). |
| **Gerente de Área (`area_manager`)** | Crea solicitudes grupales y autoriza/rechaza las solicitudes asignadas a su cargo. |
| **Gerente de RH (`hr_manager`)** | Crea solicitudes grupales y autoriza/rechaza nivel RH en todas las solicitudes. |
| **Gerente de Planta (`plant_manager`)** | Crea solicitudes grupales y autoriza/rechaza nivel Planta en todas las solicitudes. |
| **Personal de Sistemas / RH** | Acceso al Panel de Administrador (usuarios, áreas, importación Excel y reportes). |

---

## Despliegue en Producción (Docker + LAN)

### 1. Clonar el repositorio (en Linux o WSL2)
```bash
git clone <URL_DEL_REPOSITORIO>
cd rh-system-main
```

### 2. Configurar variables de entorno
```bash
cp .env.example .env
```
Editar `.env`:
```env
APP_NAME="RH Extras"
APP_ENV=production
APP_DEBUG=false
APP_URL=
HTTP_PORT=80

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=hr-extras
DB_USERNAME=sail
DB_PASSWORD=password
FORWARD_DB_PORT=1433
```

### 3. Construir y levantar contenedores
```bash
docker compose -f compose.prod.yaml up -d --build
```

### 4. Generar llave de aplicación y usuario inicial (`0356`)
```bash
docker compose -f compose.prod.yaml exec app php artisan key:generate --force
docker compose -f compose.prod.yaml exec app php artisan config:cache
docker compose -f compose.prod.yaml exec app php artisan db:seed --class=InitialAccessSeeder --force
```
El comando mostrará en consola la contraseña temporal del usuario inicial `0356` (área **Sistemas**).

---

## Desarrollo local (Laravel Sail)

```bash
docker compose -f compose.dev.yaml up -d
docker compose -f compose.dev.yaml exec web php artisan migrate
docker compose -f compose.dev.yaml exec web npm run dev
```

---

## Actualización del servidor cuando hay cambios en GitHub

```bash
git pull
docker compose -f compose.prod.yaml up -d --build
```
