# Módulo: Detalle de determinaciones (autogestión)

En UI: columna **DETERM.** (lupa) del listado de pacientes del **Menú de Clientes**.

## Propósito

Permitir al veterinario ver, de cada protocolo propio, las determinaciones
pedidas con precio de lista, descuento y totales (neto, descuentos, neto con
descuentos). Solo lectura. Incluye PDF.

No carga ni edita determinaciones (eso es staff:
[`carga-determinaciones-paciente.md`](carga-determinaciones-paciente.md)).

## Modalidades / variantes

Ninguna. Igual en todos los tenants. Respeta datos legacy (`precio` = lista
cuando `neto` está vacío).

## Actores y permisos

| Actor | Acceso |
|-------|--------|
| Usuario cliente (`menu.portal:cliente` + `idClientes`) | Listado + detalle + PDF de **sus** protocolos |
| Staff | Sin esta pantalla (middleware de portal cliente) |

Alcance: `labCtx()->idClientes`. Pagos globales / egresos: 404.

## Tablas y campos críticos

| Tabla | Rol |
|-------|-----|
| `determinaciones` | Filas pedidas; `neto` = lista, `descuento` en pesos, `precio` = neto − descuento |
| `tipodeterminaciones` | Nombre mostrado |
| `pacientes` | Cabecera (nombre, protocolo, cliente) |

## Flujo principal

1. Menú de Clientes → Pacientes → lupa **DETERM.**
2. Pantalla: veterinaria, paciente, protocolo; búsqueda rápida; tabla
   Determinaciones Solicitadas / Precio (neto) / Descuento; Total Acumulado;
   Total con Descuentos.
3. **PDF** abre el mismo detalle (A4 vertical, TCPDF). **Volver** al listado
   conservando filtros.

URLs con `OpaqueRouteToken` (sin `idPacientes` ni n.º de protocolo).

## Fuente de verdad

Filas de `determinaciones` del protocolo. Totales = suma de esas filas
(filtradas si hay búsqueda en pantalla; el PDF lista **todas**).

## Archivos clave

- `app/Livewire/Cliente/PacienteDeterminacionesDetalle.php`
- `resources/views/livewire/cliente/paciente-determinaciones-detalle.blade.php`
- `resources/views/livewire/protocolos/paciente-index-autogestion.blade.php`
- `app/Support/Cliente/DetalleDeterminacionesConsulta.php`
- `app/Support/Cliente/DetalleDeterminacionesTcpdf.php`
- `app/Http/Controllers/Cliente/DetalleDeterminacionesPdfController.php`
- Rutas: `cliente.pacientes.determinaciones` / `.pdf`

## Qué no hacer / reglas de negocio

- No reutilizar `PacienteDeterminaciones` (carga staff) en el portal.
- No poner IDs de protocolo en la URL.
- No mostrar derivación, fechas ni permitir editar importes.
- Precio de columna = **neto** (lista); Total con Descuentos = suma de `precio`.

## Checklist al modificar

- [ ] Alcance por `idClientes`; pagos globales sin lupa / 404
- [ ] Token opaco + revalidación de usuario
- [ ] Legacy `neto` vacío interpretado como lista en `precio`
- [ ] PDF TCPDF A4 vertical, fuente Arial, rate-limit
- [ ] Si cambia el comportamiento, actualizar este archivo
