# Documentación por módulo

Docs cortos (1–2 páginas) que fijan **cómo debe funcionar** cada módulo: actores,
tablas, flujos y trampas. Sirven para cambios futuros sin reexplicar el diseño
oralmente.

**Obligatorio para asistentes:** antes de modificar un módulo, leer su archivo
en esta carpeta (si existe) y respetar las reglas de negocio ahí listadas.

## Índice

| Módulo | Archivo |
|--------|---------|
| Carga de determinaciones al paciente (protocolo) | [carga-determinaciones-paciente.md](carga-determinaciones-paciente.md) |
| Gestión de determinaciones (ABM tipos / precios / centro) | [gestion-determinaciones.md](gestion-determinaciones.md) |
| Listas de precios (alcance lista 1/2/3 por tenant) | [listas-de-precios.md](listas-de-precios.md) |
| Carga de resultados (informe + hemograma auto) | [carga-resultados.md](carga-resultados.md) |
| Valores de referencia (rangovalores) | [valores-referencia.md](valores-referencia.md) |
| Stock de reactivos e insumos | [stock-reactivos.md](stock-reactivos.md) |
| Tesorería (variantes por tenant) | [tesoreria.md](tesoreria.md) |
| Cuenta corriente de clientes | [cuenta-corriente.md](cuenta-corriente.md) |
| Resumen cliente entre fechas (PDF) | [resumen-cliente-entre-fechas.md](resumen-cliente-entre-fechas.md) |
| Determinaciones por cliente (listado + Excel) | [determinaciones-por-cliente.md](determinaciones-por-cliente.md) |
| Clientes resumen mensual (grid + PDF + Excel) | [clientes-resumen-mensual.md](clientes-resumen-mensual.md) |
| Detalle de determinaciones (autogestión cliente) | [autogestion-detalle-determinaciones.md](autogestion-detalle-determinaciones.md) |
| Hoja de ruta (hemograma) | [hoja-ruta-hemograma.md](hoja-ruta-hemograma.md) |

## Plantilla

```markdown
# Módulo: …
## Propósito
## Modalidades / variantes
## Actores y permisos
## Tablas y campos críticos
## Flujo principal
## Fuente de verdad
## Archivos clave
## Qué no hacer / reglas de negocio
## Checklist al modificar
```

Convenciones generales: [../05-preferencias-y-convenciones.md](../05-preferencias-y-convenciones.md).  
Tenants / flags: [../07-versionado-de-modulos-por-tenant.md](../07-versionado-de-modulos-por-tenant.md).  
Seguridad: [../06-reglas-de-seguridad.md](../06-reglas-de-seguridad.md).
