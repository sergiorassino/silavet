# Número de protocolo — variantes por laboratorio

> Cómo se calcula el próximo `nombreProtocolo` al dar de alta un caso en `pacientes`.
> Antes de modificar la generación o agregar un tenant, leer este documento.
> Complementa [07-versionado-de-modulos-por-tenant.md](07-versionado-de-modulos-por-tenant.md) §3.3.

---

## 1. Conceptos básicos

| Concepto | Detalle |
|----------|---------|
| **Campo en BD** | `pacientes.nombreProtocolo` (string, hasta 50 caracteres) |
| **Cuándo se asigna** | Solo al **alta** de un protocolo. En edición el número es fijo. |
| **Zona horaria** | `America/Argentina/Buenos_Aires` para prefijos con fecha |
| **Vista previa** | El formulario muestra un número **provisional**; puede cambiar si otro usuario guarda antes. |
| **Reserva definitiva** | Al guardar, bajo lock exclusivo + transacción, se confirma el siguiente libre. |
| **Config por tenant** | `config('tenant.protocolos.implementacion')` + overrides en `config/tenants/{slug}.php` |

En la UI y la documentación interna, **protocolo** = registro analítico en `pacientes`
(no confundir con paciente animal ni con humano).

---

## 2. Resumen de variantes

Cada laboratorio elige **una** implementación. Los nombres de clave son estables
y no deben renombrarse una vez en producción.

| Clave `implementacion` | Formato | Longitud típica | Estado | Origen legacy |
|------------------------|---------|-----------------|--------|---------------|
| `fecha_diaria` | `YYMMDD` + secuencia diaria | 9 (`260706001`) | **Implementada** | ScriptCase opción 2 |
| `dual_corto_largo` | Largo: `YYMMDDNNN` · Corto: `C` + 9 dígitos | 9 o 10 | **Implementada** | ScriptCase opción 3 |
| `anual_consecutivo` | `AA` + secuencia anual | 7 (`2600001`) | **Implementada** | ScriptCase opción 1 |

**Tenants configurados hoy:**

| Slug | `implementacion` | Archivo |
|------|------------------|---------|
| *(default)* | `fecha_diaria` | `config/tenant.php` |
| `neolab` | `fecha_diaria` | `config/tenants/neolab.php` |
| `labvetciudad` | `anual_consecutivo` | `config/tenants/labvetciudad.php` |
| `civetfranca` | `dual_corto_largo` | `config/tenants/civetfranca.php` |

---

## 3. Variante `fecha_diaria`

### Formato

```
YYMMDD + NNN
│      └── secuencia del día (3 dígitos, desde 001)
└── año/mes/día de la fecha del protocolo (fechhoy)
```

**Ejemplo:** fecha `2026-07-06` → `260706001`, `260706002`, …

### Reglas de cálculo

1. Prefijo = `fechhoy` formateado como `ymd` (ej. `260706`).
2. Buscar el máximo `nombreProtocolo` que:
   - comience con ese prefijo, y
   - tenga longitud exacta `6 + longitud_secuencia` (por defecto 9).
3. Si no hay registros ese día → secuencia `001`.
4. Si hay → tomar los últimos 3 dígitos, sumar 1, rellenar con ceros.

> **Nota:** esta variante filtra por **prefijo del número**, no por columna `fechhoy`.
> Es coherente mientras el prefijo coincida con la fecha del caso.

### Configuración

```php
// config/tenant.php (defaults)
'protocolos' => [
    'implementacion' => 'fecha_diaria',
    'fecha_diaria' => [
        'longitud_secuencia' => 3,
    ],
],
```

### UI

No requiere campos extra. Solo fecha + vista previa del protocolo.

### Código

- Generador: `App\Support\ProtocoloNumero\Generators\FechaDiariaGenerator`
- Lock MySQL: `vl_protocolo_{YYMMDD}`

---

## 4. Variante `dual_corto_largo`

Un mismo laboratorio puede usar **dos formatos** según el tipo elegido al crear el caso.
Conviven en la misma tabla; la distinción es por forma del número y, en el largo, por `fechhoy`.

### 4.1 Protocolo largo (`tipo = L`)

**Formato:** igual que `fecha_diaria` → `YYMMDDNNN`

**Ejemplo:** `260706042`

**Reglas:**

1. Prefijo = `ymd` de `fechhoy`.
2. Buscar máximo entre registros con:
   - `fechhoy` = fecha del caso,
   - `nombreProtocolo NOT LIKE 'C%'`,
   - longitud exacta 9.
3. Secuencia desde `001` por día.

### 4.2 Protocolo corto (`tipo = C`)

**Formato:** `C` + 9 dígitos numéricos

**Ejemplo:** `C000000101`, `C000000102`, …

**Reglas:**

1. Prefijo fijo `C` (configurable).
2. Buscar `MAX` numérico de la parte después de `C` en todos los `nombreProtocolo LIKE 'C%'`.
3. Si no hay ninguno → arrancar en `101` (configurable).
4. Rellenar la parte numérica a 9 dígitos.

### Configuración

```php
'dual_corto_largo' => [
    'corto_prefijo'       => 'C',
    'corto_inicio'        => 101,
    'corto_longitud'      => 9,
    'largo_secuencia_len' => 3,
    'tipo_default'        => 'L',   // valor inicial del selector en el formulario
],
```

### UI

Si `implementacion === 'dual_corto_largo'`, el formulario de **Nuevo protocolo** muestra:

- **Tipo de protocolo:** Protocolo largo (`L`) / Protocolo corto (`C`).
- Al cambiar tipo o fecha se recalcula la vista previa.

El tipo **no se persiste** en BD: queda codificado en el número generado.

### Código

- Generador: `App\Support\ProtocoloNumero\Generators\DualCortoLargoGenerator`
- Locks MySQL:
  - largo: `vl_protocolo_largo_{YYMMDD}`
  - corto: `vl_protocolo_corto`

---

## 5. Variante `anual_consecutivo`

Migrada desde ScriptCase (opción 1). Usada por **labvetciudad**.

### Formato

```
AA + NNNNN
│    └── secuencia anual (5 dígitos, desde 00001)
└── año en 2 dígitos según fechhoy (ej. 26)
```

**Ejemplo:** `2600001`, `2600042`, `2700001` (al cambiar de año)

### Reglas de cálculo

1. Prefijo `AA` = año de `fechhoy` formateado como `y` (zona `America/Argentina/Buenos_Aires`).
2. Buscar el máximo `nombreProtocolo` que:
   - comience con ese prefijo de año, y
   - tenga longitud exacta `2 + longitud_secuencia` (por defecto 7).
3. Si no hay registros ese año → secuencia `00001`.
4. Si hay → tomar los últimos 5 dígitos, sumar 1, rellenar con ceros.

> **Mejora respecto al legacy:** el ScriptCase original usaba `MAX(nombreProtocolo)`
> sin filtrar por año; aquí la secuencia es **por prefijo anual**, de modo que al
> pasar de `26xxxx` a `27xxxx` la numeración reinicia correctamente.

### Configuración

```php
'anual_consecutivo' => [
    'longitud_secuencia' => 5,
],
```

### UI

No requiere campos extra. Solo fecha + vista previa del protocolo.

### Código

- Generador: `App\Support\ProtocoloNumero\Generators\AnualConsecutivoGenerator`
- Lock MySQL: `vl_protocolo_anual_{AA}`

---

## 6. Concurrencia y consistencia

Varios usuarios pueden crear protocolos a la vez. Para evitar duplicados:

1. **Lock MySQL** (`GET_LOCK` / `RELEASE_LOCK`) por clave de secuencia (ver cada variante).
2. **Transacción** alrededor del cálculo + insert.
3. **Re-chequeo:** si el número calculado ya existe, incrementar hasta encontrar uno libre.

En entornos sin MySQL (tests con SQLite), el lock se degrada a transacción simple.

### API pública

```php
use App\Support\ProtocoloNumero;
use App\Support\ProtocoloNumero\ProtocoloNumeroContext;

// Vista previa (sin reservar)
ProtocoloNumero::previsualizarParaFecha('2026-07-06');
ProtocoloNumero::previsualizarParaFecha('2026-07-06', 'C'); // solo dual

// Reserva al guardar
ProtocoloNumero::withSiguienteReservado($fecha, function (string $numero) {
    // Paciente::create([..., 'nombreProtocolo' => $numero]);
}, $tipoOpcional);

// Contexto explícito
$ctx = ProtocoloNumeroContext::fromFecha('2026-07-06', 'L');
ProtocoloNumero::previsualizar($ctx);
ProtocoloNumero::withContextoReservado($ctx, $callback);
```

**Punto de uso actual:** `App\Livewire\Protocolos\PacienteForm` (alta de protocolos).

---

## 7. Configuración por tenant

Patrón estándar del proyecto: defaults en `config/tenant.php`, solo diferencias en
`config/tenants/{slug}.php`.

```php
// config/tenants/labvetciudad.php — ejemplo real
return [
    'protocolos' => [
        'implementacion' => 'anual_consecutivo',
    ],
];
```

Tras cambiar config en producción: `php artisan config:clear` o `config:cache`.

**Regla:** un tenant = una BD propia. Las secuencias son independientes por
instalación; no hay `tenant_id` en `pacientes`.

---

## 8. Agregar una nueva variante

Checklist obligatorio para no romper otros laboratorios:

1. **Documentar** la variante en este archivo (formato, reglas, ejemplos, locks).
2. **Crear generador** en `app/Support/ProtocoloNumero/Generators/{Nombre}Generator.php`
   extendiendo `AbstractProtocoloNumeroGenerator`.
3. **Implementar** `ProtocoloNumeroGenerator`:
   - `lockKey(ProtocoloNumeroContext $ctx)`
   - `calcularSiguiente(ProtocoloNumeroContext $ctx)`
   - `incrementar(string $numero, ProtocoloNumeroContext $ctx)`
4. **Registrar** la clave en `ProtocoloNumeroRegistry::MAP`.
5. **Agregar defaults** en `config/tenant.php` bajo `protocolos.{clave}`.
6. **Tests** en `tests/Unit/ProtocoloNumeroTest.php` (formato + casos borde).
7. **UI:** solo si la variante requiere input extra (como `dual_corto_largo`);
   usar `ProtocoloNumero::usaTipoProtocolo()` o un helper equivalente, **nunca**
   `if (tenant === 'x')` en Blade.
8. **Asignar** la clave en `config/tenants/{slug}.php` del laboratorio que la use.

### Estructura de archivos

```
app/Support/
├── ProtocoloNumero.php                          # Facade (API estable)
└── ProtocoloNumero/
    ├── ProtocoloNumeroContext.php               # fecha + parámetros (tipo, etc.)
    ├── ProtocoloNumeroGenerator.php             # Interfaz
    ├── ProtocoloNumeroRegistry.php              # Mapa implementacion → clase
    ├── AbstractProtocoloNumeroGenerator.php       # Lock + transacción + anti-duplicado
    ├── Concerns/ReservaConLock.php
    └── Generators/
        ├── AnualConsecutivoGenerator.php
        ├── FechaDiariaGenerator.php
        └── DualCortoLargoGenerator.php
```

---

## 9. Tests

`tests/Unit/ProtocoloNumeroTest.php` valida:

- formato de cada implementación activa;
- flag `ProtocoloNumero::usaTipoProtocolo()` según config.

Al agregar variantes, incluir al menos:

- primer número del período (día / año / global);
- incremento cuando ya existen registros;
- formato exacto (regex + longitud).

---

## 10. Decisiones de diseño

| Decisión | Motivo |
|----------|--------|
| Registry + config, no `if` por slug | Mismo criterio que informes y resto de módulos multi-tenant |
| Clase por variante | Fácil agregar formatos sin tocar las existentes |
| Preview sin lock | Mejor UX; el número definitivo se fija al guardar |
| Lock por secuencia, no global | Dos días, años o tipos (largo/corto) no se bloquean entre sí |

---

## 11. Referencias

- Modelo de datos: `pacientes.nombreProtocolo` en [02-modelo-de-datos.md](02-modelo-de-datos.md)
- Personalización por tenant: [07-versionado-de-modulos-por-tenant.md](07-versionado-de-modulos-por-tenant.md)
- Seguridad (números predecibles en URLs): [06-reglas-de-seguridad.md](06-reglas-de-seguridad.md) §10
- Formulario de alta: `app/Livewire/Protocolos/PacienteForm.php`
