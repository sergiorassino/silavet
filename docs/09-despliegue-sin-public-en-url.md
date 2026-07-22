# Despliegue Apache sin `/public` en la URL

Adaptado desde Sistemas Escolares. Mismas reglas técnicas; reemplazar referencias
`se_*` por `vl_*` y rutas de colegio por rutas de laboratorio.

---

## Por qué en local funciona y en producción no

| Entorno | Qué ocurre |
|---------|------------|
| `php artisan serve` | El document root **es** `public/`. `APP_URL` suele ser `http://127.0.0.1:8000`. |
| Producción (subcarpeta) | El navegador pide `https://dominio.com/silavet/login`. Apache debe tener el **document root en la carpeta del lab** (padre de `public/`, donde está `artisan`), con `.htaccess` en esa raíz reenviando a `public/`. |

---

## Checklist en el servidor

1. **Clonar el repo** en la carpeta del lab (p. ej. `public_html/silavet`), rama **`main`** — igual que Sistemas Escolares.
2. **Document root** = carpeta que contiene `artisan`, `app/` y `public/`.
3. **`.htaccess` en la raíz** del proyecto (junto a `artisan`).
4. **`APP_URL` en `.env`** = URL pública exacta, **con** subcarpeta si aplica:
   - Subcarpeta: `https://dominio.com/silavet` (sin barra final; sin `/public`).
   - Subdominio: `https://lab.ejemplo.com`.
5. **`php artisan config:clear`** tras cambiar `.env`.
6. **Assets:** igual que Sistemas Escolares: `public/build/` **va en el repo** (no está en `.gitignore`). En local, antes de publicar cambios de CSS/JS: `npm run build`, commit de `public/build/` y push. En el servidor, tras `git pull`, **borrar** `public/hot` si existe (solo sirve para Vite en desarrollo).
7. **Apache:** `mod_rewrite` activo y `AllowOverride All`.
8. **HTTPS:** coherente con `SESSION_SECURE_COOKIE=true`.

### Actualización (igual que colegios)

```bash
cd ~/public_html/silavet   # carpeta con artisan y .git
git pull --ff-only
php artisan config:clear
php artisan view:clear
php artisan route:clear
php artisan lb:migrate-legacy --force
```

---

## Síntomas frecuentes

| Síntoma | Causa probable |
|---------|----------------|
| 404 en rutas | Document root en `public/` o falta `.htaccess` en raíz. |
| CSS/JS rotos | `public/hot` presente, falta `public/build/`, o `APP_URL` incorrecto. |
| Login no persiste | `APP_URL` sin path de subcarpeta → cookies mal scoped. |
| Livewire 404 en AJAX | `APP_URL` mal; `URL::forceRootUrl` en `AppServiceProvider`. |
| `livewire.js` 403 | Hosting bloquea `/vendor/`; usar ruta Laravel alternativa. |
| Logo no se guarda | Permisos en `public/entorno/` y `storage/app/livewire-tmp`. |
| `The logoUpload failed to upload` / `upload-file` **401** | Firma HTTPS/subcarpeta: ver **Subida de archivos Livewire**. |

---

## Subida de archivos Livewire (`upload-file` 401)

En Red (F12): si `livewire/update` es **200** y `livewire/upload-file` es **401**, no es tamaño
ni login: la URL firmada no coincide con la que ve PHP.

| Petición | Qué valida | Por qué falla en producción |
|----------|------------|-----------------------------|
| `…/update` | Sesión + CSRF | Suele andar si Livewire/AJAX general funciona. |
| `…/upload-file` | **Firma** (host + https + path de `APP_URL`) | `public/index.php` recorta la subcarpeta; sin `X-Forwarded-Prefix` / HTTPS la firma no cuadra → **401** → mensaje *"failed to upload"*. |

Checklist:

1. **`APP_URL`** = URL exacta del navegador (`https://…` + subcarpeta, sin barra final).
2. Desplegar `app/Http/Middleware/ForceHttpsBehindProxy.php` (HTTPS + `X-Forwarded-Prefix`).
3. `php artisan config:clear`.
4. Si Cloudflare: SSL **Full** (no Flexible).
5. Permisos de escritura en `storage/app/livewire-tmp` (usuario del servidor web).

---

## Logo institucional

Se guarda en `public/entorno/logos/{TENANT_SLUG}/` (campo `entorno.logo`). En la
misma carpeta van el encabezado y el pie opcionales del informe
(`entorno.headerInforme` → `header-informe.*`, `entorno.footerInforme` →
`footer-informe.*`). La subida temporal de Livewire usa `storage/app/livewire-tmp`.

Checklist:

1. **`TENANT_SLUG`** en `.env` antes de `config:cache`.
2. Permisos de escritura en `public/entorno` y `storage/app/livewire-tmp`.
3. **`APP_URL`** con path completo + `config:clear`.
4. Elegir archivo, esperar a que desaparezca «Subiendo…», luego **Guardar**.

---

## Archivos implicados (al inicializar Laravel)

- `.htaccess` — reescritura raíz → `public/`
- `public/.htaccess` — front controller Laravel
- `public/index.php` — ajuste de `REQUEST_URI` según `APP_URL`
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
