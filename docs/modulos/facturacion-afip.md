# Módulo: Facturación AFIP

Emisión individual de factura, nota de crédito y comanda. El emisor es el usuario
logueado (`permisoAfip`, CUIT, punto de venta y certificados). Hay **tres modos**
de origen del importe y **dos regímenes** fiscales. El régimen no cambia el modo.

## Modos (de dónde sale la factura)

| `facturacion_afip.modo` | Origen | Laboratorios |
|---|---|---|
| `paciente` | Protocolo (`precio`) o ingreso (`pagado`) | NeoLab, LVM, Civet Franca |
| `movimiento` | Ingreso en `pacientes` | Alqu |
| `movimiento_caja` | Ingreso en `movimientos`; el usuario elige receptor | Lab Vet Ciudad |

La comanda (tipo 888) es interna: no va a AFIP y no discrimina IVA en ningún régimen.

## Régimen (cómo se arma el comprobante)

Config: `facturacion_afip.regimen` en `config/tenant.php` (default) o
`config/tenants/{slug}.php`.

| Régimen | Comprobante | IVA en WSFE |
|---|---|---|
| `monotributo` (default) | Factura C (`cbte_tipo`, default 11) y la nota de crédito configurada (`nota_credito_tipo`, default 12). Letra y tipos salen del usuario (`CbteTipo`, `NtaCredTipo`) o de esa config. | `ImpNeto = ImpTotal`, `ImpIVA = 0`. No se informan alícuotas. |
| `responsable_inscripto` | Factura **A** (código 1) si el receptor es responsable inscripto y tiene CUIT. Factura **B** (código 6) si es monotributista, exento o consumidor final. Nota de crédito A = 3, B = 8. No usa `usuarios.CbteTipo`. | `ImpNeto` + `ImpIVA` + `AlicIva`. `ImpTotal = ImpNeto + ImpIVA`. |

Un laboratorio es uno u otro. No se mezcla en el mismo CUIT. Los tenants que no
declaran `regimen` siguen en monotributo. **NeoLab** está en `responsable_inscripto`
(alícuota 21 %, precio con IVA incluido, modo `paciente`). El CAE de NeoLab sigue
simulado (`simular => true`).

### Parámetros solo del responsable inscripto

- `alicuota_iva`: porcentaje. Default **21** (código AFIP 5). Válidas: 0, 2.5, 5, 10.5, 21, 27.
- `precio_incluye_iva`: default **true**. El precio del protocolo o el monto del movimiento es el total que paga el cliente; el IVA se descuenta hacia adentro. En `false`, ese precio es neto y el total de la factura suma el IVA. No se modifica el precio guardado en el protocolo.

En modo `paciente`, al emitir se elige **cliente** o **paciente**. En esa misma pantalla se cargan CUIT y DNI de cada uno (`clientes.cuit` / `clientes.dni`, `pacientes.cuit` / `pacientes.dni`). El protocolo arranca en paciente; el pago global, en cliente. Factura y comanda usan esa elección. La nota de crédito copia el receptor de la factura.

Al emitir como responsable inscripto, la pantalla pide además la condición de IVA del receptor (responsable inscripto, monotributo, exento o consumidor final). Factura A exige CUIT del elegido: en el paciente se lee `pacientes.cuit`; en el cliente, `clientes.cuit`. Esa condición se guarda en `compafip.CondicionIVAReceptorId`. La nota de crédito copia la factura original (letra, neto e IVA); no se vuelve a elegir.

Las columnas `compafip.impNeto`, `impIva` y `alicuotaIva` entran con
`php artisan migrate` (`2026_10_01_000001_add_compafip_iva_responsable_inscripto`).
Son nulas y no las escribe el monotributo. Después de esa migración, pasar un
laboratorio a responsable inscripto es solo declarar `regimen` en su tenant.
Si faltan, la emisión muestra error y no llama a AFIP.

## Ficha del laboratorio en `entorno`

En Parámetros del Sistema, solapa **Configuración Arca**, se carga la ficha fiscal
del laboratorio (un CUIT para todos):

| Columna | Uso |
|---|---|
| `afipCuit`, `afipRazonSocial`, `afipDomicComerc` | Identidad del emisor |
| `afipCondIva`, `afipIngresosBrutos`, `afipInicioActiv` | Datos que salen en el comprobante |
| `afipPtoVta`, `afipConcepto` | Punto de venta y concepto AFIP (1 productos, 2 servicios, 3 ambos) |
| `afipKey`, `afipCrt`, `afipCrtVencimiento` | Nombre del archivo y vencimiento. Los archivos van en `afipSE/cert/entorno/` |
| `afipFormatoImpresion` | A4 o térmica, en la misma solapa |

Entran con `php artisan migrate` (`2026_10_01_000002_add_entorno_configuracion_arca`).
SQL manual: `database/sql/entorno_configuracion_arca.sql`.

El permiso para emitir (`usuarios.permisoAfip`) sigue en cada usuario. En responsable inscripto el formulario de usuarios muestra solo ese checkbox: CUIT, punto de venta y certificados no se editan ahí. En monotributo esos campos siguen en el usuario. La emisión todavía lee CUIT, punto de venta y certificados del usuario. Esta ficha es el lugar donde se cargan; no reemplaza al usuario hasta que se conecte.

## Qué no cambia en monotributo

La letra C, el CAE, el QR, los certificados y el PDF de un solo importe. En modo `paciente` también aparece la elección cliente/paciente; si no se cambia, el protocolo sigue saliendo al paciente y el pago global al cliente. `usuarios.CondicionIVAReceptorId` sigue siendo la condición que se informa en la Factura C.

## Qué no hacer

1. No emitir Factura C con IVA, ni Factura A/B con `ImpIVA = 0`, salvo alícuota 0 configurada a propósito.
2. No usar `usuarios.CbteTipo` para forzar la letra cuando el régimen es responsable inscripto.
3. No anular una Factura A con la nota de crédito C del monotributo: la nota tiene que ser del mismo tipo (A→3, B→8).
4. No declarar `responsable_inscripto` sin haber corrido `php artisan migrate` en esa base. La alícuota y `precio_incluye_iva` se confirman con el contador y se escriben en el tenant.
5. No recalcular el IVA de una nota de crédito: se copian neto, IVA y alícuota de la factura.

## Archivos clave

| Pieza | Ruta |
|---|---|
| Config | `FacturacionAfipConfig`, `config/tenant.php` → `facturacion_afip` |
| Emisión | `FacturacionAfipService`, `AfipWsfeEmision` |
| IVA / letra | `FacturacionIva` |
| Pantalla | `ComprobantesAfipIndex` |
| PDF | `CompAfipA4Tcpdf`, `CompAfipTermica80Tcpdf` |
| Migración | `database/migrations/2026_10_01_000001_add_compafip_iva_responsable_inscripto.php` |
