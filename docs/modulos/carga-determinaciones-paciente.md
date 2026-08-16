# Módulo: Carga de determinaciones al paciente

En UI: **Determinaciones solicitadas** de un protocolo (`pacientes`).  
Código / BD: filas en `determinaciones` ligadas a `idPacientes` + `idTipodeterminaciones`.

## Propósito

Permitir cargar, editar y eliminar las determinaciones pedidas sobre un protocolo:
precio de lista (neto), descuento, precio final, derivación y (si existen columnas)
fechas de envío/devolución. Al alta materializa `renglones` para la carga de
resultados; al baja los elimina. Recalcula totales del protocolo (`pacientes.precio`
/ `pacientes.neto`).

No emite PDF ni es portal de clientes. No carga resultados reales: al materializar,
`renglones.valor` queda en `"PENDIENTE"` (excepto `tipoItem` 2 valor fijo y 3 título,
que quedan vacíos; y `tipoItem` 8 texto largo, que copia `itemsinforme.textos`),
`valor2` vacío, y `renglones.mostrar` copia `itemsinforme.mostrar` (0/1).

Entrada: listado de protocolos → icono determinaciones →
`route('protocolos.determinaciones', $idPacientes)`.

## Modalidades / variantes

| Variante | Config | Efecto en esta pantalla |
|----------|--------|-------------------------|
| Derivación **Sí/No** | `tenant.tipodeterminaciones.derivacion` = `si_no` (default) | Select No/Sí; persiste `0`/`1` en `determinaciones.idDerivaciones` |
| Derivación **catálogo** | `derivacion` = `catalogo` | Select “Seleccione” + centros de `derivaciones`; persiste FK real (`0` = sin derivar) |
| Descuento **% cliente** | `tenant.precios.descuento` = `cliente_porcentaje` (default) | % de `clientes.descuento` sobre todas las determinaciones |
| Descuento **perfiles / volumen** | `perfiles_volumen_mes_anterior` (alqu) | Solo tipos con `tipodeterminaciones.perfil > 0`; % según cantidad de perfiles del **mes anterior** a `pacientes.fechhoy` |
| Lista de precios **por cliente** | `tenant.precios.lista` = `cliente` (default) | Neto según `clientes.listaPreciosCliente` (alta = lista 1) |
| Lista de precios **por protocolo** | `lista` = `paciente` (laboratoriosiv) | Neto según `pacientes.listaPreciosPaciente` → `precio` / `precio2` / `precio3` |
| Fechas de derivación | Columnas aditivas `fechaEnvioDeriv` / `fechaDevolucDeterm` | Si existen: columnas en grilla; si no, no se muestran |
| Columna `neto` | Migración aditiva en `determinaciones` / `pacientes` | Si falta: se interpreta `precio` legacy como lista en memoria |

Tenants con `catalogo` hoy: **alqu**, **neolab**, **laboratoriosiv**, **labvetciudad**, **civetfranca**.  
`mostrar_columna_perfil` afecta el **ABM** de tipodeterminaciones, **no** esta grilla.

## Actores y permisos

| Actor | Permiso | Alcance |
|-------|---------|---------|
| Staff (Menú de Laboratorio) | `PermisosIaCatalog::PROTOCOLOS` (**3**) + `menu.portal:staff` | Ruta `protocolos.determinaciones`; `abort_unless` en acciones Livewire |
| Usuario cliente | — | **No** usa esta pantalla (middleware staff) |
| Portal / informes / resultados | permisos 4–5, etc. | Fuera de alcance |

`labCtx()`: si el usuario tiene `idClientes` de cliente, el protocolo debe estar en su alcance.  
Pagos globales (`Paciente::esPagoGlobal()`): **404** — no se cargan determinaciones ahí.

## Tablas y campos críticos

| Tabla | Rol |
|-------|-----|
| `determinaciones` | Fuente de verdad de lo pedido (PK `idDeterminaciones`) |
| `pacientes` | Cabecera; totales `precio` / `neto`; `fechhoy` para descuento volumen |
| `tipodeterminaciones` | Catálogo; `precio` = lista 1; `perfil`; `derivacion` (centro predeterminado en modo catálogo: ABM y preselección al elegir tipo en el protocolo). `destino` es código legacy 0–3, no el FK. |
| `derivaciones` | Centros (modo catálogo) |
| `renglones` | Materializados al alta / borrados al eliminar determinación |
| `renglonesxdeterminacion` + `itemsinforme` | Plantilla de renglones (solo lectura en este módulo) |
| `clientes` | % descuento (modo cliente) / datos de cabecera |

**Precios en fila:** `neto` = lista; `descuento` = importe en pesos; `precio` = neto − descuento.  
**Unicidad tipo×protocolo:** solo en aplicación (`tipoYaCargado`); **no** hay UNIQUE en BD.

## Flujo principal

1. **Agregar** (F2 / Insert / botón): una sola `filaNueva`; combobox de tipos disponibles (excluye ya cargados).
2. **Elegir tipo:** resuelve neto + descuento + precio. En modo **catálogo** preselecciona `tipodeterminaciones.derivacion` si es un centro válido (si es `0` o inválido, “Seleccione”). El select **sigue editable**. Si el centro predeterminado implica envío, completa `fechaEnvioDeriv` = hoy (igual que al elegir a mano). Modo **Sí/No:** sigue en “No” (no usa `destino` como centro).
3. **Usuario** puede ajustar neto/descuento, cambiar derivación y fechas.
4. **Confirmar:** valida → `INSERT determinaciones` → `RenglonesMaterializer::asegurarParaDeterminacion` → actualiza totales → abre otra fila nueva.
5. **Editar fila existente:** blur de neto/descuento → `guardarFila`; cambio de derivación → `guardarDerivacion` (si `idDerivaciones > 0` pone `fechaEnvioDeriv = hoy`; si vuelve a `0`, limpia fecha); fechas con `guardarFecha*`.
6. **Eliminar:** borra determinación + **todos** los `renglones` de ese tipo en el protocolo → recalcula totales.

Rate limits: save ~40/min, delete ~20/min por usuario (`prot-det-save:*` / `prot-det-del:*`).

## Fuente de verdad

| Dato | Quién escribe | Quién solo lee |
|------|---------------|----------------|
| Filas pedidas | `PacienteDeterminaciones` → `determinaciones` | Listados, derivaciones, facturación |
| Totales protocolo | `actualizarTotalProtocolo()` → `pacientes.precio` / `neto` | Listado protocolos, informes |
| Renglones (`valor` = PENDIENTE; tipoItem 2/3 vacío; 8 = textos; `mostrar` ← `itemsinforme`) | `RenglonesMaterializer` al alta/baja | Módulo de resultados |
| Precio de lista | — | `tipodeterminaciones.precio` / `precio2` / `precio3` según `tenant.precios.lista` (ver [../12-listas-de-precios-por-tenant.md](../12-listas-de-precios-por-tenant.md)) |
| Default centro del tipo | ABM tipodeterminaciones (`derivacion` en modo catálogo) | Al **elegir el tipo** en esta pantalla (preselección editable) |
| Centros | ABM centros de derivación | Select modo catálogo |

## Archivos clave

| Pieza | Ruta |
|-------|------|
| Componente | `app/Livewire/Protocolos/PacienteDeterminaciones.php` |
| Vista | `resources/views/livewire/protocolos/paciente-determinaciones.blade.php` |
| Combobox | `vlProtDetCombobox` en `resources/js/app.js` |
| Materialización | `app/Support/Resultados/RenglonesMaterializer.php` |
| Precios / descuentos | `app/Support/Precios/PrecioDeterminacionResolver.php`, `ListaPreciosConfig.php`, `DescuentoDeterminacionResolver.php`, `DescuentoDeterminacionConfig.php`, `DescuentoPerfilesVolumenConsulta.php` |
| Flags UI derivación | `app/Support/Tipodeterminaciones/TipodeterminacionesGridConfig.php` |
| Modelo | `app/Models/Determinacion.php` |
| Ruta | `routes/web.php` → `protocolos.determinaciones` (`permiso:3`) |
| Config | `config/tenant.php` → `tipodeterminaciones.derivacion`, `precios.descuento` + overrides en `config/tenants/{slug}.php` |

Hermanos: ABM tipos (`TipodeterminacionIndex`, permiso 2); Gestión de Derivaciones (`DerivacionIndex`, permiso 3).

## Efecto sobre stock de reactivos

Al **alta** de una determinación (`confirmarNueva`), tras crear la fila y materializar renglones,
se llama a `StockReactivosService::descontarPorTipo($idTipo)` que descuenta de `reactivos.cantidad`
la cantidad configurada en `reactivoxdeterminacion` para ese tipo.

Al **baja** de una determinación (`eliminar`), tras borrar la fila y los renglones,
se llama a `StockReactivosService::reponerPorTipo($idTipo)` que devuelve las mismas cantidades.

Si el stock queda `<= minAviso`, se muestra `vl-swal-error` con título "Stock bajo" sin bloquear.
Si el tipo no tiene reactivos configurados en `reactivoxdeterminacion`, no hay efecto.

Detalle: [`docs/modulos/stock-reactivos.md`](stock-reactivos.md).

## Qué no hacer / reglas de negocio

1. Al elegir el tipo en modo catálogo **sí** preseleccionar `tipodeterminaciones.derivacion` (centro válido); el select permanece editable. No usar `destino` (código 0–3).
2. **No** pisar un centro que el usuario ya cambió en la misma fila nueva (solo aplicar default cuando cambia el tipo).
3. **No permitir** dos veces el mismo `idTipodeterminaciones` en el mismo protocolo (UI + `tipoYaCargado`).
4. **No cargar** determinaciones sobre protocolos de pago global.
5. Al eliminar determinación: **sí** borrar renglones/resultados de ese tipo en el protocolo (comportamiento actual; no “suavizar” sin pedido explícito).
6. Si el tipo no tiene plantilla en `renglonesxdeterminacion`, la determinación se crea igual pero **sin** renglones.
7. Descuento calculado es **sugerencia**: el usuario puede editar el importe; al blur solo se recalcula `precio = neto − descuento`.
8. Modo volumen (alqu): tipos con `perfil = 0` → descuento 0; umbrales miran el mes **anterior** a `fechhoy`.
9. Diálogos: `vl-swal-*` / helpers `vlSwal*`, no `wire:confirm` / `alert`.
10. Esta pantalla es **staff**; no exponerla en portal cliente ni poner IDs sensibles en URLs de portal (aquí el `{id}` de protocolo es staff interno). El veterinario ve el mismo detalle en solo lectura desde autogestión (`cliente.pacientes.determinaciones`; ver [`autogestion-detalle-determinaciones.md`](autogestion-detalle-determinaciones.md)).
11. Precio de lista al alta: respetar `tenant.precios.lista` (`cliente` / `paciente`). No hardcodear `precio` lista 1 si el tenant usa otra lista. No recalcular filas ya guardadas al cambiar la lista del protocolo/cliente.

## Checklist al modificar

- [ ] ¿Modo `si_no` y `catalogo` siguen correctos según `TipodeterminacionesGridConfig`?
- [ ] ¿Al elegir tipo en catálogo se preselecciona `tipodeterminaciones.derivacion` y el select sigue editable?
- [ ] ¿Al elegir centro/Sí se completa fecha de envío; al limpiar se vacía?
- [ ] ¿Sigue bloqueado el duplicado de tipo en el mismo protocolo?
- [ ] ¿Alta materializa renglones y baja los elimina?
- [ ] ¿Totales `pacientes.precio` / `neto` se recalculan tras alta, edición de importes y baja?
- [ ] ¿Descuentos `cliente_porcentaje` y `perfiles_volumen_mes_anterior` intactos?
- [ ] ¿El neto al elegir tipo usa `resolverPrecioListaParaPaciente` (no siempre lista 1)?
- [ ] ¿Guards `Schema::hasColumn` para `neto` y fechas de derivación?
- [ ] ¿Permiso 3 + alcance `labCtx` / no pago global?
- [ ] ¿Tenant nuevo necesita override de `derivacion`, `precios.descuento` o `precios.lista`?
