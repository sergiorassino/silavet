# SILAVET — Sistema de Gestión de Laboratorios Veterinarios

Sistema de información para laboratorios veterinarios: recepción de muestras,
carga de resultados, informes, facturación (AFIP) y portal de clientes
(veterinarias / clínicas).

## Stack

- **Backend:** PHP 8.2+ · Laravel 11 · Livewire 4
- **Frontend:** Blade · Tailwind CSS 4 · Vite 5
- **Base de datos:** MySQL (legacy, existente)
- **Servidor local:** WAMP 64-bit

## Estructura del proyecto

```
SILAVET/
├── estructura_bd.sql      # Esquema completo de la BD (equivalente a schema.sql en SE)
└── sistema/               # Proyecto Laravel 11
    ├── app/               # Lógica de aplicación
    ├── docs/              # Documentación del proyecto
    ├── database/          # Migraciones aditivas y scripts SQL
    ├── resources/         # Vistas Blade + assets
    └── routes/            # Definición de rutas
```

## Documentación

| #  | Archivo                              | Contenido                                  |
|----|--------------------------------------|--------------------------------------------|
| 01 | `01-descripcion-general.md`          | Visión general, stack, estructura          |
| 02 | `02-modelo-de-datos.md`              | Tablas del núcleo, relaciones, `entorno`   |
| 03 | `03-autenticacion-y-permisos.md`     | Logins, passwords, roles, permisos         |
| 04 | `04-identidad-visual.md`             | Paleta de colores, logos, design system    |
| 05 | `05-preferencias-y-convenciones.md`  | Convenciones de código, preferencias       |
| 06 | `06-reglas-de-seguridad.md`          | Baseline de seguridad obligatorio          |
| 07 | `07-versionado-de-modulos-por-tenant.md` | Personalización por laboratorio        |
| 08 | `08-menus-de-navegacion.md`          | Terminología de portales y sidebars        |
| 09 | `09-despliegue-sin-public-en-url.md` | Apache, subcarpeta, Livewire en producción |

## Flujo de ramas Git

| Rama | Uso |
|------|-----|
| **`desarrollo`** | Desarrollo diario (rama activa por defecto) |
| **`main`** | Resguardo estable en remoto |

## Asistentes de código (Cursor, Copilot, etc.)

Políticas versionadas en el repo: ver **`AGENTS.md`** en esta carpeta.

## Setup local

Desde la **raíz del repo** (`D:\SILAVET`) o desde `sistema/`:

```bash
# Primera vez (dependencias PHP + Node)
cd sistema
composer install
npm install

# Copiar entorno y clave (en sistema/)
cp .env.example .env
php artisan key:generate
# Configurar DB_* en .env → MySQL lb_neolab (WAMP)

# Arrancar Laravel (8001) + Vite (5174) juntos
npm run dev:all
```

También desde Cursor: **Terminal → Run Task → Dev: Laravel + Vite (SILAVET)**  
(o `Ctrl+Shift+B` si la tarea de build está por defecto).

URLs locales (conviven con Sistemas Escolares en 8000 / 5173):

- Laravel: http://127.0.0.1:8001
- Vite: http://127.0.0.1:5174

Si Vite dice *Port 5174 is already in use*, ejecutá de nuevo `npm run dev:all`: el script `predev:all` libera automáticamente los puertos 8001 y 5174 antes de arrancar.

## Migraciones en BD legacy

Tras configurar el tenant y la BD en `.env`:

```bash
# Cambiar de laboratorio (opcional)
php artisan lb:switch neolab

# Vista previa (no escribe en BD)
php artisan lb:migrate-legacy --dry-run

# Aplicar migraciones aditivas (precio2/precio3, permisos_ia, etc.)
php artisan lb:migrate-legacy --force
```

Equivalente a `php artisan se:migrate-legacy --force` en Sistemas Escolares.

## Referencia de arquitectura

Este proyecto replica las convenciones de **Sistemas Escolares**
(`D:\SCRIPTCASE_DEPLOY\ia\sistema`): contexto de sesión, permisos, tenants,
migraciones aditivas, design system y políticas de seguridad documentadas.
