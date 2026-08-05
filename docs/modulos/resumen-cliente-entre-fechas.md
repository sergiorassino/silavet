# Módulo: Resumen cliente entre fechas

## Propósito

PDF de movimientos de un cliente entre dos fechas inclusive, con saldo actual
en el título. El formulario es único; el PDF cambia según la variante de tesorería.

## Variantes

| Variante tesorería | Fuente de datos | PDF controller | Columnas PDF |
|--------------------|-----------------|----------------|--------------|
| `tesoreria_movimientos` | `pacientes` (`protocolosCliente`) | `ResumenClienteEntreFechasPdfController` | Nombre, Protocolo, Especie, Raza, Fecha, Propietario, Estado, Precio, Descuento, Pagado, Saldo |
| `tesoreria_pacientes` | `movimientos` (`movimientosCliente`) | `ResumenClienteEntreFechasMovimientosPdfController` | Nombre, Cuenta, Concepto, Fecha/Hora, Monto, Obs |

Ambas variantes usan **A4 horizontal** (landscape) y `TcpdfHeaderInstitucional`.
El switch de controller ocurre en `routes/web.php` con `TesoreriaConfig::usaPacientes()`.

## Modalidades / variantes

- Solo Menú de Laboratorio (staff), permiso Facturación (`permiso:6`).
- Encabezado: si el lab tiene `entorno.headerInforme` (membrete), se usa esa
  imagen; si no, logo + nombre vía `TcpdfHeaderInstitucional`.

## Actores y permisos

| Actor | Acceso |
|-------|--------|
| Laboratorio con `FACTURACION` (6) | Formulario + descarga PDF |
| Cliente / sin permiso | Sin entrada de menú ni ruta usable |

## Tablas y campos críticos

**Variante `tesoreria_movimientos`:**
- `pacientes`: `idClientes`, `fechhoy`, `nombre`, `nombreProtocolo`,
  `propietario`, `estado`, `precio`, `descuento`, `pagado`, `tipoRegistro`
- `especies` / `razas`: nombres vía relaciones
- `clientes`: nombre; se excluyen `tipoCliente = 1` del selector

**Variante `tesoreria_pacientes`:**
- `movimientos`: `idClientes`, `fechhora`, `idCuentas`, `idConcepto`, `monto`, `obs`, `idPacientes`
- `mediodepago`, `conceptos`: labels de cuenta y concepto
- `clientes`: nombre; misma exclusión `tipoCliente = 1`

## Flujo principal

1. Menú **Clientes** → **Resumen Cliente Entre Fechas**.
2. Elegir cliente + desde/hasta → **Aceptar**.
3. Se abre el PDF en pestaña nueva (`vl-abrir-url`).
4. Título: `Resumen: {cliente} entre el {desde} y el {hasta} -- SALDO ACTUAL: $ …`
   (saldo = consulta de la variante activa, todo el historial).

## Fuente de verdad

- `tesoreria_movimientos`: `CuentaCorrienteConsulta::protocolosCliente` / `saldoClienteHoy`.
- `tesoreria_pacientes`: `CuentaCorrienteMovimientosConsulta::movimientosCliente` / `saldoClienteHoy`.
- No usar `pacientes.saldo` crudo del legacy ni `tmpSaldo`.

## Archivos clave

- `app/Livewire/Clientes/ResumenClienteEntreFechas.php`
- `resources/views/livewire/clientes/resumen-cliente-entre-fechas.blade.php`
- `app/Http/Controllers/Clientes/ResumenClienteEntreFechasPdfController.php` (movimientos NeoLab)
- `app/Http/Controllers/Clientes/ResumenClienteEntreFechasMovimientosPdfController.php` (tabla movimientos)
- `app/Support/CuentaCorriente/ResumenClienteEntreFechasTcpdf.php`
- `app/Support/CuentaCorriente/ResumenClienteEntreFechasMovimientosTcpdf.php`
- Rutas: `clientes.resumen-entre-fechas.index` / `.pdf`

## Qué no hacer / reglas de negocio

- No regenerar saldo con `SUM(precio) - SUM(pagado)` raw; usar la consulta de la variante activa.
- No usar DomPDF ni vistas Blade para el PDF.
- No hardcodear `if (slug === 'labvetciudad')` — solo `TesoreriaConfig`.
- No orientar el PDF en vertical sin pedido explícito (el legacy es landscape).
- No poner el módulo fuera del grupo Clientes ni cambiar el permiso sin actualizar este doc.

## Checklist al modificar

- [ ] ¿Se verifica qué variante es activa antes de tocar la consulta o PDF?
- [ ] Filtro de fechas inclusive sobre `fechhoy` (movimientos) / `fechhora` (movimientos tabla)
- [ ] Rate-limit / throttle en descarga PDF
- [ ] Si cambia el comportamiento documentado, actualizar este archivo

