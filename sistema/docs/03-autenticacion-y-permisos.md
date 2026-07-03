# Autenticación y Permisos

---

## 1. Portales y logins

Hay **tres menús de navegación** (ver [08-menus-de-navegacion.md](08-menus-de-navegacion.md))
y **dos logins** previstos:

| Menú | Login |
|------|--------|
| **Menú de Laboratorio** | `/login` → `usuarios` (roles operativos) |
| **Menú de Administración** | Mismo login; redirección por rol |
| **Menú de Clientes** | `/loginCliente` → `usuarios` con `idClientes` > 0 |

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
| Cliente veterinario (`idClientes` > 0) | **Menú de Clientes** (login separado) |

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

## 2. Manejo de Contraseñas — Modo Híbrido

El sistema legacy usa contraseñas en **texto plano** (`varchar(10)`). La versión
nueva adoptará el mismo esquema híbrido que Sistemas Escolares:

```
┌─────────────────────┐     ┌──────────────────────────────┐
│ Usuarios existentes │────►│ Contraseña en texto plano    │
│ (legacy)            │     │ Comparación: hash_equals()   │
└─────────────────────┘     └──────────────────────────────┘

┌─────────────────────┐     ┌──────────────────────────────┐
│ Usuarios nuevos o   │────►│ Hash bcrypt ($2y$ / $2a$)    │
│ blanqueo de clave   │     │ Comparación: password_verify()│
└─────────────────────┘     └──────────────────────────────┘
```

**Lógica de validación** (en `UsuarioUserProvider::validateCredentials`):

1. Si `$stored` empieza con `$2y$` o `$2a$` → usar `password_verify()`.
2. Si no → comparar con `hash_equals()` (texto plano legacy).

**Regla para código nuevo:**

- Al crear usuario nuevo o blanquear contraseña → guardar con `bcrypt()`.

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
