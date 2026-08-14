# Módulo: Determinaciones por cliente

## Propósito

Listado estadístico de determinaciones **pedidas** (tabla `determinaciones`), agrupado
por cliente veterinario. Cada grupo muestra cantidad de filas y suma de `precio`.
Al expandir un cliente se ven las filas de detalle. Exporta a Excel con la misma
estructura agrupada del listado legacy de ScriptCase (`determinacionesPorCliente`).

No carga resultados (`renglones`) ni edita protocolos.

## Modalidades / variantes

Ninguna. Igual en todos los tenants.

## Actores y permisos

| Actor | Acceso |
|-------|--------|
| Staff con `LISTADOS_ESTADISTICOS` (10) | Pantalla + Excel |
| Usuario cliente | Sin entrada de menú (middleware `menu.portal:staff`); si llegara a la ruta, se fuerza `idClientes` de sesión |

## Tablas y campos críticos

| Tabla | Rol |
|-------|-----|
| `determinaciones` | Fuente de cada fila; `precio` es el importe cobrado (neto − descuento) |
| `pacientes` | Protocolo: `fechhoy`, `nombreProtocolo`, `nombre` (animal), `idClientes`. Se incluyen `tipoRegistro` 0/NULL (legacy) y 1; se excluyen 2 y 3 (tesorería) |
| `clientes` | Nombre del grupo |
| `tipodeterminaciones` | Nombre de la determinación |

El cliente del grupo es **`pacientes.idClientes`**, no `determinaciones.idClientes`.

## Flujo principal

1. Menú de Laboratorio → **Listados Estadísticos** → **Determinaciones por Cliente** (última opción del grupo).
2. Filtros: cliente, búsqueda rápida (cliente / determinación / protocolo / paciente), desde/hasta **opcionales**. Sin fechas se listan **todos** los registros históricos.
3. Se listan **grupos de cliente** (50 por página) con cantidad y suma. Clic en el encabezado expande el detalle.
4. **Exportar Excel** descarga todos los grupos del filtro (no solo la página), con encabezado de grupo, columnas, subtotal **Suma** y **Total Acumulado**.

## Fuente de verdad

- Filas: `determinaciones` ligadas a protocolos analíticos (`tipoRegistro` distinto de ingreso/egreso). En labvetciudad el histórico suele ser `tipoRegistro = 0`; no exigir `= 1`.
- Precio de cada línea: `determinaciones.precio`.
- Fecha: `pacientes.fechhoy`.
- No usar `renglones` ni `pacientes.precio` (total del protocolo).

## Archivos clave

- `app/Livewire/Listados/DeterminacionesPorCliente.php`
- `resources/views/livewire/listados/determinaciones-por-cliente.blade.php`
- `app/Support/Listados/DeterminacionesPorClienteConsulta.php`
- `app/Support/Listados/DeterminacionesPorClienteExporter.php`
- `app/Http/Controllers/Listados/DeterminacionesPorClienteExcelController.php`
- Rutas: `listados.determinaciones-por-cliente` / `.excel`

## Qué no hacer / reglas de negocio

- No filtrar `pacientes.tipoRegistro = 1`: deja afuera el histórico legacy (`0`). Solo excluir tesorería (2 y 3).
- No agrupar por ítems de informe (`itemsinforme` / `renglones`): este listado es de **tipos pedidos**.
- No paginar las filas de detalle a costa de partir un cliente en dos páginas: la paginación es por **grupos de cliente**.
- No poner el módulo fuera del grupo Listados Estadísticos ni cambiar el permiso 10 sin actualizar este doc.
- Excel: repetir encabezados por grupo (`Nombre`, `Nombre`, `Fecha`, `Nombre Protocolo`, `Nombre`, `Precio`) y cerrar con ` - Suma` / `Total Acumulado(N) - Suma`.

## Checklist al modificar

- [ ] Filtro de fechas inclusive sobre `pacientes.fechhoy`; vacío = todo el historial (no recortar al mes en curso)
- [ ] Protocolos legacy (`tipoRegistro` 0/NULL) incluidos; no exigir `tipoRegistro = 1`
- [ ] Alcance por `labCtx()` si el usuario es cliente
- [ ] Rate-limit / throttle en descarga Excel
- [ ] Icono de menú único (`determinaciones-por-cliente`) en el catálogo
- [ ] Si cambia el comportamiento documentado, actualizar este archivo
