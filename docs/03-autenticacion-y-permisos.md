# Autenticación y Permisos

---

## 1. Portales y logins

Hay **tres menús de navegación** (ver [08-menus-de-navegacion.md](08-menus-de-navegacion.md))
y **un login** unificado:

| Menú | Login |
|------|--------|
| **Menú de Laboratorio** | `/login` → personal operativo / usuarios con `idClientes` = 1 |
| **Menú de Administración** | Mismo login; redirección por rol |
| **Menú de Clientes** | Mismo `/login` → `idClientes` distinto de 1 |

---

### 1.1 Login de gestión (tabla `usuarios`)

Aplica a: bioquímicos, técnicos, administrativos del laboratorio.

| Campo          | Origen                           |
|----------------|----------------------------------|
| Usuario        | `usuarios.dni`                   |
| Contraseña     | `usuarios.password`              |

**Implementación prevista:**

- Componente Livewire: `App\Livewire\Auth\Login`
- Auth provider custom: `App\Auth\UsuarioUserProvider`
- Al hacer login, se establece el `LabContext` (idUsuarios, idRoles, idClientes) en sesión.
- Middleware `EnsureLabContext` protege todas las rutas autenticadas de gestión.

**Menú según rol (`idRoles`):**

| Rol típico | Menú tras login |
|------------|-----------------|
| Operativo / técnico | **Menú de Laboratorio** |
| Administración / facturación | **Menú de Administración** |
| Cliente veterinario (`idRoles` = 1) | **Menú de Clientes** (login separado) |

Middleware `menu.portal` impide cruzar portales por URL directa.

---

### 1.2 Login del Menú de Clientes

Portal para veterinarias y clínicas que consultan protocolos e informes.

| Campo          | Origen                           |
|----------------|----------------------------------|
| Usuario        | `usuarios.dni`                   |
| Contraseña     | `usuarios.password`              |
| Alcance        | Solo protocolos de `usuarios.idClientes` |

**Diferencias clave con el login de gestión:**

- Sin acceso a ABMs internos ni facturación.
- Consultas filtradas estrictamente por `idClientes` del usuario.
- Requiere guard y rutas separadas del personal de laboratorio.

---

## 2. Manejo de Contraseñas — Texto plano

Las contraseñas se almacenan en **texto plano** en `usuarios.password` (`varchar(10)`),
alineado con el sistema NeoLab legacy y con la misma convención acordada en Sistemas Escolares.

```
┌─────────────────────┐     ┌──────────────────────────────┐
│ Alta / edición /    │────►│ Texto plano en `password`    │
│ blanqueo de clave   │     │ (sin bcrypt ni otro hash)    │
└─────────────────────┘     └──────────────────────────────┘
                                       │
                                       ▼
                             ┌──────────────────────────────┐
                             │ Login: hash_equals()         │
                             │ (UsuarioUserProvider)        │
                             └──────────────────────────────┘
```

**Lógica de validación** (en `UsuarioUserProvider::validateCredentials`):

- Comparar la contraseña ingresada con el valor almacenado usando `hash_equals()`.

**Regla para código nuevo:**

- Al crear usuario, blanquear o cambiar contraseña → guardar **siempre en texto plano**.
- **No** usar bcrypt ni migración automática a hash en el login.

**Motivo operativo:** administración informa la clave al usuario; debe poder consultarse
y restablecerse cuando corresponda, igual que en el sistema escolar.

---

## 3. Contexto de sesión — `LabContext`

```
┌──────────┐   autentica    ┌──────────────────────┐
│  Login   │───────────────►│  Sesión: idUsuarios   │
│          │                │  idRoles, idClientes  │
└──────────┘                └──────────────────────┘
                                       │
                                       ▼
                             ┌──────────────────────┐
                             │  Consultas filtradas  │
                             │  por rol y cliente    │
                             └──────────────────────┘
```

**Implementación prevista:** `App\Support\LabContext`

- Almacena `idUsuarios`, `idRoles`, `idClientes` (nullable) en sesión.
- Helper global `labCtx()` retorna la instancia.
- Personal de laboratorio: consultas sin restricción de cliente (salvo permisos).
- Usuario cliente: **siempre** filtrar por `labCtx()->idClientes`.

---

## 4. Modelo de Permisos

### Estado legacy

- `roles` + `usermenu`: menú por rol en sistema ScriptCase.
- `usuarios.permisoAfip`: flag puntual para facturación.

### Sistema nuevo (previsto, igual que SE)

- `permisos_ia` — catálogo de permisos del sistema nuevo (`id`, `orden`, `tema`, `descripcion`).
- `usuarios.permisos_ia` — cadena de `0` y `1` (un carácter por cada `orden` del catálogo).
- Catálogo de referencia en código: `App\Support\PermisosIaCatalog`.
- SQL de sincronización: `database/sql/permisos_ia_catalogo_completo.sql`.

### Mecánica

```
permisos_ia = "111111111111111..."
                 │││
                 ││└─ orden=2 → tiene permiso
                 │└── orden=1 → tiene permiso
                 └─── orden=0 → tiene permiso
```

### Verificación obligatoria

- Helper global: `tienePermiso(int $orden)`.
- Rutas: middleware `permiso:N`.
- Livewire / controladores: `abort_unless(tienePermiso(N), 403)` en `mount()` y acciones sensibles.
- Menú: `@if (tienePermiso(N))` por ítem o grupo.

### Ejemplo

```php
abort_unless(tienePermiso(PermisosIaCatalog::PROTOCOLOS_GESTION), 403);
```

---

## 5. Catálogo de permisos (borrador Etapa 1)

Los órdenes definitivos se fijarán al implementar el catálogo. Referencia inicial:

| Orden | Tema | Descripción |
|-------|------|-------------|
| 0 | Clientes | ABM clientes |
| 1 | Especies | ABM especies y razas |
| 2 | Determinaciones | ABM tipos de determinación |
| 3 | Protocolos | Recepción y gestión de protocolos |
| 4 | Resultados | Carga de resultados |
| 5 | Informes | Emisión y envío de informes |
| 6 | Facturación | Comprobantes y cobranza |
| 7 | Reactivos | Stock de reactivos |
| 8 | Parámetros | Configuración del laboratorio (`entorno`) |
| 9 | Usuarios | ABM usuarios y roles |
| 10 | Listados estadísticos | Estimación de costos y listados estadísticos |
