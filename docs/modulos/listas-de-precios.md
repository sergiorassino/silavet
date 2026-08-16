# Módulo: Listas de precios (alcance por tenant)

En UI: selector en ABM de protocolos y/o clientes, columna **L/P**, y neto al cargar
determinaciones.  
Código: `tenant.precios.lista` + `ListaPreciosConfig` + `PrecioDeterminacionResolver`.

## Propósito

Elegir de cuál de las tres listas del catálogo (`tipodeterminaciones.precio` /
`precio2` / `precio3`) se toma el precio de lista (neto) al dar de alta una
determinación en un protocolo.

## Modalidades / variantes

Ver [../12-listas-de-precios-por-tenant.md](../12-listas-de-precios-por-tenant.md).

| Clave | Efecto |
|-------|--------|
| `cliente` (default) | Lista por cliente veterinario (default 1) |
| `paciente` | Lista por protocolo (laboratoriosiv) |

## Actores y permisos

Mismos que los ABM y la carga de determinaciones (permisos 2, 3 y clientes).

## Tablas y campos críticos

| Tabla | Columna | Quién escribe |
|-------|---------|---------------|
| `tipodeterminaciones` | `precio`, `precio2`, `precio3` | ABM Gestión Determinaciones |
| `pacientes` | `listaPreciosPaciente` | ABM protocolo (solo modo `paciente`) |
| `clientes` | `listaPreciosCliente` | ABM clientes (solo modo `cliente`) |
| `determinaciones` | `neto` / `precio` | Carga de determinaciones (materializa el importe elegido) |

## Flujo principal

1. Tenant declara `precios.lista` (`cliente` por defecto; `paciente` en laboratoriosiv).
2. Si `paciente`: el usuario elige lista al alta/edición del protocolo; el listado muestra **L/P**.
3. Si `cliente` (default): el usuario elige lista en el cliente; default 1.
4. Al elegir un tipo en el protocolo, el resolver toma `precio` / `precio2` / `precio3`.

## Fuente de verdad

El catálogo de importes es `tipodeterminaciones`. El alcance (quién elige 1/2/3)
es config de tenant + columna aditiva. El importe ya cargado en `determinaciones`
no se vuelve a leer del catálogo.

## Archivos clave

| Pieza | Ruta |
|-------|------|
| Config helper | `app/Support/Precios/ListaPreciosConfig.php` |
| Resolver | `app/Support/Precios/PrecioDeterminacionResolver.php` |
| ABM protocolo | `PacienteForm` + `paciente-form.blade.php` |
| Listado protocolos | `PacienteIndex` + `paciente-index.blade.php` |
| ABM clientes | `ClienteForm` / `ClienteIndex` |
| Carga al protocolo | `PacienteDeterminaciones` |
| SQL | `database/sql/listas_precios_paciente_cliente.sql` |

## Qué no hacer / reglas de negocio

1. No reintroducir un modo “siempre lista 1”: el default del cliente es lista 1.
2. Columna ausente → error visible, no éxito parcial.
3. Default 1 al crear protocolo, cliente o la columna.

## Checklist al modificar

- [ ] ¿El default `cliente` muestra selector y columna L/P en clientes, con lista 1 al alta?
- [ ] ¿laboratoriosiv (`paciente`) persiste `pacientes.listaPreciosPaciente`?
- [ ] ¿Modo `cliente` lee `clientes.listaPreciosCliente` al cargar determinaciones?
- [ ] ¿Guard `Schema::hasColumn` con error visible si falta la columna?
