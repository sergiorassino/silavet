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
que quedan vacíos; y `tipoItem` 8 texto largo, que copia `itemsinforme.textos`)
y `valor2` vacío.

Entrada: listado de protocolos → icono determinaciones →
`route('protocolos.determinaciones', $idPacientes)`.

## Modalidades / variantes

| Variante | Config | Efecto en esta pantalla |
|----------|--------|-------------------------|
| Derivación **Sí/No** | `tenant.tipodeterminaciones.derivacion` = `si_no` (default) | Select No/Sí; persiste `0`/`1` en `determinaciones.idDerivaciones` |
| Derivación **catálogo** | `derivacion` = `catalogo` | Select “Seleccione” + centros de `derivaciones`; persiste FK real (`0` = sin derivar) |
| Descuento **% cliente** | `tenant.precios.descuento` = `cliente_porcentaje` (default) | % de `clientes.descuento` sobre todas las determinaciones |
| Descuento **perfiles / volumen** | `perfiles_volumen_mes_anterior` (alqu) | Solo tipos con `tipodeterminaciones.perfil > 0`; % según cantidad de perfiles del **mes anterior** a `pacientes.fechhoy` |
| Fechas de derivación | Columnas aditivas `fechaEnvioDeriv` / `fechaDevolucDeterm` | Si existen: columnas en grilla; si no, no se muestran |
| Columna `neto` | Migración aditiva en `determinaciones` / `pacientes` | Si falta: se interpreta `precio` legacy como lista en memoria |

Tenants con `catalogo` hoy: **alqu**, **neolab**, **laboratoriosiv**.  
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
| `tipodeterminaciones` | Catálogo; `precio` = lista 1; `perfil`; `destino` (solo ABM) |
| `derivaciones` | Centros (modo catálogo) |
| `renglones` | Materializados al alta / borrados al eliminar determinación |
| `renglonesxdeterminacion` + `itemsinforme` | Plantilla de renglones (solo lectura en este módulo) |
| `clientes` | % descuento (modo cliente) / datos de cabecera |

**Precios en fila:** `neto` = lista; `descuento` = importe en pesos; `precio` = neto − descuento.  
**Unicidad tipo×protocolo:** solo en aplicación (`tipoYaCargado`); **no** hay UNIQUE en BD.

## Flujo principal

1. **Agregar** (F2 / Insert / botón): una sola `filaNueva`; combobox de tipos disponibles (excluye ya cargados).
2. **Elegir tipo:** resuelve neto + descuento + precio. **No** copia `tipodeterminaciones.destino`. Derivación queda en `0` (“Seleccione” / “No”); fecha de envío **vacía**.
3. **Usuario** puede ajustar neto/descuento, elegir derivación y fechas.
4. **Confirmar:** valida → `INSERT determinaciones` → `RenglonesMaterializer::asegurarParaDeterminacion` → actualiza totales → abre otra fila nueva.
5. **Editar fila existente:** blur de neto/descuento → `guardarFila`; cambio de derivación → `guardarDerivacion` (si `idDerivaciones > 0` pone `fechaEnvioDeriv = hoy`; si vuelve a `0`, limpia fecha); fechas con `guardarFecha*`.
6. **Eliminar:** borra determinación + **todos** los `renglones` de ese tipo en el protocolo → recalcula totales.

Rate limits: save ~40/min, delete ~20/min por usuario (`prot-det-save:*` / `prot-det-del:*`).

## Fuente de verdad

| Dato | Quién escribe | Quién solo lee |
|------|---------------|----------------|
| Filas pedidas | `PacienteDeterminaciones` → `determinaciones` | Listados, derivaciones, facturación |
| Totales protocolo | `actualizarTotalProtocolo()` → `pacientes.precio` / `neto` | Listado protocolos, informes |
| Renglones (`valor` = PENDIENTE; tipoItem 2/3 vacío; 8 = textos) | `RenglonesMaterializer` al alta/baja | Módulo de resultados |
| Precio de lista | — | `tipodeterminaciones.precio` (lista 1; no usa precio2/3) |
| Default destino del tipo | ABM tipodeterminaciones (`destino`) | **No** se aplica al cargar en protocolo |
| Centros | ABM centros de derivación | Select modo catálogo |

## Archivos clave

| Pieza | Ruta |
|-------|------|
| Componente | `app/Livewire/Protocolos/PacienteDeterminaciones.php` |
| Vista | `resources/views/livewire/protocolos/paciente-determinaciones.blade.php` |
| Combobox | `vlProtDetCombobox` en `resources/js/app.js` |
| Materialización | `app/Support/Resultados/RenglonesMaterializer.php` |
| Precios / descuentos | `app/Support/Precios/PrecioDeterminacionResolver.php`, `DescuentoDeterminacionResolver.php`, `DescuentoDeterminacionConfig.php`, `DescuentoPerfilesVolumenConsulta.php` |
| Flags UI derivación | `app/Support/Tipodeterminaciones/TipodeterminacionesGridConfig.php` |
| Modelo | `app/Models/Determinacion.php` |
| Ruta | `routes/web.php` → `protocolos.determinaciones` (`permiso:3`) |
| Config | `config/tenant.php` → `tipodeterminaciones.derivacion`, `precios.descuento` + overrides en `config/tenants/{slug}.php` |

Hermanos: ABM tipos (`TipodeterminacionIndex`, permiso 2); Gestión de Derivaciones (`DerivacionIndex`, permiso 3).

## Qué no hacer / reglas de negocio

1. **No preseleccionar** centro ni Sí al elegir el tipo: siempre `idDerivaciones = 0` y fecha de envío vacía hasta que el usuario elija.
2. **No copiar** `tipodeterminaciones.destino` a la fila del protocolo (ese campo es del ABM).
3. **No permitir** dos veces el mismo `idTipodeterminaciones` en el mismo protocolo (UI + `tipoYaCargado`).
4. **No cargar** determinaciones sobre protocolos de pago global.
5. Al eliminar determinación: **sí** borrar renglones/resultados de ese tipo en el protocolo (comportamiento actual; no “suavizar” sin pedido explícito).
6. Si el tipo no tiene plantilla en `renglonesxdeterminacion`, la determinación se crea igual pero **sin** renglones.
7. Descuento calculado es **sugerencia**: el usuario puede editar el importe; al blur solo se recalcula `precio = neto − descuento`.
8. Modo volumen (alqu): tipos con `perfil = 0` → descuento 0; umbrales miran el mes **anterior** a `fechhoy`.
9. Diálogos: `vl-swal-*` / helpers `vlSwal*`, no `wire:confirm` / `alert`.
10. Esta pantalla es **staff**; no exponerla en portal cliente ni poner IDs sensibles en URLs de portal (aquí el `{id}` de protocolo es staff interno).

## Checklist al modificar

- [ ] ¿Modo `si_no` y `catalogo` siguen correctos según `TipodeterminacionesGridConfig`?
- [ ] ¿Al elegir tipo sigue en “Seleccione”/“No” con fecha de envío vacía?
- [ ] ¿Al elegir centro/Sí se completa fecha de envío; al limpiar se vacía?
- [ ] ¿Sigue bloqueado el duplicado de tipo en el mismo protocolo?
- [ ] ¿Alta materializa renglones y baja los elimina?
- [ ] ¿Totales `pacientes.precio` / `neto` se recalculan tras alta, edición de importes y baja?
- [ ] ¿Descuentos `cliente_porcentaje` y `perfiles_volumen_mes_anterior` intactos?
- [ ] ¿Guards `Schema::hasColumn` para `neto` y fechas de derivación?
- [ ] ¿Permiso 3 + alcance `labCtx` / no pago global?
- [ ] ¿Tenant nuevo necesita override de `derivacion` o `precios.descuento`?
