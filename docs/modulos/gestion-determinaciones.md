# Módulo: Gestión de determinaciones (ABM)

En UI: **Gestión Determinaciones** (Menú de Administración / Parámetros).  
Código / BD: catálogo `tipodeterminaciones` (tipos de análisis, precios y centro predeterminado).

## Propósito

Editar en grilla el catálogo de tipos: orden, nombre, listas de precio y, según el tenant,
perfil y centro de derivación predeterminado. No carga determinaciones a un protocolo.

## Modalidades / variantes

| Variante | Config | Efecto |
|----------|--------|--------|
| Derivación **Sí/No** | `tenant.tipodeterminaciones.derivacion` = `si_no` | Select No/Sí; persiste `0`/`1` en `tipodeterminaciones.destino` |
| Derivación **catálogo** | `derivacion` = `catalogo` | Select de centros; persiste FK en `tipodeterminaciones.derivacion` (`0` = sin derivar) |
| Columna perfil | `mostrar_columna_perfil` (alqu) | Select No/Sí → `perfil` |
| Precio 2 / 3 | Columnas aditivas `precio2` / `precio3` | Si faltan: aviso ámbar; no se muestran. El alcance (quién usa cada lista) es `tenant.precios.lista`: ver [../12-listas-de-precios-por-tenant.md](../12-listas-de-precios-por-tenant.md) |

Tenants con `catalogo` hoy: **alqu**, **neolab**, **laboratoriosiv**, **labvetciudad**, **civetfranca**, **epizoolab**.

## Actores y permisos

Staff con `PermisosIaCatalog::DETERMINACIONES` (**2**). Ruta `abm.tipodeterminaciones.index`.

## Tablas y campos críticos

| Tabla | Rol |
|-------|-----|
| `tipodeterminaciones` | Fuente de verdad del catálogo |
| `derivaciones` | Centros (modo catálogo) |

**Columna del centro predeterminado (modo catálogo): `derivacion`.**  
El sistema anterior (ScriptCase) guardó ahí el `idDerivaciones` (p. ej. Laboratorio SIV:
8 RAPELA, 9 EPIZOOLAB, 10 DUCHENE, 11 LAB. SIV).

**No usar `destino` como FK de centro.** En las BD legacy `destino` es un código 0–3 de
otra semántica (plantilla común a varios laboratorios). Leer `destino` en modo catálogo
hace que los centros predeterminados “no aparezcan”.

Si falta `tipodeterminaciones.derivacion`: aviso visible y no se persiste el centro
(no redirigir el valor a `destino`). SQL: `database/sql/tipodeterminaciones_derivacion.sql`.

## Flujo principal

1. Carga todas las filas a memoria (`filas`); filtro local por orden/nombre.
2. Orden de la grilla: por defecto alfabético español por nombre (`asc`): á/é/í/ó/ú
   con su vocal, ñ después de n. Clic en cabeceras **Orden** y **Nombre de la
   determinación** alterna `asc`/`desc` de esa columna.
3. Edición inline con guardado automático al salir del campo (blur) o al
   cambiar un select; solo acción de fila: eliminar. Alta con valores en 0.
4. Eliminar bloqueado si hay filas en `determinaciones` con ese tipo.

## Fuente de verdad

| Dato | Columna |
|------|---------|
| Centro predeterminado (catálogo) | `tipodeterminaciones.derivacion` |
| Sí/No (tenants `si_no`) | `tipodeterminaciones.destino` |
| Listas de precio | `precio`, `precio2`, `precio3` |

Este ABM **no** escribe `determinaciones.idDerivaciones`. Al **elegir el tipo** en el protocolo
se preselecciona `derivacion` (modo catálogo); el select de esa pantalla sigue editable
(ver [carga-determinaciones-paciente.md](carga-determinaciones-paciente.md)).

## Archivos clave

| Pieza | Ruta |
|-------|------|
| Componente | `app/Livewire/Abm/Tipodeterminaciones/TipodeterminacionIndex.php` |
| Vista | `resources/views/livewire/abm/tipodeterminaciones/tipodeterminacion-index.blade.php` |
| Config de grilla | `app/Support/Tipodeterminaciones/TipodeterminacionesGridConfig.php` |
| Orden alfabético español | `app/Support/OrdenAlfabeticoEspanol.php` |
| Modelo | `app/Models/Tipodeterminacion.php` |

## Qué no hacer / reglas de negocio

1. En modo catálogo **no** leer ni escribir el centro en `destino`.
2. No tratar `destino` 1/2/3 como id de centro.
3. Al elegir el tipo en el protocolo **sí** se preselecciona `derivacion` (modo catálogo);
   el select permanece editable. No copiar `destino`.
4. Diálogos: `vl-swal-*`, no `alert`/`confirm`.
5. No cambiar el default alfabético por nombre salvo pedido explícito.

## Checklist al modificar

- [ ] ¿Modo `si_no` sigue en `destino` y modo `catalogo` en `derivacion`?
- [ ] ¿Laboratorio SIV muestra RAPELA / EPIZOOLAB / DUCHENE / LAB. SIV según `derivacion`?
- [ ] ¿Guard `Schema::hasColumn('tipodeterminaciones', 'derivacion')` con error visible si falta?
- [ ] ¿Permiso 2?
- [ ] ¿Default alfabético español por nombre (acentos con su vocal, ñ tras n), y cabeceras Orden/Nombre reordenan?
- [ ] ¿Inputs guardan al blur y selects al change, sin botones guardar/descartar?
