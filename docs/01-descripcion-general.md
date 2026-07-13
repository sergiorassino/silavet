# Descripción General del Sistema

## 1. Resumen

Sistema de información para la gestión de laboratorios veterinarios: recepción
de protocolos (casos), determinaciones analíticas, carga de resultados, emisión
de informes, facturación AFIP y portal de consulta para clientes (veterinarias
y clínicas).

Existe una versión en producción con base de datos MySQL (NeoLab / ScriptCase)
que se mantiene. Se construye una versión nueva sobre esa misma base de datos.

El desarrollo es por etapas. La **Etapa 0** (directorio de trabajo) está en curso.
La **Etapa 1 (núcleo)** comenzará con login, contexto de sesión y ABMs básicos.

---

## 2. Stack Técnico

| Capa        | Tecnología                                                  |
|-------------|-------------------------------------------------------------|
| Backend     | PHP 8.2+ · Laravel 11 · Livewire 4                         |
| Frontend    | Blade + Tailwind CSS 4 · Vite 5                             |
| Base de datos | MySQL existente (legacy) — NO modificar tablas existentes |
| Servidor local | WAMP 64-bit                                              |
| Auth provider | Custom (`UsuarioUserProvider`) sobre tabla `usuarios`     |

### Reglas de base de datos

- Crear **migraciones para instalación limpia** y **migraciones aditivas** a MySQL existente.
- **NO modificar tablas existentes** de la base legacy.
- Usar Eloquent con modelos que apunten a las tablas existentes (sin convenciones de timestamps/pluralización).

---

## 3. Interfaz de usuario

Tres **menús de navegación** (sidebar). Nomenclatura oficial:
[08-menus-de-navegacion.md](08-menus-de-navegacion.md).

| Menú | Layout | Orientación típica |
|------|--------|-------------------|
| **Laboratorio** (operaciones analíticas) | `layouts/laboratorio.blade.php` | 80% desktop |
| **Administración** (facturación, stock, parámetros) | `layouts/administracion.blade.php` | 80% desktop |
| **Clientes** (veterinarias / clínicas) | `layouts/cliente.blade.php` | 90% mobile |

- Sidebar responsivo en los tres portales.
- Design system basado en paleta institucional (ver [04-identidad-visual.md](04-identidad-visual.md)).

---

## 4. Archivos de referencia en la raíz del proyecto

| Archivo / Carpeta  | Descripción                                                     |
|--------------------|-----------------------------------------------------------------|
| `schema_lb_neolab.sql` | Estructura completa de todas las tablas de la BD legacy        |
| `public/img/`      | Logos y paleta del laboratorio (a definir en Etapa 1)           |

---

## 5. Estructura del proyecto Laravel

```
SILAVET/                         # Laravel en la raíz (igual que sistema_ia)
├── schema_lb_neolab.sql         # Esquema completo de BD (referencia)
├── app/
│   ├── Auth/                    # UsuarioUserProvider (auth custom)
│   ├── Http/Middleware/         # EnsureLabContext, EnsureMenuPortal
│   ├── Livewire/
│   │   ├── Auth/Login.php       # Login de gestión
│   │   └── Abm/                 # Módulos ABM por dominio
│   ├── Models/                  # Eloquent models (legacy tables)
│   └── Support/                 # LabContext, helpers
├── docs/                        # ← Documentación del proyecto
├── resources/views/
│   ├── layouts/                 # laboratorio, administracion, cliente, guest
│   └── livewire/                # Vistas Livewire
├── routes/web.php               # Rutas (guest + auth + lab.context)
└── artisan
```

### Varios laboratorios (tenants)

Mismo código en la raíz del repo, **una BD por laboratorio**, identificador
`TENANT_SLUG` en `.env` y overrides en `config/tenants/{slug}.php`.
Detalle: [07-versionado-de-modulos-por-tenant.md](07-versionado-de-modulos-por-tenant.md).

---

## 6. Etapas de desarrollo

### Etapa 0 — Directorio de trabajo (completada)

- Estructura de carpetas del proyecto Laravel
- Documentación numerada (`docs/01` … `docs/09`)
- Reglas para asistentes de código (`AGENTS.md`, `.cursor/rules/`)
- Esquema de BD de referencia (`schema_lb_neolab.sql`)

### Etapa 1 — Núcleo (en curso)

**Implementado:**

- Laravel 11 + Livewire 4 + Tailwind 4 + Vite
- Login de gestión (`usuarios`, auth híbrida)
- `LabContext` + middleware `EnsureLabContext`
- Menú de Laboratorio y Menú de Administración (layouts + `menu.portal`)
- Dashboard + ABM Clientes (`clientes`)
- Tenant: `config/tenant.php`, `lb:switch`, merge por slug
- Design system `vl-*`, SweetAlert2, paginación compacta

**Pendiente en Etapa 1:**

- ABM Especies / Razas (`especies`, `razas`)
- ABM Tipos de determinación (`tipodeterminaciones`)
- Login y portal de clientes (`/loginCliente`)
- Sincronizar catálogo `permisos_ia` en BD
- Logos en `public/img/`

**Etapas posteriores:**

- Recepción de protocolos (`pacientes`)
- Carga de resultados (`renglones`, `itemsinforme`)
- Informes PDF
- Facturación AFIP (`compafip`, integración afipSE)
- Stock de reactivos (`reactivos`)
- Portal de clientes (consulta de protocolos e informes)
- Notificaciones

---

## 7. Relación con Sistemas Escolares

SILAVET replica la arquitectura probada en
`D:\SCRIPTCASE_DEPLOY\ia\sistema`:

| Concepto SE | Equivalente SILAVET |
|-------------|---------------------|
| `schoolCtx()` | `labCtx()` |
| `ento` | `entorno` |
| `profesores` | `usuarios` |
| `legajos` | `pacientes` (protocolos/casos) |
| `terlec` | Período operativo (a definir en sesión o `entorno`) |
| Prefijo CSS `se-*` | Prefijo CSS `vl-*` |
| `TENANT_SLUG` / `config/tenants/` | Igual |
| Migraciones aditivas | Igual |
| `AGENTS.md` / política BD | Igual |
