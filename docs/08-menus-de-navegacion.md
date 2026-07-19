# Menús de navegación (terminología oficial)

Este documento fija los **nombres** que usamos en el equipo para los portales con sidebar.
Evita confusiones entre roles operativos, administración y clientes veterinarios.

---

## Resumen

| Nombre oficial | Qué es | Layout Blade | Login / guard |
|----------------|--------|--------------|---------------|
| **Menú de Laboratorio** | Operaciones analíticas: protocolos, resultados, informes, parametrización operativa | `layouts.staff` + nav laboratorio | `/login` · `menu.portal:laboratorio` |
| **Menú de Administración** | Facturación AFIP, cobranza, stock de reactivos, usuarios, parámetros financieros | `layouts.staff` + nav administración | Mismo login · `menu.portal:administracion` |
| **Menú de Clientes** | Autogestión: pacientes propios, lista de precios, estimación de costos | `layouts.staff` + `sidebar-nav-cliente` | Mismo `/login` · `menu.portal:cliente` |

**Cantidad de sidebars previstos:** 3 layouts. Laboratorio y Administración comparten
login pero middleware `menu.portal` separa rutas sensibles.

---

## 1. Menú de Laboratorio

- **Audiencia:** bioquímicos, técnicos, recepción.
- **Rutas:** prefijo raíz (`/dashboard`, `/protocolos/…`, `/abm/…`) con middleware
  `auth` + `lab.context`.
- **Contexto de sesión:** `labCtx()` (usuario + rol).
- **Grupos típicos del sidebar (borrador):**

| Grupo | Módulos |
|-------|---------|
| **PROTOCOLOS** | Recepción · Búsqueda · Pendientes de resultado |
| **RESULTADOS** | Carga por protocolo · Carga por analizador |
| **INFORMES** | Emisión · Envío por mail |
| **PARAMETRIZACIÓN** | Clientes · Especies · Razas · Tipos de determinación · Ítems de informe |
| **CONSULTAS** | Listados · Estadísticas |

Orientación UI: **desktop-first**.

---

## 2. Menú de Administración

- **Audiencia:** administración, facturación, dirección técnica.
- **Rutas:** prefijo `/admin/…` o rutas con middleware `menu.portal:administracion`.
- **Grupos típicos:**

| Grupo | Módulos |
|-------|---------|
| **FACTURACIÓN** | Comprobantes AFIP · Cobranza · Medios de pago |
| **PRECIOS** | Lista de precios · Estimación por cliente |
| **STOCK** | Reactivos · Alertas de mínimo |
| **CONTABILIDAD** | Cuentas · Movimientos |
| **SISTEMA** | Usuarios · Roles · Parámetros (`entorno`) |

---

## 3. Menú de Clientes

- **Audiencia:** veterinarias y clínicas (`usuarios` con rol cliente e `idClientes` distinto de 1).
- **Rutas:** prefijo `/cliente/…` · middleware `auth` + `lab.context` + `menu.portal:cliente`.
- **Login:** el mismo `/login` que el personal; la redirección depende de `idClientes`.
- **Contexto:** filtrado estricto por `labCtx()->idClientes`.
- **Opciones del sidebar:** Pacientes · Lista de Precios · Estimación de Costos.
- **Layout:** misma estética que el Menú de Laboratorio (`layouts.staff` + `sidebar-nav-cliente`).

---

## 4. Redirección post-login

Login único en `/login`. Destino según `usuarios.idClientes` y rol:

| Condición | Destino |
|-----------|---------|
| `idClientes` distinto de 1 (y > 0) | `cliente.home` — Menú de Clientes (autogestión) |
| `idClientes` = 1 (laboratorio) o sin cliente | Menú de Laboratorio / Administración según rol |
| Rol administración / facturación (sin autogestión) | `admin.dashboard` — Menú de Administración |
| Rol operativo (default) | `dashboard` — Menú de Laboratorio |

Implementación: `App\Support\UsuarioMenuPortal` y middleware `menu.portal`.

---

## 5. Convenciones de rutas

- Nombres de ruta con prefijo de portal cuando aplique:
  - `protocolos.*`, `informes.*` → laboratorio
  - `admin.facturacion.*` → administración
  - `cliente.protocolos.*` → clientes
- No reutilizar el mismo nombre de ruta en portales distintos.
- Tooltips en sidebar: `title="Nombre del módulo (v1.0)"`.

---

## 6. Iconos del sidebar (regla obligatoria)

**Ningún icono puede repetirse** en el menú lateral del mismo portal: ni entre
grupos, ni entre opciones, ni entre un grupo y una opción de otro grupo.

### Reglas

1. Los **grupos** usan iconos del prefijo `grupo-*` (sección / categoría).
2. Las **opciones** usan iconos semánticos del módulo (`pacientes`, `determinaciones`, etc.).
3. Un icono de grupo **nunca** puede ser igual al de una opción, aunque el grupo
   tenga una sola opción visible.
4. Al agregar un módulo nuevo, registrar el icono en el catálogo antes de usarlo.
5. No incrustar SVG sueltos en partials del menú: usar siempre `<x-vl-sidebar-icon name="…" />`.

### Catálogo único

Fuente de verdad: `resources/views/components/vl-sidebar-icon.blade.php`.

| Clave | Uso |
|-------|-----|
| `inicio` | Enlace Inicio (fuera de grupos) |
| `grupo-gestion` | Grupo Gestión |
| `grupo-clientes` | Grupo Clientes |
| `grupo-tesoreria` | Grupo Tesorería |
| `grupo-stock` | Grupo Gestión de Stock |
| `grupo-parametros-generales` | Grupo Parámetros Generales |
| `grupo-parametros-determinaciones` | Grupo Parámetros Determinaciones |
| `grupo-listados-estadisticos` | Grupo Listados Estadísticos |
| `grupo-procedimientos-muestras` | Grupo Procedimientos Toma de Muestras |
| `pacientes` | Gestión de Pacientes |
| `derivaciones` | Gestión de Derivaciones |
| `determinaciones` | Gestión de Determinaciones (Administ) |
| `grupos-determinacion` | Gestión de Grupos |
| `det-por-grupo` | Det. por Grupo (Inf) |
| `items-informe` | Parametrización de Items |
| `automatizacion` | Script de Automatización |
| `centros-derivacion` | Gestión de Centros de Derivación |
| `parametros-sistema` | Parámetros del Sistema |
| `gestion-clientes` | Gestión de Clientes |
| `gestion-usuarios` | Gestión de Usuarios |
| `especies` | Gestión de Especies |
| `razas` | Gestión de Razas |
| `cuenta-corriente` | Cuenta Corriente |
| `movimientos` | Movimientos (Tesorería) |
| `movimientos-entre-cuentas` | Movimientos entre Cuentas (Tesorería / tesoreria_movimientos) |
| `saldos-por-dia` | Saldos por Día (Tesorería / tesoreria_movimientos) |
| `gestion-conceptos` | Gestión de Conceptos (Tesorería / tesoreria_movimientos) |
| `gestion-proveedores` | Gestión de Proveedores (Tesorería / tesoreria_movimientos) |
| `transferencias-intercuenta` | Transferencias Intercuenta (Tesorería) |
| `cuentas-contables` | Gestión de Cuentas Contables (Tesorería) |
| `cuentas-detalle` | Gestión de Cuentas Detalle (Tesorería) |
| `estimacion-costos` | Estimación de Costos (Listados Estadísticos) |
| `estadistico-pacientes` | Listado Estadístico de Pacientes |
| `historial-determinaciones` | Historial de Determinaciones |
| `cantidad-determinaciones-comparac` | Cantidad Determinaciones (comparac.) |
| `excel-pacientes` | Excel de Pacientes (Listados Estadísticos) |
| `lista-precios` | Lista de Precios (Menú de Clientes) |
| `gestion-procedimientos` | Gestión de Procedimientos |
| `muestras-por-determinacion` | Muestras por Determinación |

Al sumar filas a esta tabla, verificar que el `name` nuevo no exista ya en el componente.

---

## 7. Equivalencia con Sistemas Escolares

| SE | SILAVET |
|----|---------|
| Menú de Secretaría | Menú de Laboratorio |
| Menú de Administración | Menú de Administración |
| Menú de Alumnos | Menú de Clientes |
| Menú de Docentes | *(no aplica; rol operativo integrado en Laboratorio)* |
