# Menús de navegación (terminología oficial)

Este documento fija los **nombres** que usamos en el equipo para los portales con sidebar.
Evita confusiones entre roles operativos, administración y clientes veterinarios.

---

## Resumen

| Nombre oficial | Qué es | Layout Blade | Login / guard |
|----------------|--------|--------------|---------------|
| **Menú de Laboratorio** | Operaciones analíticas: protocolos, resultados, informes, parametrización operativa | `resources/views/layouts/laboratorio.blade.php` | `/login` · `menu.portal:laboratorio` |
| **Menú de Administración** | Facturación AFIP, cobranza, stock de reactivos, usuarios, parámetros financieros | `resources/views/layouts/administracion.blade.php` | Mismo login · `menu.portal:administracion` |
| **Menú de Clientes** | Portal veterinarias/clínicas: consulta de protocolos e informes | `resources/views/layouts/cliente.blade.php` | `/loginCliente` · guard `cliente` |

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
| **PARAMETRIZACIÓN** | Clientes · Especies · Tipos de determinación · Ítems de informe |
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

- **Audiencia:** veterinarias y clínicas (`usuarios` con `idClientes` > 0).
- **Rutas:** prefijo `/cliente/…` · middleware `auth:cliente`.
- **Contexto:** filtrado estricto por `labCtx()->idClientes`.
- **Enlaces típicos:** mis protocolos · informes PDF · notificaciones · datos de cuenta.

Orientación UI: **mobile-first** (ver [01-descripcion-general.md](01-descripcion-general.md)).

---

## 4. Redirección post-login (prevista)

| Condición | Destino |
|-----------|---------|
| `idClientes` > 0 (login cliente) | `cliente.home` — Menú de Clientes |
| Rol administración / facturación | `admin.dashboard` — Menú de Administración |
| Rol operativo (default) | `dashboard` — Menú de Laboratorio |

Implementación prevista: `App\Support\UsuarioMenuPortal` y middleware `menu.portal`.

---

## 5. Convenciones de rutas

- Nombres de ruta con prefijo de portal cuando aplique:
  - `protocolos.*`, `informes.*` → laboratorio
  - `admin.facturacion.*` → administración
  - `cliente.protocolos.*` → clientes
- No reutilizar el mismo nombre de ruta en portales distintos.
- Tooltips en sidebar: `title="Nombre del módulo (v1.0)"`.

---

## 6. Equivalencia con Sistemas Escolares

| SE | SILAVET |
|----|---------|
| Menú de Secretaría | Menú de Laboratorio |
| Menú de Administración | Menú de Administración |
| Menú de Alumnos | Menú de Clientes |
| Menú de Docentes | *(no aplica; rol operativo integrado en Laboratorio)* |
