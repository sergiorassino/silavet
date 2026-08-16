# Listas de precios — variantes por laboratorio

> Cómo se elige la lista 1 / 2 / 3 (`tipodeterminaciones.precio`, `precio2`, `precio3`)
> al cargar determinaciones en un protocolo.
> Complementa [07-versionado-de-modulos-por-tenant.md](07-versionado-de-modulos-por-tenant.md) §3.3.
> Carga de filas: [modulos/carga-determinaciones-paciente.md](modulos/carga-determinaciones-paciente.md).
> Catálogo de tipos: [modulos/gestion-determinaciones.md](modulos/gestion-determinaciones.md).

---

## 1. Resumen de variantes

| Clave `lista` | Quién elige la lista | Labs típicos | Estado |
|---------------|----------------------|--------------|--------|
| `cliente` | Cada cliente veterinario (`clientes.listaPreciosCliente`) | **Default** (epizoolab y el resto) | **Implementada** |
| `paciente` | Cada protocolo (`pacientes.listaPreciosPaciente`) | **laboratoriosiv** | **Implementada** |

La clave legacy `fija_1` se interpreta como `cliente`. No hace falta un modo “siempre lista 1”: los clientes nuevos y la columna nacen en **lista 1**.

Config:

```php
// config/tenant.php (default)
'precios' => [
    'lista' => 'cliente',
],

// config/tenants/laboratoriosiv.php
'precios' => [
    'lista' => 'paciente',
],
```

Helper: `App\Support\Precios\ListaPreciosConfig`
(`esPorPaciente()` / `esPorCliente()` / `nroParaPaciente()`).

Resolver: `PrecioDeterminacionResolver::resolverPrecioListaParaPaciente()`.

---

## 2. Variante `cliente` (default)

- Campo **Lista de precios** en el ABM de clientes. Default al alta y al crear la columna: **1** (`tipodeterminaciones.precio`).
- Columna **L/P** en el listado de clientes.
- Persistencia: `clientes.listaPreciosCliente` (TINYINT 1–3, default 1).
- Al cargar determinaciones del protocolo, se usa la lista del **cliente** al que pertenece el paciente.
- Estimación de costos (listado) también usa la lista del cliente seleccionado.
- Si nadie cambia la lista, el comportamiento de precios es el de lista 1 para todos.

---

## 3. Variante `paciente` (laboratoriosiv)

- Primer campo del ABM de protocolo: **Lista de precios** (Lista 1 / 2 / 3). Default al alta: **1**.
- Columna indicativa **L/P** en Gestión de pacientes (entre Tutor y Especie), con etiqueta `L.1` / `L.2` / `L.3`.
- Persistencia: `pacientes.listaPreciosPaciente` (1, 2 o 3). En laboratoriosiv la columna **ya existía** (legacy ScriptCase); no se modifica el tipo si ya está.
- Al cargar determinaciones, el neto se toma de `precio` / `precio2` / `precio3` según esa lista.
- Cambiar la lista de un protocolo **no** recalcula filas ya guardadas en `determinaciones`; solo afecta altas posteriores.

---

## 4. Columnas y valores

| Tabla | Columna | Valores | Default |
|-------|---------|---------|---------|
| `tipodeterminaciones` | `precio`, `precio2`, `precio3` | Importes | 0 |
| `pacientes` | `listaPreciosPaciente` | 1, 2, 3 (también lee legacy `L.1`) | 1 |
| `clientes` | `listaPreciosCliente` | 1, 2, 3 | 1 |

Si falta `precio2`/`precio3` en el registro del tipo, el resolver usa lista 1.
Si falta la columna de alcance, el guardado **falla con aviso visible** (no hay éxito silencioso). SQL: `database/sql/listas_precios_paciente_cliente.sql`.

---

## 5. Qué no hacer

1. No hardcodear `if (tenantSlug() === 'laboratoriosiv')` en Blade: usar `ListaPreciosConfig`.
2. No redirigir el valor de lista a otra columna (`destino`, `descuento`, etc.).
3. No recalcular `determinaciones.neto` al cambiar la lista salvo pedido explícito.
4. No reintroducir un modo `fija_1`: el default de cliente = lista 1 cubre ese caso.
