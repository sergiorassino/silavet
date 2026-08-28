# Módulo: Clientes resumen mensual

## Propósito

Listado de protocolos (`pacientes`) entre dos fechas, agrupado por cliente
veterinario, con desglose de IVA 21 %, importe pagado, medio de pago y las
determinaciones de cada protocolo. Exporta a PDF (A4 horizontal) y Excel.
Replica el blank ScriptCase `pacientesIVA` / `pacientesIVApdf` /
`pacientesIVAexcel`.

## Modalidades / variantes

Ninguna. Igual en todos los tenants.

## Actores y permisos

| Actor | Acceso |
|-------|--------|
| Staff con `LISTADOS_ESTADISTICOS` (10) | Pantalla + PDF + Excel |
| Usuario cliente | Sin entrada de menú (`menu.portal:staff`); si llegara a la ruta, se fuerza `idClientes` de sesión |

## Tablas y campos críticos

| Tabla | Rol |
|-------|-----|
| `pacientes` | Una fila por protocolo. Fecha `fechhoy`. **Precio con IVA** = `neto` (nunca negativo). **Pagado** = `pagado`. Recuadro de descuento = `precio`. Se excluye `tipoRegistro = 2` (ingresos de tesorería). |
| `clientes` | Nombre del grupo; `descuento` solo para el recuadro verde si hay un cliente filtrado |
| `mediodepago` | `nombreMedioPago`; si falta, «Sin medio» |
| `determinaciones` + `tipodeterminaciones` | Mini-tabla: solo `td.nombre`, orden `td.orden`, `td.nombre` |

## Flujo principal

1. Menú de Laboratorio → **Listados Estadísticos** → **Clientes Resumen Mensual** (última opción).
2. Filtros: fecha desde / hasta (default: 1° del mes → hoy) y cliente (todos o uno). Si desde > hasta, se intercambian.
3. Grid agrupada por nombre de cliente, con subtotal por cliente **solo si hay más de un grupo**, y total general.
4. **Exportar PDF** / **Exportar Excel** descargan **todo** el filtro (no solo la página).

## Fuente de verdad

- IVA: `con_iva = max(neto, 0)`; `sin_iva = round(con_iva / 1.21, 2)`; `iva = round(con_iva − sin_iva, 2)`. Los totales suman esos redondeos **por fila** (no el desglose del total).
- Recuadro verde (`N% descuento`): solo con **un** cliente filtrado y `clientes.descuento > 0`. Usa el mismo desglose sobre `pacientes.precio` (importe con descuento).
- Fecha: `pacientes.fechhoy` inclusive (`whereDate`).
- No usar `renglones` ni recalcular IVA desde las líneas de determinación.

## Archivos clave

- `app/Livewire/Listados/ClientesResumenMensual.php`
- `resources/views/livewire/listados/clientes-resumen-mensual.blade.php`
- `app/Support/Listados/ClientesResumenMensualConsulta.php`
- `app/Support/Listados/ClientesResumenMensualTcpdf.php`
- `app/Support/Listados/ClientesResumenMensualExporter.php`
- `app/Http/Controllers/Listados/ClientesResumenMensualPdfController.php`
- `app/Http/Controllers/Listados/ClientesResumenMensualExcelController.php`
- Rutas: `listados.clientes-resumen-mensual` / `.pdf` / `.excel`

## Qué no hacer / reglas de negocio

- No filtrar `tipoRegistro = 1`: el blank usa `tipoRegistro <> 2` (incluye 0 y 3; en SQL los `NULL` no entran).
- No agrupar por `idClientes` si el nombre coincide: el blank agrupa por `clientes.nombre`.
- No poner subtotal cuando hay un solo cliente en el conjunto mostrado.
- PDF: A4 **horizontal** (documentado en la clase TCPDF). Encabezado institucional
  (`headerInforme`) sin deformar: cabe en 35 mm de alto y se centra; no estirar
  a todo el ancho apaisado. No logo hardcodeado de un tenant.
- No usar DomPDF. Excel con PhpSpreadsheet (no PHPExcel).
- No poner el módulo fuera del grupo Listados Estadísticos ni cambiar el permiso 10 sin actualizar este doc.

## Checklist al modificar

- [ ] Fechas inclusive; default 1° del mes / hoy; intercambio si desde > hasta
- [ ] IVA 21 % por fila sobre `neto`; recuadro de descuento sobre `precio`
- [ ] `tipoRegistro <> 2`; alcance `labCtx()` si el usuario es cliente
- [ ] Rate-limit en PDF/Excel; exporta el filtro completo
- [ ] Icono de menú único (`clientes-resumen-mensual`)
- [ ] Si cambia el comportamiento documentado, actualizar este archivo
