# Despliegue Apache sin `/public` en la URL

Adaptado desde Sistemas Escolares. Mismas reglas técnicas; reemplazar referencias
`se_*` por `vl_*` y rutas de colegio por rutas de laboratorio.

---

## Por qué en local funciona y en producción no

| Entorno | Qué ocurre |
|---------|------------|
| `php artisan serve` | El document root **es** `public/`. `APP_URL` suele ser `http://127.0.0.1:8000`. |
| Producción (subcarpeta) | El navegador pide `https://dominio.com/lab/neolab/login`. Apache debe tener el **document root en `sistema/`** (padre de `public/`), con `.htaccess` en la raíz reenviando a `public/`. |

---

## Checklist en el servidor

1. **Subir** toda la carpeta `sistema/` (no solo `public/`).
2. **Document root** = carpeta que contiene `artisan`, `app/` y `public/`.
3. **`.htaccess` en la raíz** de `sistema/` (incluir al inicializar Laravel).
4. **`APP_URL` en `.env`** = URL pública exacta, **con** subcarpeta si aplica:
   - Subcarpeta: `https://dominio.com/lab/neolab` (sin barra final; sin `/public`).
   - Subdominio: `https://lab.ejemplo.com`.
5. **`php artisan config:clear`** tras cambiar `.env`.
6. **Assets:** `npm run build` o subir `public/build/`. **Borrar** `public/hot` en producción.
7. **Apache:** `mod_rewrite` activo y `AllowOverride All`.
8. **HTTPS:** coherente con `SESSION_SECURE_COOKIE=true`.

---

## Síntomas frecuentes

| Síntoma | Causa probable |
|---------|----------------|
| 404 en rutas | Document root en `public/` o falta `.htaccess` en raíz. |
| CSS/JS rotos | `public/hot` presente, falta `public/build/`, o `APP_URL` incorrecto. |
| Login no persiste | `APP_URL` sin path de subcarpeta → cookies mal scoped. |
| Livewire 404 en AJAX | `APP_URL` mal; `URL::forceRootUrl` en `AppServiceProvider`. |
| `livewire.js` 403 | Hosting bloquea `/vendor/`; usar ruta Laravel alternativa. |
| Logo no se guarda | Permisos en `storage/`; `php artisan storage:link`. |
| Livewire upload 401 | Firma HTTPS: ver middleware `ForceHttpsBehindProxy`. |

---

## Logo institucional

Previsto en `storage/app/public/entorno/logos/{TENANT_SLUG}/` y campos en `entorno`
(o columnas adicionales vía migración aditiva).

Checklist:

1. **`TENANT_SLUG`** en `.env` antes de `config:cache`.
2. Permisos de escritura en `storage/app/public` y `storage/app/livewire-tmp`.
3. **`php artisan storage:link`**.
4. **`APP_URL`** con path completo + `config:clear`.

---

## Archivos implicados (al inicializar Laravel)

- `sistema/.htaccess` — reescritura raíz → `public/`
- `sistema/public/.htaccess` — front controller Laravel
- `sistema/public/index.php` — ajuste de `REQUEST_URI` según `APP_URL`
- `AppServiceProvider` — `session.path`, `asset_url`, Livewire en subcarpeta
- `resources/views/layouts/partials/livewire-scripts.blade.php`

---

## Plantilla `.env` producción (subcarpeta)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://dominio.com/lab/neolab

TENANT_SLUG=neolab
DB_DATABASE=lb_neolab

SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=
```

Tras desplegar: `php artisan config:clear` y `php artisan view:clear`.
