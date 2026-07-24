# Tesorería — variantes por laboratorio

> Cómo se elige la implementación de tesorería según el tenant.
> Complementa [07-versionado-de-modulos-por-tenant.md](07-versionado-de-modulos-por-tenant.md) §3.3.
> Especificación operativa del módulo: [modulos/tesoreria.md](modulos/tesoreria.md).

---

## 1. Resumen de variantes

| Clave `implementacion` | Tabla principal | Labs típicos | Estado |
|------------------------|-----------------|--------------|--------|
| `tesoreria_movimientos` | `pacientes` (`tipoRegistro` ingreso/egreso) | alqu, neolab, mayoría | **Implementada** |
| `tesoreria_pacientes` | `movimientos` | labvetciudad | **Implementada** (caja / movimientos) |

Config:

```php
// config/tenant.php (default)
'tesoreria' => [
    'implementacion' => 'tesoreria_movimientos',
],

// config/tenants/labvetciudad.php
'tesoreria' => [
    'implementacion' => 'tesoreria_pacientes',
],
```

Helper: `App\Support\Tesoreria\TesoreriaConfig`
(`usaMovimientos()` / `usaPacientes()` según la clave).

---

## 2. Variante `tesoreria_movimientos`

- Listado/alta en `App\Livewire\Tesoreria\MovimientoIndex`.
- Persiste ingresos/egresos como filas en `pacientes`.
- Listado: toggle **Hoy** / **Historial** + filtro opcional Desde/Hasta sobre `fechhoy`.
- Incluye menú de transferencias y ABM de `cuentas` / `cuentasdetalle`.

---

## 3. Variante `tesoreria_pacientes`

- Listado/alta en `App\Livewire\Tesoreria\MovimientosCajaIndex`.
- Tabla `movimientos` (legacy labvetciudad).
- En la UI, **Cuenta** = `mediodepago` (`movimientos.idCuentas`).
- Conceptos en `conceptos` filtrados por `tipoConcepto` = `tipomovimiento.id`.
- Proveedores en `proveedores` filtrados por `idConceptos`.
- El módulo de protocolos (`PacienteIndex`) y el dashboard de laboratorio
  (`DashboardLabConsulta`) **no** exigen `tipoRegistro = 1`: en este esquema los
  protocolos legacy suelen tener `tipoRegistro = 0` (la tesorería no se mezcla en
  `pacientes`). Solo se excluyen ingresos/egresos (`tipoRegistro` 2 y 3) por
  compatibilidad con tenants NeoLab.
- En el listado de protocolos se muestra la columna **Cadete** (`pacientes.cadete`)
  editable inline (blur), usada luego por el concepto Cadetería en movimientos.

### Formulario según tipo / concepto

| Situación | Campos extras |
|-----------|---------------|
| Egreso | Proveedores (opcional, filtrado por concepto) |
| Ingreso + concepto **Ingresos Diarios** | Selector de protocolo (`pacientes` del día elegido) |
| Ingreso + concepto **Cadetería** | Selector de protocolo con `cadete > 0` |
| Resto de ingresos | Sin selector de protocolo ni proveedor |

Al elegir un protocolo de Ingresos Diarios o Cadetería se completa el monto (y se guarda `idClientes` / `idPacientes`). Los egresos se persisten con **monto negativo**.

En el modal de edición hay botón **Eliminar** (con confirmación). Si era el último movimiento
de Ingresos Diarios o Cadetería para ese protocolo, se limpia `cargado` / `cargadoCadete`.

**Nuevo Asiento** (modal en el listado de Movimientos): transferencia entre cuentas
con **cliente obligatorio**. Genera dos filas en `movimientos` (egreso origen + ingreso destino)
compartiendo `idClientes`, `fechhora` y `obs`.

**Movimientos entre Cuentas** (página de menú, distinta del asiento):
`App\Livewire\Tesoreria\MovimientosEntreCuentas`. Mismo par de inserts en `movimientos`,
pero **sin cliente** (flujo legacy ScriptCase: fecha, origen, destino, monto, obs).

Nombres de concepto configurables:

```php
'tesoreria' => [
    'movimientos' => [
        'concepto_ingresos_diarios' => 'Ingresos Diarios',
        'concepto_cadeteria' => 'Cadetería',
        'dias_protocolos' => 7,
    ],
],
```

---

## 4. Menú

Con `tesoreria_pacientes` se muestran **Movimientos**, **Movimientos entre Cuentas**,
**Saldos por Día**, **Gestión de Conceptos** y **Gestión de Proveedores** en el grupo
Tesorería (no transferencias ni ABM de cuentas contables NeoLab).

**Saldos por Día** (`App\Livewire\Tesoreria\SaldosPorDiaIndex`): una fila por día con
saldo inicial/final por cuenta (`mediodepago`); al expandir, variación del día por cuenta;
al expandir una cuenta, movimientos de ese día + fila Suma.

**Gestión de Conceptos** (`App\Livewire\Tesoreria\ConceptoIndex` / `ConceptoForm`): ABM de
`conceptos` (`tipoConcepto`, `concepto`, `orden`). No elimina si hay movimientos o proveedores asociados.

**Gestión de Proveedores** (`App\Livewire\Tesoreria\ProveedorIndex` / `ProveedorForm`): ABM de
`proveedores` (`idConceptos`, `proveedor`, `cuit`). No elimina si hay movimientos asociados.

Con `tesoreria_movimientos`: **Movimientos**, **Transferencias Intercuenta**,
**Gestión de Cuentas Contables** y **Gestión de Cuentas Detalle**.

---

## 5. Agregar un tenant a la variante labvetciudad (`tesoreria_pacientes`)

1. Confirmar que la BD tiene `movimientos`, `conceptos`, `tipomovimiento`, `proveedores`.
2. En `config/tenants/{slug}.php` setear `'tesoreria' => ['implementacion' => 'tesoreria_pacientes']`.
3. Ajustar nombres de concepto si difieren del default.
