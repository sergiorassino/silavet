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
6. **Assets:** igual que Sistemas Escolares: `public/build/` **va en el repo** (no está en `.gitignore`). **Siempre** que haya cambios en CSS/JS (`resources/css/**`, `resources/js/**`): correr `npm run build` en la misma tarea, commit de `public/build/` y push. Sin rebuild, producción sigue sirviendo CSS/JS viejo. En el servidor, tras `git pull`, **borrar** `public/hot` si existe (solo sirve para Vite en desarrollo).
7. **Apache:** `mod_rewrite` activo y `AllowOverride All`.
8. **HTTPS:** coherente con `SESSION_SECURE_COOKIE=true`.
9. **BD atrasada (primer alta de un lab viejo):** en local, con `lb_neolab` y la BD del cliente cargadas, `php artisan lb:schema-sync {slug}` genera SQL aditivo. Ejecutar ese SQL **a mano** sobre la BD del cliente (local y/o producción). Después `lb:migrate-legacy --force`. En el hosting **no** suele estar NeoLab; llevar el `.sql` generado.

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
| Login / Livewire **419** (“This page has expired”) | Falta `LivewireDeploymentScripts` (`data-update-uri` con `APP_URL` completo) o `SESSION_DOMAIN=null` literal / cookie Secure. |
| `livewire.js` 403 | Hosting bloquea `/vendor/`; usar ruta Laravel alternativa. |
| Logo no se guarda | Permisos en `public/entorno/` y `storage/app/livewire-tmp`. |
| `The logoUpload failed to upload` / `upload-file` **401** | Firma HTTPS/subcarpeta: ver **Subida de archivos Livewire**. |

---

## Subida de archivos Livewire (`upload-file` 401)

En Red (F12): si `livewire/update` es **200** y `livewire/upload-file` es **401**, no es tamaño
ni login: la URL firmada no coincide con la que ve PHP.

| Petición | Qué valida | Por qué falla en producción |
|----------|------------|-----------------------------|
| `…/update` | Sesión + CSRF | URI incorrecta en subcarpeta → **419**. Se corrige con `LivewireDeploymentScripts` (`data-update-uri` con `APP_URL` completo). |
| `…/upload-file` | **Firma** (host + https + path de `APP_URL`) | `public/index.php` recorta la subcarpeta; sin `X-Forwarded-Prefix` / HTTPS la firma no cuadra → **401** → mensaje *"failed to upload"*. |

Checklist:

1. **`APP_URL`** = URL exacta del navegador (`https://…` + subcarpeta, sin barra final).
2. Desplegar `app/Http/Middleware/ForceHttpsBehindProxy.php` (HTTPS + `X-Forwarded-Prefix`).
3. `php artisan config:clear`.
4. Si Cloudflare: SSL **Full** (no Flexible).
5. Permisos de escritura en `storage/app/livewire-tmp` (usuario del servidor web).

---

## Logo institucional

El archivo se guarda en `public/entorno/logos/{TENANT_SLUG}/`. En **`entorno.logo`**,
**`firmaIzq`**, **`firmaCentro`** y **`firmaDer`** se persiste el **nombre original**
del archivo subido (`MiLogo.jpg`), igual que NeoLab. SILAVET resuelve ese nombre
a `public/entorno/logos/{TENANT_SLUG}/` o `public/entorno/firmas/{TENANT_SLUG}/`.

Opcional: `ENTORNO_LOGO_LEGACY_DIR` (carpeta `_lib/file/img` de ScriptCase) para
espejar logo y firmas al subir desde SILAVET.

En la misma carpeta van el encabezado y el pie opcionales del informe
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
- `App\Support\LivewireDeploymentScripts` — `data-update-uri` / scripts Livewire con `APP_URL`
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

`SESSION_DOMAIN` debe quedar **vacío** (`SESSION_DOMAIN=`). No usar `null` (puede quedar como texto `"null"` en la cookie).

Tras desplegar: `php artisan config:clear` y `php artisan view:clear`.
