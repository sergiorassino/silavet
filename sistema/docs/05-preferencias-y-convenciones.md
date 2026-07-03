# Preferencias y Convenciones de Desarrollo

> Este archivo concentra las preferencias del proyecto y las convenciones de
> código que deben respetarse en todos los módulos, presentes y futuros.
> Basado en `docs/05-preferencias-y-convenciones.md` de Sistemas Escolares,
> adaptado al dominio de laboratorios veterinarios.

---

## 1. Seguridad (obligatorio)

Aplicar **medidas de seguridad de un sistema profesional** (PHP + MySQL + Laravel)
a todos los módulos. Ver [06-reglas-de-seguridad.md](06-reglas-de-seguridad.md).

### Resumen de medidas mínimas por módulo

- ✅ Autenticación para todo lo interno (`auth` middleware)
- ✅ Autorización / control de alcance por contexto (`labCtx()`)
- ✅ Validación server-side y normalización (`trim`, formatos)
- ✅ Protección XSS (escape en Blade, evitar `{!! !!}`)
- ✅ Evitar SQL injection (sin `raw` con input de usuario)
- ✅ Rate limit en operaciones ABM sensibles

---

## 2. Base de datos

- **NO modificar** tablas existentes de la base legacy.
- Crear migraciones **aditivas** (agregar columnas, tablas nuevas).
- Crear migraciones para **instalación limpia** del sistema nuevo.
- Modelos Eloquent con `$table` explícito, sin timestamps automáticos.
- `$fillable` explícito en todos los modelos — nunca `$guarded = []`.
- Esquema de referencia: `../estructura_bd.sql`.

---

## 3. Estilo de implementación

- Preferir cambios **seguros y conservadores** sin romper compatibilidad legacy.
- Toda acción ABM (crear/editar/eliminar) debe revalidar el alcance del registro.
- Usuarios cliente: **siempre** filtrar por `labCtx()->idClientes`.
- Personal de laboratorio: filtrar por permisos (`tienePermiso`).

---

## 4. Convenciones de código

### PHP / Laravel

- Nombres de clases en PascalCase.
- Componentes Livewire organizados por dominio: `Livewire/Auth/`, `Livewire/Abm/`,
  `Livewire/Protocolos/`, `Livewire/Informes/`.
- Vistas Blade en mirror: `livewire/auth/`, `livewire/abm/`, etc.
- Helper global `labCtx()` para acceder al contexto de sesión.
- Mensajes de validación en español.
- Comentarios en español cuando aclaren lógica de negocio.

### Terminología de dominio

| Evitar (ambiguo) | Usar |
|------------------|------|
| Paciente (humano) | **Protocolo** o **caso** (`pacientes` = registro de protocolo) |
| Cliente (genérico) | **Cliente veterinario** (`clientes`) |
| Determinación | **Tipo de análisis** en UI; **determinación** en código/BD |
| Resultado | **Renglón** / **ítem de informe** según capa |

### Frontend / Blade

- Usar `{{ }}` siempre (escape XSS).
- Tailwind CSS 4 para estilos.
- Clases del design system con prefijo `vl-*` (ver [04-identidad-visual.md](04-identidad-visual.md)).
- Layout responsivo; portal de clientes **mobile-first**.

### Grillas / listados

- Listados paginados: 50 registros por página, paginación `vl-compact`.
- Al cambiar filtros: `resetPage()`.
- Grillas anchas: scroll horizontal, alineación a la izquierda.
- Grillas angostas: clases `vl-grid-pocos-campos` y `vl-grid-angosta-wrap`.

---

## 5. Varios laboratorios (tenants)

- Un despliegue por laboratorio: `TENANT_SLUG` + BD propia.
  Ver [07-versionado-de-modulos-por-tenant.md](07-versionado-de-modulos-por-tenant.md).
- Preferir parametrización en BD (`entorno`, permisos) antes de ramas por tenant.
- Overrides en `config/tenants/{slug}.php` solo para lo que no corresponda en BD.

---

## 6. Menús y módulos

**Nombres oficiales:** [08-menus-de-navegacion.md](08-menus-de-navegacion.md).

- **Menú de Laboratorio**, **Menú de Administración**, **Menú de Clientes**.
- Cada enlace del sidebar lleva atributo **`title`** (tooltip) con nombre del
  módulo y versión si aplica: `(v1.0)`.
- Rutas con prefijo de dominio cuando el alcance lo requiera:
  `protocolos.recepcion`, `informes.emitir`, `facturacion.afip`.

---

## 7. Resultados analíticos

- Los valores de referencia por especie están en `itemsinforme.ref*` y
  `rangovalores`. **No duplicar** lógica de rangos en múltiples módulos.
- La carga de resultados (`renglones.valor`) es responsabilidad del módulo
  autorizado; otros módulos **leen** sin recalcular.
- Fórmulas en `entorno.formulas`: interpretar solo en el servicio designado.

---

## 8. PDFs e informes

### Nuevos (TCPDF)

- Clase en `app/Support/`, controlador `*PdfController`.
- Fuente Arial (`storage/fonts/arial.ttf`).
- Respetar `entorno`: logo, color, firmas, pie de página.
- No usar DomPDF para informes nuevos.

### Legacy (DomPDF)

- Si se mantiene compatibilidad con informes existentes: columnas con ancho
  en porcentaje inline.

---

## 9. Diálogos (SweetAlert2)

- Helpers `vlSwal*` en `resources/js/app.js`.
- Eventos Livewire: `vl-swal-exito`, `vl-swal-error`.
- No usar `wire:confirm`, `alert()` ni `confirm()` del navegador.

---

## 10. Paginación

- `WithPagination`, `POR_PAGINA = 50`.
- Vista: `vendor.pagination.vl-compact`.
- Footer: `vl-matriz-list-footer`.

---

## 11. Modales Livewire

- Usar `@teleport('body')` para modales.
- Overlay neutral, contenedor `rounded-2xl`, acciones consistentes con design system.

---

## 12. Fechas

- Formato de visualización: **`d/m/Y`** (Argentina).
- Entrada en formularios: respetar formato legacy donde exista (`fechnaci` como
  varchar en `pacientes`).

---

## 13. URLs opacas

- Portal clientes, PDFs e informes descargables: tokens opacos, no IDs en URL.
- Ver [06-reglas-de-seguridad.md](06-reglas-de-seguridad.md) §10.

---

## 14. Comandos Artisan de tenant

- `php artisan vl:switch {slug}` — cambia `TENANT_SLUG` y `DB_DATABASE` en `.env`.
- `php artisan vl:migrate-legacy` — aplica migraciones aditivas sobre BD existente.

(Por implementar en Etapa 1.)
