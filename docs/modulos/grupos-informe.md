# Módulo: Gestión de grupos (informe)

En UI: **Gestión de Grupos** (Menú de Administración → Parámetros Determinaciones).  
Código: `app/Livewire/Abm/Grupos/`.  
Ruta: `admin/grupos` · `permiso:8` (PARAMETROS).

El PDF del informe usa estos grupos como títulos de sección.

## Propósito

ABM de `grupos`: nombre, orden de impresión y si el PDF muestra el encabezado
**VALORES DE REFERENCIA** en esa sección.

## Tablas y campos críticos

| Tabla | Campo | Rol |
|-------|--------|-----|
| `grupos` | `nombreGrupo`, `orden` | Título y orden en el informe |
| `grupos` | `mostrarReferencias` | `1` = imprimir encabezado; `0` = no. Columna aditiva. |

SQL: `database/sql/grupos_mostrar_referencias.sql`.  
Si falta la columna, el formulario **no guarda** (error visible). El PDF, si aún
no está la columna, trata el flag como `1` (mostrar), salvo Observaciones.

## Encabezado VALORES DE REFERENCIA (PDF)

Regla en `InformeGrupoReferencias`:

1. Si el nombre del grupo es **OBSERVACIONES** (mayúsculas/minúsculas igual):
   **nunca** se imprime el encabezado, aunque `mostrarReferencias = 1`.
2. En cualquier otro grupo: se imprime solo si `mostrarReferencias = 1`.

Eso no oculta la columna de referencias de cada fila (`itemsinforme.ref*`);
solo el rótulo a la derecha bajo el título del grupo.

Al aplicar la migración, **OBSERVACIONES** e **INFORME DE ECOGRAFÍA** quedan en
`0` (Ecografía dejaba de estar hardcodeada y pasa al flag). El resto queda en `1`.

## Actores y permisos

Staff con `PermisosIaCatalog::PARAMETROS` (8).

## Archivos clave

| Pieza | Ruta |
|-------|------|
| Listado / alta-edición | `GrupoIndex` / `GrupoForm` |
| Modelo | `app/Models/Grupo.php` |
| PDF | `InformePacienteTcpdf` + `InformeGrupoReferencias` |
| Consulta | `InformePacienteConsulta` |

## Qué no hacer

- No volver a hardcodear nombres de grupo salvo **OBSERVACIONES**.
- No guardar `mostrarReferencias` en otra columna si falta en BD.
- No filtrar renglones del PDF con este flag: solo el encabezado.

## Checklist al modificar

- [ ] ¿Observaciones sigue sin encabezado aunque el flag sea 1?
- [ ] ¿El ABM persiste en `grupos.mostrarReferencias`?
- [ ] ¿Columna ausente → error visible, sin éxito silencioso?
- [ ] Tras CSS: `npm run build` + `public/build/` en el despliegue.
