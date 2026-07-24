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
| Tesorería (variantes por tenant) | [tesoreria.md](tesoreria.md) |

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
