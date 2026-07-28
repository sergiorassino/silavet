# Módulo: Valores de referencia (rangovalores)

En UI: **Valores de Referencia** en el grupo Parámetros Determinaciones (Menú de Administración).  
Código: `app/Livewire/Abm/Rangovalores/RangovalorIndex.php`.  
Ruta: `admin/valores-referencia` · `permiso:8` (PARAMETROS).

## Propósito

Gestionar los rangos de referencia (mínimo / máximo) por ítem de informe, especie y
sexo almacenados en `rangovalores`. Son los valores que usa la automatización de
Serie Roja / Serie Blanca (ver `docs/modulos/carga-resultados.md`) para clasificar
un resultado como bajo, normal o alto.

## Flujo de carga

El formulario respeta el orden:

1. **Ítem del informe** (`idItems` → `itemsinforme`) — determinación que se quiere referenciar.
2. **Especie** — fijada por el panel izquierdo (se pre-rellena en el form).
3. **Sexos** — multi-check (Macho / Macho Castrado / Hembra / Hembra Castrada).
   "Todos / Ninguno" para selección en bloque.
4. **Mín. / Máx.** — `decimal(10,2)`; máx ≥ mín.

Al guardar el form:

- Por cada sexo marcado → upsert `(idItems, idEspecies, idSexos)` con esos min/max.
- Por cada sexo desmarcado que ya tenía fila para esa combinación → se elimina.

Si el ítem+especie ya tiene filas, los sexos se pre-marcan y los valores se precarga
del primer registro, para facilitar la reedición.

## Modalidades / variantes

No hay variantes por tenant. El módulo está disponible para cualquier lab con
permiso 8, independientemente del flag `hemograma_auto`.

## Operaciones en bloque (grilla por especie)

| Acción | Descripción |
|--------|-------------|
| Guardar todos | Persiste todos los cambios en los inputs de min/max de la grilla |
| Borrar todos | Elimina todos los rangos de la especie activa (confirmación SweetAlert2) |
| Guardar fila | Persiste el min/max de una sola fila |
| Descartar fila | Recarga los valores desde BD |
| Eliminar fila | Borra un solo registro (confirmación SweetAlert2) |

## Tablas y campos críticos

| Tabla | Rol |
|-------|-----|
| `rangovalores` | Fuente de verdad |
| `itemsinforme` | Catálogo de ítems (`idItems`, `nombreItem`) |
| `especies` | Catálogo de especies |
| `sexos` | Solo si existe en BD; si no, usa mapa fijo en `SexoCatalog` (1–4) |

Columnas de `rangovalores`: `idRangovalores` (PK), `idItems`, `idEspecies`, `idSexos`,
`valorMin`, `valorMax`. Unicidad de negocio: `(idItems, idEspecies, idSexos)` —
validada en app, no hay UNIQUE en BD.

## Actores y permisos

| Actor | Permiso | Acceso |
|-------|---------|--------|
| Staff con PARAMETROS | `PermisosIaCatalog::PARAMETROS` (8) | Lectura y escritura completa |
| Resto | — | Sin acceso (403) |

## Archivos clave

| Pieza | Ruta |
|-------|------|
| Livewire | `app/Livewire/Abm/Rangovalores/RangovalorIndex.php` |
| Vista | `resources/views/livewire/abm/rangovalores/rangovalor-index.blade.php` |
| Modelo | `app/Models/Rangovalor.php` |
| Catálogo sexos | `app/Support/SexoCatalog.php` (`opcionesConId`, `nombrePorId`) |
| CSS | clases `vl-rango-*` en `resources/css/app.css` |
| Ruta | `routes/web.php` → `admin/valores-referencia` |
| Menú | `resources/views/layouts/partials/sidebar-grupos-menu.blade.php` |
| Icono | `valores-referencia` en `resources/views/components/vl-sidebar-icon.blade.php` |
| Consumidor | `app/Support/Resultados/HemogramaAutoPayload.php` (solo lectura) |

## Qué no hacer / reglas de negocio

- No modificar `rangovalores` desde otros módulos: este ABM es la única UI de escritura.
- No duplicar la lectura de rangos en nuevos módulos: usar `HemogramaAutoPayload` como
  intermediario si se necesita el dato en otra pantalla.
- No agregar `if (tenant === …)` en Blade: el flag `hemograma_auto` lo controla en carga.
- No crear UNIQUE en BD sin migración explícita revisada por un humano.

## Checklist al modificar

1. ¿El upsert sigue validando uniqueness por app antes de escribir?
2. Si se agregan nuevos sexos: actualizar `SexoCatalog::opcionesConId` y `idSexos`.
3. Tras cambiar CSS o JS: `npm run build` + incluir `public/build/` en el despliegue.
4. Actualizar este documento si cambia el flujo de carga o las reglas de borrado.
