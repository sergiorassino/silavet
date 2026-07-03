# Identidad visual y sistema UI

La fuente operativa de verdad para la estética del proyecto será:

- `.cursor/rules/ui-front-vl.mdc`
- `resources/css/app.css`

Esta documentación resume la identidad y debe mantenerse alineada con esa regla.

---

## 1. Paleta institucional (propuesta inicial)

Hasta definir la identidad del laboratorio, se propone una paleta en la línea
de SILAVET (veterinaria / ciencia), distinta de Sistemas Escolares pero con la
misma estructura de tokens Tailwind:

| Color | Hex | Uso |
| --- | --- | --- |
| Teal (primario) | `#2D6A6A` | Acciones, activos, links de énfasis |
| Charcoal | `#2C3333` | Texto fuerte, sidebar |
| Sage | `#5E8A8A` | Apoyos visuales y estados secundarios |
| Mist | `#D4E4E4` | Fondos suaves, bordes, hover |
| White | `#FFFFFF` | Superficies principales |

En Tailwind (cuando exista `app.css`):

- `primary-*` para Teal.
- `neutral-*` para Charcoal.
- `accent-*` para Mist.

> **Pendiente Etapa 1:** incorporar logo y paleta definitivos del laboratorio en
> `public/img/` y actualizar esta tabla.

---

## 2. Logos

Ubicación prevista: `public/img/`.

| Archivo | Uso recomendado |
| --- | --- |
| `logo-icon.png` | Icono compacto, sidebar colapsado |
| `logo-main.png` | Logo principal para login y dashboard |
| `logo-horizontal.png` | Variante horizontal para headers |
| `favicon-vl-light.svg` | Favicon (tema claro del navegador) |
| `favicon-vl-dark.svg` | Favicon (tema oscuro del navegador) |

Regla de uso:

- Preferir `labLogoUrl()` cuando exista logo dinámico desde `entorno.logo`.
- Usar `asset('img/logo-main.png')` como fallback principal.

---

## 3. Componentes visuales (convención `vl-*`)

Los componentes reutilizables vivirán en `resources/css/app.css`, con prefijo
**`vl-`** (vet lab), análogo al prefijo **`se-`** de Sistemas Escolares:

- Autenticación: `vl-auth-card`, `vl-auth-label`, `vl-auth-input`, `vl-auth-btn`.
- Dashboard: `vl-dash-access`, `vl-dash-access-icon`.
- Pantallas internas/ABM: `vl-page`, `vl-hero`, `vl-card`, `vl-toolbar`, `vl-pill`.
- Formularios de tabs: `vl-form-tabs`, `vl-form-tab`, `vl-form-tab-active`.
- Protocolos: `vl-protocolo-form` para formularios largos de recepción.
- Grillas compactas: `gf-*` solo cuando la pantalla requiera formato planilla.

---

## 4. Criterio general

- El sistema debe sentirse profesional, claro y orientado a operación de laboratorio.
- Login y dashboard muestran la marca con presencia.
- Páginas internas priorizan lectura, búsqueda de protocolos, tablas de resultados
  y formularios ergonómicos.
- Informes PDF deben respetar `entorno.colorInforme`, firmas y textos de pie.
- Mobile usable en el portal de clientes (consulta de protocolos).
- Para nuevas pantallas, seguir primero `.cursor/rules/ui-front-vl.mdc`.
