# Instrucciones para asistentes de código (Cursor, Copilot, etc.)

Este archivo está **en el repositorio**: aplica a **todas** las personas y
herramientas que trabajen sobre este código.

## Base de datos (obligatorio)

**No ejecutar** desde la herramienta de terminal del asistente nada que **escriba**
en la base de datos, **aunque el usuario lo pida**. Incluye, entre otros:

- `php artisan tinker` … con `delete` / `update` / `insert`
- `php artisan migrate*`, `db:*`, `db:seed`, imports, cliente `mysql`
- Scripts PHP one-shot (`php -r`, etc.) que usen Eloquent o `DB::`

**Sí hacer:** entregar **solo SQL** (o el comando Artisan) **en el chat como texto**
para que un humano lo revise y ejecute en su cliente; y guardar migraciones/código
en archivos **sin** invocarlos para aplicar el cambio en la BD.

**Cierre de tareas:** si el cambio implica **esquema o datos**, al **final** de la
respuesta o del PR debe figurar un bloque listo para copiar con:

1. Las sentencias **SQL** equivalentes al `up()` de la migración, en orden
   correcto respecto de FKs si aplica; y
2. Una **advertencia breve** de alcance (tablas afectadas, irreversibilidad).

Detalle: `docs/06-reglas-de-seguridad.md` sección **9**.

## Despliegue a producción (obligatorio al cerrar tareas)

Si el cambio implica **código o vistas**, al **final** debe figurar un bloque
**Archivos para producción** con:

1. **Lista de rutas** relativas a la raíz del repo (una por línea).
2. **Assets compilados**, si aplica: `npm run build` o subir `public/build/`.
3. **Comandos post-despliegue** opcionales (`php artisan view:clear`, etc.),
   **sin ejecutarlos** desde la herramienta del asistente.
4. Si **no** hubo cambios desplegables, decirlo explícitamente.

Referencia: `docs/09-despliegue-sin-public-en-url.md`.

## PDFs

- **Nuevos:** **TCPDF** (`tecnickcom/tcpdf`), clase en `app/Support/`, controlador
  `*PdfController`. Fuente **Arial** vía `App\Support\Pdf\TcpdfFuenteArial`
  (`storage/fonts/arial.ttf`). Regla: `.cursor/rules/pdf-tcpdf-nuevos.mdc`.
- **Papel por defecto:** **A4 vertical** (`'P', 'mm', 'A4'`). Otra orientación o
  formato solo si se indica explícitamente en la tarea o el requisito funcional.
- **Legacy (DomPDF):** tablas con columnas de distinto ancho: **porcentaje inline**
  en cada `th` y `td`. Regla: `.cursor/rules/pdf-dompdf-columnas.mdc`.

## Informes de laboratorio

Los informes PDF/HTML se generan a partir de `renglones`, `itemsinforme` y
`entorno`. Respetar la configuración de pie de página, firmas y colores en
`entorno`. No recalcular valores de referencia fuera del módulo de carga
autorizado.

## Menús de navegación (terminología)

Usar siempre: **Menú de Laboratorio** (`layouts/laboratorio`), **Menú de
Administración** (`layouts/administracion`), **Menú de Clientes**
(`layouts/cliente`). Detalle: `docs/08-menus-de-navegacion.md`.

**Iconos del menú:** no repetir iconos entre grupos y opciones. Usar
`<x-vl-sidebar-icon name="…" />` y registrar cada nombre nuevo en el catálogo del
componente (ver `docs/08-menus-de-navegacion.md` §6).

## Diálogos al usuario (SweetAlert2)

Confirmaciones, éxitos, avisos y errores: helpers `vlSwal*` en
`resources/js/app.js`; eventos Livewire `vl-swal-exito` / `vl-swal-error`.
No usar `wire:confirm` ni `alert`/`confirm` del navegador.

## Paginación en listados Livewire

Listados paginados: `WithPagination`, `POR_PAGINA = 50`, `resetPage()` al cambiar
filtros, y en Blade `@if ($registros->hasPages())` +
`$registros->links('vendor.pagination.vl-compact')`.

## URLs sin IDs reveladores

En portal de clientes, PDFs y descargas por GET: **no** poner IDs de BD, CUIT ni
número de protocolo en la URL. Usar `App\Support\Security\OpaqueRouteToken`.
Detalle: `docs/06-reglas-de-seguridad.md` §10.

## Resto del baseline

Seguridad, permisos, `labCtx()`, Blade, etc.: `docs/06-reglas-de-seguridad.md`
y las reglas en `.cursor/rules/`.
