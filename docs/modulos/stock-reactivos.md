# Módulo: Stock de Reactivos

## Propósito

Gestionar el inventario de reactivos e insumos del laboratorio y descuentar/reponer automáticamente
el stock al alta o baja de determinaciones en un protocolo.

## Módulos involucrados

| Módulo | Descripción |
|--------|-------------|
| **Reactivos e Insumos** (ABM) | CRUD sobre `reactivos`: nombre, cantidad, mínimo para aviso, existencia ideal |
| **Reactivos por Determinación** | Maestro-detalle `tipodeterminaciones` → `reactivoxdeterminacion`: cuántos de cada reactivo consume cada tipo |
| Enganche en **Carga de Determinaciones** | Al alta de una determinación se descuenta stock; al baja se repone |

## Actores y permisos

| Actor | Permiso | Acceso |
|-------|---------|--------|
| Staff (Menú de Laboratorio) | `PermisosIaCatalog::REACTIVOS` (**7**) | ABM reactivos + maestro-detalle; `abort_unless` en `mount()` y acciones |

Rutas bajo middleware `menu.portal:laboratorio` + `permiso:7`.

## Tablas y campos críticos

| Tabla | Campos |
|-------|--------|
| `reactivos` | `id`, `reactivo` (varchar 50), `cantidad` (int), `minAviso` (int), `existIdeal` (int) |
| `reactivoxdeterminacion` | `id`, `idTipodeterminaciones`, `idReactivos`, `cantidad` |

Las tablas son legacy y ya existen; no hay migraciones.

## Flujo principal

### ABM Reactivos

1. Listado paginado (50/página) con resaltado en rojo de filas donde `cantidad <= minAviso`.
2. Alta/edición: campos nombre, cantidad, mínimo para aviso, existencia ideal.
3. Eliminar bloqueado si el reactivo está referenciado en `reactivoxdeterminacion`.

### Reactivos por Determinación

1. Panel izquierdo: listado/búsqueda de `tipodeterminaciones`; muestra cantidad de reactivos configurados.
2. Panel derecho: grilla de reactivos + cantidad para la determinación seleccionada.
3. Botón Nuevo → modal con select de reactivos disponibles (excluye ya asociados) + cantidad.
4. Edición inline de cantidad; eliminación con confirmación.
5. No permite duplicar el mismo `idReactivos` en el mismo tipo (validación aplicación; sin UNIQUE en BD).

### Descuento/reposición automática en protocolos

| Evento | Acción |
|--------|--------|
| Alta determinación (`confirmarNueva`) | `StockReactivosService::descontarPorTipo($idTipo)` tras crear la determinación |
| Baja determinación (`eliminar`) | `StockReactivosService::reponerPorTipo($idTipo)` tras borrar |
| Sin filas en `reactivoxdeterminacion` | No-op; no afecta el flujo |

El stock puede quedar negativo (no bloquea). Si tras el descuento algún reactivo queda
`<= minAviso`, se lanza `vl-swal-error` con título "Stock bajo" listando los reactivos afectados.

## Fuente de verdad

| Dato | Quién escribe |
|------|---------------|
| Stock `reactivos.cantidad` | ABM manual + `StockReactivosService` (descuento/reposición) |
| Consumo por tipo | ABM `reactivoxdeterminacion` |

## Archivos clave

| Pieza | Ruta |
|-------|------|
| Modelos | `app/Models/Reactivo.php`, `app/Models/Reactivoxdeterminacion.php` |
| Servicio | `app/Support/Stock/StockReactivosService.php` |
| ABM Reactivos | `app/Livewire/Abm/Reactivos/ReactivoIndex.php`, `ReactivoForm.php` |
| Vistas ABM | `resources/views/livewire/abm/reactivos/` |
| Maestro-detalle | `app/Livewire/Abm/ReactivosPorDeterminacion/ReactivosPorDeterminacionIndex.php` |
| Vista maestro-detalle | `resources/views/livewire/abm/reactivos-por-determinacion/` |
| Rutas | `routes/web.php` → `abm.reactivos.*`, `abm.reactivos-por-determinacion.index` |
| Menú | `resources/views/layouts/partials/sidebar-grupos-menu.blade.php` |
| Enganche stock | `app/Livewire/Protocolos/PacienteDeterminaciones.php` (`confirmarNueva`, `eliminar`) |

## Qué no hacer / reglas de negocio

1. **No bloquear** el alta de determinaciones por stock insuficiente; solo avisar.
2. El campo `minAviso` es solo para mostrar alertas visuales; no es límite operativo.
3. No usar el ABM de `cantidad` para registrar ingresos de compra (fuera de alcance; es solo corrección directa).
4. No cambiar la tabla legacy ni agregar UNIQUE en BD.
5. Respetar guards `Schema::hasTable` / `hasColumn` en el servicio para entornos sin la tabla.

## Checklist al modificar

- [ ] ¿El descuento ocurre tras `Determinacion::create` (no antes)?
- [ ] ¿La reposición ocurre tras `$registro->delete()` (no antes)?
- [ ] ¿El aviso "Stock bajo" lista nombres + cantidades?
- [ ] ¿Eliminar reactivo del ABM verifica que no esté en `reactivoxdeterminacion`?
- [ ] ¿La unicidad reactivo×tipo se valida en la aplicación antes del INSERT?
