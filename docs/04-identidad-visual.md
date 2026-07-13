# Identidad visual y sistema UI

La fuente operativa de verdad para la estética del proyecto es:

- `.cursor/rules/ui-front-vl.mdc`
- `resources/css/app.css`

Esta documentación resume la identidad y debe mantenerse alineada con esa regla.

---

## 1. Paleta institucional

Misma **estructura** que Sistemas Escolares, con **celeste intenso** como color de marca:

| Color | Hex | Uso |
| --- | --- | --- |
| Celeste intenso | `#0EA5E9` | Primario, acciones, activos, links de énfasis |
| Celeste oscuro | `#0284C7` | Hover, sidebar, hero |
| Jet | `#333333` | Texto fuerte, cierre de degradés |
| Celeste claro | `#BAE6FD` | Fondos suaves, bordes, inputs auth |
| Blanco | `#FFFFFF` | Superficies principales |

En Tailwind (`resources/css/app.css`):

- `primary-*` para celeste intenso.
- `neutral-*` para Jet / grises.
- `accent-*` para celestes claros.

---

## 2. Logos

Ubicación prevista: `public/img/`.

| Archivo | Uso recomendado |
| --- | --- |
| `logo-icon.png` | Icono compacto, sidebar colapsado |
| `logo-main.png` | Logo principal para login y dashboard |
| `logo-horizontal.png` | Variante horizontal para headers |

Hasta disponer de logos, se usa monograma **SV** en login y sidebar.

Regla de uso:

- Preferir logo dinámico desde `entorno.logo` cuando exista el helper.
- Fallback: `asset('img/logo-main.png')`.

---

## 3. Componentes visuales (convención `vl-*`)

Análogo al prefijo **`se-`** de Sistemas Escolares:

- Autenticación: `vl-auth-card`, `vl-auth-label`, `vl-auth-input`, `vl-auth-btn`.
- Layout: `vl-sidebar--bosque`, `vl-main`, `vl-sidebar-link`.
- Dashboard: `vl-dash-access`, `vl-dash-access-icon`.
- Pantallas internas/ABM: `vl-page`, `vl-hero`, `vl-eyebrow`, `vl-card`, `vl-toolbar`, `vl-pill`.
- Grillas compactas: `gf-*` solo cuando la pantalla requiera formato planilla.

Layout staff unificado: `resources/views/layouts/staff.blade.php`.

---

## 4. Criterio general

- Misma sensación profesional y operativa que Sistemas Escolares.
- Login split-screen con panel editorial celeste + formulario blanco.
- Hero con degradé en dashboards y cabeceras de ABM.
- Sidebar “bosque” con degradé celeste → gris oscuro.
- Portal clientes: mobile-first cuando se implemente.
- Informes PDF respetan `entorno` (colores legacy del laboratorio).

Para nuevas pantallas, seguir `.cursor/rules/ui-front-vl.mdc`.
