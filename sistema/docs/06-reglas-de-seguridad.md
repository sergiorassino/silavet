# Reglas de Seguridad (Baseline Obligatorio)

> Estas reglas aplican a **todo módulo nuevo** y a cambios en módulos existentes.
> Son el estándar mínimo de seguridad del sistema.
> Adaptado desde Sistemas Escolares (`docs/06-reglas-de-seguridad.md`).

---

## 1. Autenticación y Sesión

- Usar `auth` middleware para toda ruta interna.
- Cookies/sesión seguras (config Laravel): `SESSION_SECURE_COOKIE`, `SESSION_HTTP_ONLY`, `same_site`.
- Regenerar sesión/token en login/logout.
- Dos guards separados: gestión (`usuarios` staff) y clientes (`usuarios` con `idClientes`).

---

## 2. Autorización (evitar acceso indebido)

- Todo ABM debe tener **chequeo de alcance por contexto** y/o permisos:
  - Personal de laboratorio: según `tienePermiso(N)`.
  - Usuario cliente: **filtrar siempre** por `labCtx()->idClientes`.
  - En operaciones por ID (editar/eliminar/ver informe), **volver a consultar** el
    registro con el mismo filtro (no confiar en IDs del cliente).
- Verificar permisos con cadena `0/1` de `usuarios.permisos_ia` contra catálogo
  `permisos_ia` (ver [03-autenticacion-y-permisos.md](03-autenticacion-y-permisos.md)).

---

## 3. Validación, Normalización y Seguridad de Datos

- Validar **siempre server-side** (`$this->validate()` o FormRequest).
- Normalizar entradas antes de guardar: `trim()` en strings.
- Evitar mass-assignment peligroso:
  - Preferir `$fillable` explícito en modelos.
  - En updates/creates, pasar arrays con claves explícitas.

---

## 4. Protección contra XSS

- En Blade, usar `{{ }}` (escape) siempre.
- Evitar `{!! !!}`; si fuese indispensable, sanitizar en backend primero.

---

## 5. SQL Injection

- Usar Eloquent/Query Builder con parámetros (bindings).
- Evitar `DB::raw()` con entrada de usuario.

---

## 6. Rate Limiting y Abuso

- Rate-limit en acciones sensibles (crear/editar/eliminar) en Livewire/Controllers.
- Límites por usuario con ventanas cortas:
  - Save/update: máx 30/min
  - Delete: máx 10/min

---

## 7. Errores y Logging

- No exponer trazas/SQL en producción (`APP_DEBUG=false`).
- Loggear eventos de ABM importantes sin datos sensibles (CUIT, claves AFIP).

---

## 8. Checklist por Módulo / PR

- [ ] ¿La consulta está filtrada por contexto (`labCtx`) cuando corresponde?
- [ ] ¿Usuarios cliente solo ven datos de su `idClientes`?
- [ ] ¿Acciones por ID revalidan alcance del registro?
- [ ] ¿Hay validación y normalización server-side?
- [ ] ¿No hay `DB::raw()` con input?
- [ ] ¿Blade escapa correctamente?
- [ ] ¿Rate limiting configurado en acciones sensibles?
- [ ] ¿Permisos verificados según modelo de cadena `0/1`?
- [ ] ¿Quedó documentado el SQL equivalente al cierre (§9.1)?
- [ ] ¿URLs visibles no exponen IDs enumerables (§10)?

---

## 9. Base de datos: operaciones destructivas y control de ejecución

**Prohibido** salvo entorno desechable, backup verificado y aprobación explícita:
`migrate:fresh`, `migrate:refresh`, `db:wipe`, `DROP TABLE` masivos, etc.

**Política de desarrollo asistido:** ningún agente de IA debe **ejecutar** nada
que altere esquema o datos en la base del proyecto. Entregar SQL o comandos
Artisan **como texto** para revisión humana.

### 9.1 Entregable al cerrar cambios que tocan la base de datos

Al final de la respuesta o PR:

- SQL ejecutable equivalente al `up()` de la migración; y
- Advertencia de alcance e irreversibilidad si aplica.

---

## 10. URLs sin identificadores reveladores

Las URLs visibles **no deben** incluir:

- IDs numéricos de tablas (`/informe/44205`, `?idPacientes=123`).
- CUIT, DNI, número de protocolo predecible.
- Tokens de descarga sin cifrar.

### Qué hacer en su lugar

| Caso | Patrón |
|------|--------|
| PDF/informe en portal clientes | `OpaqueRouteToken` cifrado con `APP_KEY` |
| Enlaces internos ABM (staff) | `{id}` tras `auth` + permisos |
| Archivo de descarga | Nombre sin IDs internos |

Implementación prevista: `app/Support/Security/OpaqueRouteToken.php`.
