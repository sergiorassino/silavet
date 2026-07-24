# Módulo: Tesorería

En UI: grupo **Tesorería** del Menú de Laboratorio (staff).  
Código: dos implementaciones por tenant; la clave de config **no** coincide con el
nombre de la tabla física (ver tabla de variantes).

Detalle de flags por laboratorio: [../11-tesoreria-por-tenant.md](../11-tesoreria-por-tenant.md).

## Propósito

Registrar **ingresos y egresos de caja** del laboratorio, con pantallas auxiliares
según la variante (cuentas contables NeoLab, o conceptos / proveedores / saldos
labvetciudad).

No es el ABM de protocolos analíticos. En la variante NeoLab los movimientos de
caja **conviven** en `pacientes` vía `tipoRegistro`; en labvetciudad la caja vive
en la tabla `movimientos` y los protocolos no se mezclan con tesorería.

Entrada típica: sidebar → **Movimientos** →
`route('tesoreria.movimientos.index')` (mismo path; el componente Livewire cambia
según variante).

## Modalidades / variantes

| Clave `implementacion` | Tabla de caja | UI principal | Labs típicos |
|------------------------|---------------|--------------|--------------|
| `tesoreria_movimientos` (default) | `pacientes` (`tipoRegistro` 2/3) | `MovimientoIndex` | alqu, neolab, mayoría |
| `tesoreria_pacientes` | `movimientos` | `MovimientosCajaIndex` | labvetciudad |

```php
// config/tenant.php (default)
'tesoreria' => ['implementacion' => 'tesoreria_movimientos'],

// config/tenants/labvetciudad.php
'tesoreria' => ['implementacion' => 'tesoreria_pacientes'],
```

Helper: `App\Support\Tesoreria\TesoreriaConfig`.

| Método | Clave | Persistencia real |
|--------|-------|-------------------|
| `usaMovimientos()` | `tesoreria_movimientos` | filas en `pacientes` |
| `usaPacientes()` | `tesoreria_pacientes` | tabla `movimientos` |

**Trampa de nombres:** los métodos siguen la clave de config, **no** el nombre de
la tabla. Al documentar o codear, citar siempre clave + clase + tabla juntos.

### Menú por variante

| Opción | `tesoreria_movimientos` | `tesoreria_pacientes` |
|--------|:-----------------------:|:--------------------:|
| Movimientos | sí | sí |
| Transferencias Intercuenta | sí | — |
| Gestión de Cuentas Contables | sí | — |
| Gestión de Cuentas Detalle | sí | — |
| Movimientos entre Cuentas | — | sí |
| Saldos por Día | — | sí |
| Gestión de Conceptos | — | sí |
| Gestión de Proveedores | — | sí |

### Config extra (solo caja `movimientos`)

```php
'tesoreria' => [
    'movimientos' => [
        'concepto_ingresos_diarios' => 'Ingresos Diarios',
        'concepto_cadeteria' => 'Cadetería',
        'dias_protocolos' => 7,
    ],
],
```

Los conceptos especiales se resuelven por **nombre** en `conceptos` (no por id fijo).

## Actores y permisos

| Actor | Permiso | Alcance |
|-------|---------|---------|
| Staff (Menú de Laboratorio) | `PermisosIaCatalog::FACTURACION` (**6**) + `menu.portal:staff` | Todas las rutas `tesoreria.*`; `abort_unless` en Livewire |
| Usuario cliente / portal | — | **No** usa este módulo |

Paginación listados: 50/página, `vendor.pagination.vl-compact`.  
Diálogos: `vl-swal-*` / `vlSwal*`, no `wire:confirm` / `alert`.

## Tablas y campos críticos

### Variante `tesoreria_movimientos` → `pacientes`

| Campo | Uso |
|-------|-----|
| `tipoRegistro` | `2` = ingreso (`TIPO_INGRESO`), `3` = egreso (`TIPO_EGRESO`); protocolos = `1` |
| `fechhoy` | fecha/hora del movimiento |
| `idClientes` | ingreso: cliente real; egreso: fijo `Paciente::ID_CLIENTES_EGRESO` (= 1) |
| `idCuentasdetalle` | egreso: ítem de `cuentasdetalle` (UI “proveedor”); ingreso: `0` |
| `pagado` | importe (> 0) |
| `idMediodepago` | medio de pago / “cuenta” de caja |
| `observaciones` | texto libre |
| `estado` | `'Pago'` / `'Egreso'` |

Catálogos: `clientes`, `cuentas`, `cuentasdetalle`, `mediodepago`.

### Variante `tesoreria_pacientes` → `movimientos`

| Campo | Uso |
|-------|-----|
| `idTipoMovimiento` | `1` ingreso / `2` egreso (`TipoMovimiento::INGRESO` / `EGRESO`) |
| `idCuentas` | FK a **`mediodepago.id`** (UI etiqueta “Cuenta”; **no** es `cuentas`) |
| `idConcepto` | `conceptos` con `tipoConcepto` = tipo de movimiento |
| `idProveedores` | egresos opcionales; filtrados por `idConceptos` |
| `idPacientes` / `idClientes` | Ingresos Diarios / Cadetería (tomados del protocolo) |
| `monto` | ingresos positivos; egresos **negativos** |
| `fechhora`, `comprobante`, `obs` | metadatos |

Flags en protocolo (misma variante): `pacientes.cadete`, `cargado`, `cargadoCadete`
(`✅` al cargar; se vacían si se elimina el último movimiento del concepto para ese protocolo).

En listados de protocolos / dashboard de esta variante: **no** exigir
`tipoRegistro = 1` (legacy suele ser `0`); excluir solo ingresos/egresos NeoLab
(`tipoRegistro` 2 y 3) por compatibilidad.

## Flujo principal

### A) `tesoreria_movimientos` (NeoLab / mayoría)

1. Listado: `pacientes` con `tipoRegistro IN (2, 3)`. Toggle **Hoy** (default:
   solo `fechhoy` de hoy) / **Historial** (con filtro opcional Desde/Hasta).
2. Alta/edición (modal): tipo → fecha/hora → (ingreso: cliente | egreso: cuenta + detalle) → importe → medio de pago → observaciones.
3. Persistencia: `Paciente::create` / `update` (misma tabla que protocolos).
4. **Transferencias Intercuenta:** dos filas en `pacientes` (egreso origen + ingreso destino), `idClientes = 1`, mismo importe/obs, distinto `idMediodepago`.
5. ABM de `cuentas` y `cuentasdetalle`.

### B) `tesoreria_pacientes` (labvetciudad)

1. Listado: tabla `movimientos` (orden `fechhora` desc).
2. Formulario según tipo / concepto:

| Situación | Campos extras |
|-----------|---------------|
| Egreso | Proveedor opcional (filtrado por concepto) |
| Ingreso + **Ingresos Diarios** | Selector de protocolo del día → monto = `pacientes.precio`; marca `cargado` |
| Ingreso + **Cadetería** | Protocolo con `cadete > 0` → monto = `cadete`; marca `cargadoCadete` |
| Resto de ingresos | Sin protocolo ni proveedor |

3. **Nuevo Asiento** (modal en el listado): transferencia con **cliente obligatorio** → dos filas en `movimientos` (egreso origen + ingreso destino).
4. **Movimientos entre Cuentas** (página de menú): mismo par de inserts **sin cliente** (`idClientes = 0`).
5. **Saldos por Día:** una fila por día / saldos por `mediodepago`; expandir día → cuentas; expandir cuenta → movimientos + suma (`SaldosPorDiaConsulta`).
6. **Eliminar** movimiento: si era el último Ingresos Diarios/Cadetería de ese protocolo → limpia `cargado` / `cargadoCadete`.
7. En listado de protocolos: columna **Cadete** editable inline (`PacienteIndex::guardarCadete`).

Rate limits típicos: save ~30/min; delete caja ~10/min por usuario.

## Fuente de verdad

| Dato | Quién escribe | Quién solo lee |
|------|---------------|----------------|
| Variante activa | config tenant + `TesoreriaConfig` | Rutas, sidebar, guards Livewire |
| Caja NeoLab | `MovimientoIndex` / `TransferenciaIntercuenta` → `pacientes` | Listados, AFIP modo movimiento |
| Caja labvetciudad | `MovimientosCajaIndex` / asientos / entre cuentas → `movimientos` | Saldos por día |
| Cuentas contables NeoLab | ABM `Cuenta*` / `CuentaDetalle*` | Formulario de egresos NeoLab |
| Conceptos / proveedores | ABM `Concepto*` / `Proveedor*` | Formulario de caja labvetciudad |
| “Cuenta” en caja labvetciudad | ABM medios de pago (fuera de este menú) | `mediodepago` vía `idCuentas` |
| Cadete / marcas cargado | Protocolos + caja labvetciudad | Selectores Ingresos Diarios / Cadetería |
| Conceptos especiales | Config por nombre + fila en `conceptos` | Selectores y flags |

### AFIP (relacionado, no es el núcleo)

- Config: `tenant.facturacion_afip` (`modo`: `paciente` \| `movimiento`).
- **`modo = movimiento`** (ej. alqu): icono en `MovimientoIndex` sobre **ingresos** en `pacientes`; emite contra ese `idPacientes`.
- **No** aplica a la UI de tabla `movimientos` (`MovimientosCajaIndex`).

## Archivos clave

| Pieza | Ruta |
|-------|------|
| Config helper | `app/Support/Tesoreria/TesoreriaConfig.php` |
| Saldos | `app/Support/Tesoreria/SaldosPorDiaConsulta.php` |
| Config default | `config/tenant.php` → `tesoreria` |
| Override labvetciudad | `config/tenants/labvetciudad.php` |
| Rutas | `routes/web.php` (grupo `tesoreria/*`, `permiso:6`) |
| Sidebar | `resources/views/layouts/partials/sidebar-grupos-menu.blade.php` |
| Doc flags tenant | `docs/11-tesoreria-por-tenant.md` |

### Livewire + Blade (`app/Livewire/Tesoreria/`, `resources/views/livewire/tesoreria/`)

| Variante | Clase | Vista |
|----------|-------|-------|
| movimientos | `MovimientoIndex` | `movimiento-index.blade.php` |
| movimientos | `TransferenciaIntercuenta` | `transferencia-intercuenta.blade.php` |
| movimientos | `CuentaIndex` / `CuentaForm` | `cuenta-*.blade.php` |
| movimientos | `CuentaDetalleIndex` / `CuentaDetalleForm` | `cuenta-detalle-*.blade.php` |
| pacientes | `MovimientosCajaIndex` | `movimientos-caja-index.blade.php` |
| pacientes | `MovimientosEntreCuentas` | `movimientos-entre-cuentas.blade.php` |
| pacientes | `SaldosPorDiaIndex` | `saldos-por-dia-index.blade.php` |
| pacientes | `ConceptoIndex` / `ConceptoForm` | `concepto-*.blade.php` |
| pacientes | `ProveedorIndex` / `ProveedorForm` | `proveedor-*.blade.php` |

Modelos: `Paciente`, `Movimiento`, `Concepto`, `Proveedor`, `Cuenta`, `CuentaDetalle`,
`MedioDePago`, `TipoMovimiento`, `Cliente`.

Cross-cutting: `PacienteIndex` (cadete / filtro `tipoRegistro`);
`DashboardLabConsulta`; AFIP (`FacturacionAfipConfig`, icono en `MovimientoIndex`).

## Qué no hacer / reglas de negocio

1. **No confundir claves con tablas:** `tesoreria_movimientos` ≠ tabla `movimientos`;
   `tesoreria_pacientes` ≠ filas de caja en `pacientes`.
2. **No mezclar UIs:** no montar `MovimientoIndex` en labvetciudad ni
   `MovimientosCajaIndex` en NeoLab (rutas + `abort_unless` + 404 si falta tabla).
3. En caja labvetciudad, **“Cuenta” = `mediodepago`**, no `cuentas` / `cuentasdetalle`.
4. En NeoLab, el “proveedor” de egreso es **`cuentasdetalle`**, no la tabla `proveedores`.
5. Egresos en `movimientos`: persistir `monto` **negativo** (la UI valida positivo y niega).
6. Egresos NeoLab: siempre `idClientes = 1`; no elegir cliente libre.
7. **Asiento** (modal) exige cliente; **Movimientos entre Cuentas** (menú) no.
8. No eliminar conceptos/proveedores con movimientos (o proveedores) asociados.
9. En labvetciudad **no exigir** `tipoRegistro = 1` en listados de protocolos.
10. Conceptos Ingresos Diarios / Cadetería: resolver por **nombre** de config, no hardcodear ids.
11. No alterar tablas legacy; columnas nuevas solo con migración aditiva + SQL entregado.
12. AFIP modo `movimiento` solo sobre ingresos NeoLab en `pacientes`, no sobre `movimientos`.
13. Diálogos: `vlSwal*`; sin `wire:confirm` / `alert`.

## Checklist al modificar

- [ ] ¿Se leyó este doc + `docs/11-tesoreria-por-tenant.md`?
- [ ] ¿Queda clara la variante (`TesoreriaConfig::implementacion()` / slug)?
- [ ] ¿El cambio toca solo la rama correcta (tabla + Livewire + menú/rutas)?
- [ ] ¿Listado NeoLab: Hoy / Historial y Desde/Hasta sobre `fechhoy` intactos?
- [ ] ¿`usaPacientes()` / `usaMovimientos()` siguen alineados con sidebar y `routes/web.php`?
- [ ] Si Ingresos Diarios / Cadetería: ¿flags `cargado*` y selectores de protocolo intactos?
- [ ] Si protocolos en labvetciudad: ¿filtro `tipoRegistro` y columna Cadete intactos?
- [ ] Si AFIP: ¿`facturacion_afip.modo` y el id es `pacientes.idPacientes`?
- [ ] ¿Permiso 6 + rate limits + paginación 50 + `vlSwal*`?
- [ ] ¿Tenant nuevo a caja: BD con `movimientos`/`conceptos`/`tipomovimiento`/`proveedores` + config `tesoreria_pacientes`?
- [ ] ¿Si cambió el comportamiento documentado, se actualizó este archivo y/o `docs/11-…`?
