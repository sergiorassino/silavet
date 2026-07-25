# Módulo: Resumen cliente entre fechas

## Propósito

PDF de movimientos (protocolos y pagos) de un cliente veterinario entre dos
fechas inclusive, con saldo acumulado por fila y saldo actual de la cuenta en
el título. Réplica del informe NeoLab «Resumen cliente entre fechas».

## Modalidades / variantes

- Solo Menú de Laboratorio (staff), permiso Facturación (`permiso:6`).
- PDF en **A4 horizontal** (landscape): requisito del listado legacy / NeoLab.
- Encabezado: si el lab tiene `entorno.headerInforme` (membrete), se usa esa
  imagen; si no, logo + nombre vía `TcpdfHeaderInstitucional`.

## Actores y permisos

| Actor | Acceso |
|-------|--------|
| Laboratorio con `FACTURACION` (6) | Formulario + descarga PDF |
| Cliente / sin permiso | Sin entrada de menú ni ruta usable |

## Tablas y campos críticos

- `pacientes`: `idClientes`, `fechhoy`, `nombre`, `nombreProtocolo`,
  `propietario`, `estado`, `precio`, `descuento`, `pagado`, `tipoRegistro`
- `especies` / `razas`: nombres vía relaciones
- `clientes`: nombre; se excluyen `tipoCliente = 1` del selector (misma regla
  que cuenta corriente)

## Flujo principal

1. Menú **Clientes** → **Resumen Cliente Entre Fechas** (debajo de Cuenta Corriente).
2. Elegir cliente + desde/hasta → **Aceptar**.
3. Se abre el PDF en pestaña nueva (`vl-abrir-url`).
4. Título: `Resumen: {cliente} entre el {desde} y el {hasta} -- SALDO ACTUAL: $ …`
   (saldo = `CuentaCorrienteConsulta::saldoClienteHoy`, todo el historial).
5. Filas = `CuentaCorrienteConsulta::protocolosCliente` en el período (incluye
   pagos globales; saldo por fila = mapa de saldo acumulado).

## Fuente de verdad

Misma lógica de movimientos y saldo que **Cuenta corriente**
(`CuentaCorrienteConsulta`), no el `pacientes.saldo` crudo del legacy.

## Archivos clave

- `app/Livewire/Clientes/ResumenClienteEntreFechas.php`
- `resources/views/livewire/clientes/resumen-cliente-entre-fechas.blade.php`
- `app/Http/Controllers/Clientes/ResumenClienteEntreFechasPdfController.php`
- `app/Support/CuentaCorriente/ResumenClienteEntreFechasTcpdf.php`
- Rutas: `clientes.resumen-entre-fechas.index` / `.pdf`

## Qué no hacer / reglas de negocio

- No regenerar saldo con `SUM(precio) - SUM(pagado)` del ScriptCase; usar
  `saldoClienteHoy` / `mapaSaldoAcumuladoPorProtocolo`.
- No usar DomPDF ni vistas Blade para el PDF.
- No poner el módulo fuera del grupo Clientes ni cambiar el permiso sin
  actualizar este doc.
- No orientar el PDF en vertical sin pedido explícito (el legacy es landscape).

## Checklist al modificar

- [ ] Columnas del PDF alineadas con NeoLab (Nombre…Saldo, incl. Descuento)
- [ ] Filtro de fechas inclusive sobre `fechhoy`
- [ ] Pagos globales: celdas de paciente vacías; importe en Pagado
- [ ] Menú + icono `resumen-entre-fechas` en catálogo
- [ ] Rate-limit / throttle en descarga PDF
