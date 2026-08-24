# Módulo: Cuenta Corriente de Clientes

En UI: ítem **Cuenta Corriente** del grupo Clientes, Menú de Laboratorio (staff).

Existen **dos implementaciones** según la variante de tesorería activa
(`TesoreriaConfig`). El selector de código es `TesoreriaConfig::usaPacientes()`
— **no** existe bifurcación `if (slug === '...')` en el código.

## Variantes

| Clave tesorería | Tabla de CC | Saldo | UI principal |
|-----------------|-------------|-------|--------------|
| `tesoreria_movimientos` (default) | `pacientes` | `precio − pagado` por protocolo | `CuentaCorrienteIndex` / `Detalle` |
| `tesoreria_pacientes` (labvetciudad) | `movimientos` | `SUM(monto) WHERE idCuentas = id_cuenta_cc` | `CuentaCorrienteMovimientosIndex` / `MovimientosDetalle` |

Config `id_cuenta_cc` (solo variante `tesoreria_pacientes`):

```php
// config/tenant.php (default) — mediodepago id que representa la CC
'cuenta_corriente' => [
    'id_cuenta_cc' => 1,
],
```

## Actores y permisos

| Actor | Permiso |
|-------|---------|
| Staff (Menú de Laboratorio) | `FACTURACION` (6) + `menu.portal:laboratorio` |
| Portal cliente / sin permiso | Sin acceso |

## Variante `tesoreria_movimientos` (NeoLab / mayoría)

**Fuente:** `pacientes` (`tipoRegistro` 1 protocolos, 2 pagos globales).

**Saldo por cliente:** `SUM(precio − pagado)` acumulado por protocolo
(excluye `tipoRegistro = 3`).

**Detalle:** protocolos + pagos globales con saldo corrido por fila.

**Archivos clave:**
- `app/Support/CuentaCorriente/CuentaCorrienteConsulta.php`
- `app/Livewire/Clientes/CuentaCorrienteIndex.php`
- `app/Livewire/Clientes/CuentaCorrienteDetalle.php`
- `app/Support/CuentaCorriente/CuentaCorrienteClientesTcpdf.php`
- `app/Support/CuentaCorriente/CuentaCorrienteDetalleTcpdf.php`
- `app/Support/CuentaCorriente/CuentaCorrienteExporter.php`

## Variante `tesoreria_pacientes` (labvetciudad)

**Fuente:** `movimientos` (tabla de caja labvetciudad).

**Saldo por cliente:** `SUM(movimientos.monto) WHERE idClientes = X AND idCuentas = {id_cuenta_cc}`.
- Ingresos positivos, egresos ya vienen negativos. No se reaplica ninguna lógica extra.
- Solo se suman movimientos de la cuenta CC (`id_cuenta_cc`).
- No se escribe `clientes.tmpSaldo` (legacy ScriptCase); se calcula siempre en consulta.

**Listado de clientes:** `idClientes > 1` + exclusión `tipoCliente != 1`.

**Detalle por cliente:** solo movimientos de la cuenta CC (`idCuentas =
id_cuenta_cc`), orden `fechhora` DESC; filtrable por Desde/Hasta. El saldo del
encabezado, el saldo anterior y el total del período usan el mismo filtro.
Pie de tabla (también en PDF y Excel): **Total período** (suma de montos del
rango) y **TOTAL A LA FECHA** (saldo anterior + total del período; si no hay
fecha Desde, el saldo anterior se toma como 0).
Columnas: #, Nombre, Id Cuentas, Fechhora, Monto, Obs (sin Concepto).

**Resalte de filas:** filas con `monto < 0` (egresos del cliente) se destacan
con fondo ámbar (`vl-cc-mov-row--negativo`).

**Archivos clave:**
- `app/Support/CuentaCorriente/CuentaCorrienteMovimientosConsulta.php`
- `app/Livewire/Clientes/CuentaCorrienteMovimientosIndex.php`
- `app/Livewire/Clientes/CuentaCorrienteMovimientosDetalle.php`
- `app/Support/CuentaCorriente/CuentaCorrienteMovimientosClientesTcpdf.php`
- `app/Support/CuentaCorriente/CuentaCorrienteMovimientosDetalleTcpdf.php`
- `app/Support/CuentaCorriente/CuentaCorrienteMovimientosExporter.php`

## Facade de saldo (cross-variant)

`App\Support\CuentaCorriente\CuentaCorrienteFacade::saldoClienteHoy(int $idClientes)`
delega a la consulta correcta según `TesoreriaConfig`. Usar este facade
en callers que no pertenecen directamente a ninguna variante:
- `DashboardClienteConsulta`
- `DescuentoDeterminacionResolver`

## Cableado de rutas

Las rutas `clientes.cuenta-corriente.*` siempre tienen los mismos nombres.
En `routes/web.php` se elige la clase Livewire / controller con:

```php
if (TesoreriaConfig::usaPacientes()) {
    Route::get('/', CuentaCorrienteMovimientosIndex::class)->name('clientes.cuenta-corriente.index');
    // ...
} else {
    Route::get('/', CuentaCorrienteIndex::class)->name('clientes.cuenta-corriente.index');
    // ...
}
```

El ítem de menú sidebar es único (`clientes.cuenta-corriente.index`).

## Resumen entre fechas

- Formulario: mismo Livewire `ResumenClienteEntreFechas` para ambas variantes.
- PDF: `tesoreria_movimientos` → `ResumenClienteEntreFechasPdfController` (protocolos, landscape A4);
  `tesoreria_pacientes` → `ResumenClienteEntreFechasMovimientosPdfController` (movimientos, landscape A4).
- Switch en `routes/web.php` → `clientes.resumen-entre-fechas.pdf`.

## Autogestión cliente (PacienteIndex)

- Saldo de encabezado: vía `CuentaCorrienteFacade::saldoClienteHoy`.
- Columna saldo corrido por protocolo: solo se calcula con `tesoreria_movimientos`
  (`mapaSaldoAcumuladoPorProtocolo`). Con `tesoreria_pacientes`, se pasa `[]`
  y la columna muestra 0 por fila (la CC no vive en `pacientes`).

## Qué no hacer

- No tocar `CuentaCorrienteConsulta` (NeoLab); no mezclar sus fórmulas con `movimientos`.
- No persistir `clientes.tmpSaldo` (legacy ScriptCase).
- No hardcodear `if (slug === 'labvetciudad')` — solo `TesoreriaConfig`.
- No cambiar `id_cuenta_cc` sin confirmar que la BD de ese tenant tiene esa FK en `mediodepago`.
- PDFs nuevos: TCPDF A4 (detalle/listado vertical; resumen entre fechas landscape explícito).

## Checklist al modificar

- [ ] ¿Leído este doc + `docs/11-tesoreria-por-tenant.md`?
- [ ] ¿El cambio toca la rama correcta (`tesoreria_movimientos` o `tesoreria_pacientes`)?
- [ ] ¿Facade actualizado si se agrega un método de saldo compartido?
- [ ] ¿Las rutas siguen usando los mismos `name()`?
- [ ] ¿Los archivos para producción incluyen `public/build/` si se tocó CSS/JS?
