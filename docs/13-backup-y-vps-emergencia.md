# Backup de archivos y VPS de emergencia

Cómo armar un laboratorio de reserva que se puede levantar **a demanda**
(última hora), sin que el VPS de emergencia se actualice cada hora.

Piezas:

| Pieza | Dónde | Qué cubre |
|-------|--------|-----------|
| Código | Git (`main`) | Laravel, vistas, `public/build/` |
| Base de datos | S3 `backupHora` (ya existe) | Dump MySQL horario |
| Archivos de laboratorio | S3 espejo incremental | Lo que no está en git |

El VPS de emergencia queda **preparado** (stack + clone + Composer + overlay de
`.env`). **No** hay cron ahí. Cuando cae producción se corre un solo comando.

---

## 1. Qué se sube a S3 (archivos)

Prefijo: `s3://sistesco/backupDedicado/archivos/{PROYECTO}/`

| Ruta | Contenido |
|------|-----------|
| `.env` | Claves de la app (el bucket ya es sensible por los dumps) |
| `public/REPOSITORIO/` | Imágenes de renglones |
| `public/entorno/` | Logos, firmas, encabezado/pie, listas de precios |
| `public/adjuntos/` | PDF adjuntos al protocolo |
| `afipSE/cert/` | Certificados AFIP por usuario |
| `storage/fonts/` | Arial (PDF TCPDF) |
| `storage/app/AUTOANALIZADORES/` | CSV de analizadores, si se usa |

No se sube `vendor/`, logs, cache, `livewire-tmp` ni el árbol git. El sync **no**
usa `--delete`: un archivo borrado en disco no se borra en S3.

Orden en el cron de producción: **primero el dump MySQL, después**
`respaldar-archivos.sh`.

---

## 2. Producción — un cron más, después del dump

Requisitos: AWS CLI, `flock`, el mismo usuario/IAM que ya sube los dumps.

### 2.1 Hosting compartido (`aws: command not found`)

En Hostinger y similares **no** viene AWS CLI. Instalarlo en el home, sin root:

```bash
cd ~
curl "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o awscliv2.zip
unzip -q awscliv2.zip
./aws/install -i "$HOME/.local/aws-cli" -b "$HOME/.local/bin"
"$HOME/.local/bin/aws" --version
```

Si `unzip` o el instalador fallan, alternativa:

```bash
pip3 install --user awscli
# suele quedar en ~/.local/bin/aws
~/.local/bin/aws --version
```

Credenciales (las mismas del backup de bases):

```bash
mkdir -p ~/.aws
nano ~/.aws/credentials
nano ~/.aws/config
```

```ini
# ~/.aws/credentials
[default]
aws_access_key_id=...
aws_secret_access_key=...

# ~/.aws/config
[default]
region=sa-east-1
```

Usá la región real del bucket `sistesco`. Probar:

```bash
export PATH="$HOME/.local/bin:$PATH"
aws sts get-caller-identity
aws s3 ls s3://sistesco/
```

En `scripts/emergencia/config.env` (del laboratorio):

```bash
AWS_BIN=/home/u577894275/.local/bin/aws
```

(ajustá el usuario; `echo $HOME` + `/.local/bin/aws`).

Si no sabés cómo se suben hoy los dumps, buscá el cron o el script de backup: ahí están la key y la región.

---

```bash
sudo mkdir -p /etc/silavet
sudo cp /var/www/silavet/scripts/emergencia/config.example.env /etc/silavet/config.env
sudo chmod 600 /etc/silavet/config.env
sudo nano /etc/silavet/config.env
```

Ajustar `PROYECTO`, `APP_DIR`, `S3_PREFIX_DUMPS` (hoy el dump horario vive en
`backupDedicdo/backupHora` — respetar el nombre real del prefijo),
`S3_DUMP_FILTER` (subcadena única de **este** laboratorio en el nombre del
`.sql.gz`) y `S3_PREFIX_ARCHIVOS`.

Probar:

```bash
sudo bash /var/www/silavet/scripts/emergencia/respaldar-archivos.sh --dry-run
sudo bash /var/www/silavet/scripts/emergencia/respaldar-archivos.sh
```

Cron (minuto 5, cuando el dump de las :00 ya terminó; adaptar la ruta):

```cron
5 * * * * /bin/bash /var/www/silavet/scripts/emergencia/respaldar-archivos.sh >> /var/log/silavet-archivos.log 2>&1
```

Hasta que esto corra **una vez**, el VPS de emergencia no tiene archivos que
bajar.

---

## 3. VPS de emergencia — preparación (una vez)

Este VPS de reserva es **DirectAdmin + AlmaLinux**
(`sistemasescolares4.com.ar`). No uses `/var/www` ni `www-data`: el sitio vive
en el `public_html` del usuario del panel. El recetario detallado es el **§3.1**.

Resumen: clone en `APP_DIR` → PHP CLI del panel con extensiones reales →
`config.env` + overlay → Composer → **sin cron**. Los datos se bajan recién
con `restaurar.sh`.

Diagnóstico (no cambia nada; pegá la salida si falla):

```bash
cd /home/admin/public_html/silavet/alqu
bash scripts/emergencia/diagnostico.sh
```

---

## 3.1 DirectAdmin (sistemasescolares4.com.ar) — recetario

Trabajá por **SSH como `admin`**, salvo los pasos marcados **(root)**.
No ejecutes `preparar-vps.sh` con `php`: es un script **bash**.

URL de emergencia:

`https://sistemasescolares4.com.ar/silavet/alqu`

Carpeta Laravel (`APP_DIR`, donde está `artisan`):

`/home/admin/public_html/silavet/alqu`

### Paso A — El PHP de SSH no es el de los sitios

En DirectAdmin hay **varios PHP**. El `php` de AlmaLinux (PATH) suele no tener
MySQL. Los sitios usan `/usr/local/php82/bin/php` o `/usr/local/php83/bin/php`.

El error que bloquea `preparar-vps.sh` es este:

```
PHP Warning: Unable to load dynamic library 'pdo_mysql' ... no-debug-non-zts-.../pdo_mysql.so
ERROR: Falta la extensión PHP: pdo_mysql
```

**Qué significa:** el `php.ini` del CLI pide `extension=pdo_mysql` (y curl, gd,
mbstring, zip, …) como `.so`. En DirectAdmin esas extensiones **ya van
compiladas dentro de PHP**. No hay archivo `.so`. Poner `extension=...` en el
ini **no las activa**: solo tira el warning. `php -m | grep pdo_mysql` miente
porque coincide con el texto del warning.

Comprobación que sí vale (tiene que decir `yes`):

```bash
ls -d /usr/local/php*/bin/php
/usr/local/php83/bin/php -r 'echo extension_loaded("pdo_mysql") ? "yes" : "no", "\n";' 2>/dev/null
/usr/local/php82/bin/php -r 'echo extension_loaded("pdo_mysql") ? "yes" : "no", "\n";' 2>/dev/null
/usr/local/php83/bin/php --ini
```

#### A1. Si algún binario dice `yes`

Ese es tu `PHP_BIN`. En `scripts/emergencia/config.env`:

```bash
PHP_BIN=/usr/local/php83/bin/php
```

(ajustá `83` / `82` al que dijo `yes`). En DirectAdmin → **Account Manager →
PHP Settings** (o Domain Setup) del dominio, la versión del sitio debe ser **la
misma**.

#### A2. Si todos dicen `no` y ves `Unable to load dynamic library`

**(root)** El ini CLI tiene líneas `extension=` de más. Localizá el archivo:

```bash
/usr/local/php83/bin/php --ini
# "Loaded Configuration File" = el que hay que editar
```

Suele ser `/usr/local/php83/lib/php.ini`. Comentá (poné `;` adelante) **todas**
estas si existen:

```ini
;extension=curl
;extension=gd
;extension=mbstring
;extension=mysqli
;extension=pdo_mysql
;extension=zip
```

También revisá `/usr/local/php83/lib/php.conf.d/*.ini` por las mismas líneas.

Volvé a correr el `extension_loaded("pdo_mysql")`. Si ahora dice `yes`, seguí.
No hace falta rebuild.

#### A3. Si después de comentar el ini sigue en `no`

Las extensiones **no están compiladas** en ese PHP. **(root)** rebuild con
CustomBuild (varios minutos; no corta los sitios que usan otro PHP):

```bash
cd /usr/local/directadmin/custombuild
./build update
./build php n
```

O desde el panel: **Admin → CustomBuild → Build** PHP. `pdo_mysql` / `curl` /
`mbstring` / `zip` / `gd` van por defecto; no se agregan con PECL ni con
`extension=` en el ini.

Después, otra vez `extension_loaded("pdo_mysql")` → `yes`.

### Paso B — Composer, Git, AWS CLI (como `admin`)

```bash
export PATH="/usr/local/php83/bin:$PATH"   # el PHP que dijo yes
php -v
php -r 'echo extension_loaded("pdo_mysql") ? "pdo_mysql yes\n" : "pdo_mysql NO\n";'

command -v git || echo "instalar git (root: dnf install git)"
command -v composer || curl -sS https://getcomposer.org/installer | php -- --install-dir="$HOME/bin" --filename=composer
export PATH="$HOME/bin:$PATH"
composer -V

command -v aws || echo "falta AWS CLI — igual que en producción, o docs/13 §2.1"
aws sts get-caller-identity
aws s3 ls s3://sistesco/backupDedicado/archivos/alqu/
aws s3 ls s3://sistesco/backupDedicdo/backupHora/ | grep -i labvetciudad | tail
```

Si `composer` queda en `$HOME/bin`, ese directorio tiene que estar en PATH
cuando corras `preparar-vps.sh` (o agregalo en `~/.bashrc`).

### Paso C — Base MySQL local (panel DirectAdmin)

`restaurar.sh` importa el dump en **este** MySQL, no en el de Hostinger.

1. DirectAdmin → **MySQL Management** → Create Database.
2. Anotá **exactamente** los nombres que genera el panel. DirectAdmin suele
   prefijar: `admin_labvetciudad` / usuario `admin_silavet` (no `silavet` suelto
   ni `lb_labvetciudad` si el panel no te deja).
3. Esos tres valores (base, usuario, clave) van **iguales** en
   `config.env` (`MYSQL_*`) y en `env.emergencia` (`DB_*`).
4. Host: `127.0.0.1`. El dump todavía no se importa.

Prueba:

```bash
mysql -h 127.0.0.1 -u ADMIN_USUARIO -p -e "SHOW DATABASES;"
```

### Paso D — Config y overlay (secretos, no van a git)

```bash
cd /home/admin/public_html/silavet/alqu

cp -n scripts/emergencia/config.example.env scripts/emergencia/config.env
cp -n scripts/emergencia/env.emergencia.example scripts/emergencia/env.emergencia
chmod 600 scripts/emergencia/config.env scripts/emergencia/env.emergencia
nano scripts/emergencia/config.env
nano scripts/emergencia/env.emergencia
```

`config.env` (valores de este VPS):

```bash
PROYECTO=alqu
APP_DIR=/home/admin/public_html/silavet/alqu
WEB_USER=admin
WEB_GROUP=admin
GIT_URL=https://github.com/sergiorassino/silavet.git
GIT_BRANCH=main
PHP_BIN=/usr/local/php83/bin/php
ENV_EMERGENCIA_FILE=/home/admin/public_html/silavet/alqu/scripts/emergencia/env.emergencia
S3_BUCKET=sistesco
S3_PREFIX_DUMPS=backupDedicdo/backupHora
S3_PREFIX_ARCHIVOS=backupDedicado/archivos
S3_DUMP_FILTER=labvetciudad
AWS_REGION=
AWS_BIN=
MYSQL_HOST=127.0.0.1
MYSQL_PORT=3306
MYSQL_USER=admin_silavet          # el que creó DirectAdmin
MYSQL_PASSWORD=...
MYSQL_DATABASE=admin_labvetciudad # el que creó DirectAdmin
```

`env.emergencia` (pisa el `.env` de producción al restaurar):

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sistemasescolares4.com.ar/silavet/alqu

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=admin_labvetciudad
DB_USERNAME=admin_silavet
DB_PASSWORD=...

SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=
```

Sin `__CAMBIAR__`. `APP_URL` sin barra final. `SESSION_DOMAIN` vacío (no
`null`).

### Paso E — RewriteBase (subcarpeta)

```bash
nano /home/admin/public_html/silavet/alqu/.htaccess
```

Descomentá y dejá:

```apache
RewriteBase /silavet/alqu/
```

El document root de DirectAdmin sigue siendo `public_html` del dominio. Laravel
está en la subcarpeta; el `.htaccess` de la raíz del lab reescribe a `public/`
([09-despliegue-sin-public-en-url.md](09-despliegue-sin-public-en-url.md)).

En el panel: PHP del dominio = la misma versión que `PHP_BIN`. `mod_rewrite`
activo (lo está por defecto).

### Paso F — preparar (todavía sin bajar S3 ni importar BD)

```bash
export PATH="/usr/local/php83/bin:$HOME/bin:$PATH"
cd /home/admin/public_html/silavet/alqu
bash scripts/emergencia/diagnostico.sh
bash scripts/emergencia/preparar-vps.sh
```

Listo si termina sin `ERROR` (puede avisar AWS o placeholders). Crea `vendor/`,
carpetas de laboratorio y permisos como `admin`. **No** pongas cron de sync en
este VPS.

Si el clone aún no existía, `preparar-vps.sh` clona `GIT_URL` en `APP_DIR`.
Si `APP_DIR` ya tiene archivos y no es un git, el script se niega: o vaciá la
carpeta o cloná a mano antes.

### Paso G — Ensayo de restauración (cuando A–F están OK)

```bash
bash scripts/emergencia/restaurar.sh --dry-run
# si el dry-run lista dump y archivos:
bash scripts/emergencia/restaurar.sh --yes
```

Esto **sí** pisa la base local y los archivos de laboratorio. No toca
Hostinger. Para probar sin cambiar el DNS de producción: el dominio ya apunta
a este VPS, o usá `/etc/hosts` en tu PC.

---

## 3.2 VPS genérico (sin DirectAdmin)

Solo si el de emergencia **no** es DirectAdmin: clone en `/var/www/silavet`,
usuario `www-data`, config en `/etc/silavet/`. El flujo es el mismo
(`preparar-vps.sh` una vez, `restaurar.sh` a demanda).

---

## 4. Cuando cae producción

En el VPS de emergencia (DirectAdmin, como `admin`):

```bash
export PATH="/usr/local/php83/bin:$HOME/bin:$PATH"
cd /home/admin/public_html/silavet/alqu
bash scripts/emergencia/restaurar.sh
# o sin pregunta:
bash scripts/emergencia/restaurar.sh --yes
```

Hace, en orden: `git pull` → `composer install --no-dev` → `s3 sync` de
archivos → overlay de `.env` → importa el dump **más reciente** de
`S3_PREFIX_DUMPS` que coincida con `S3_DUMP_FILTER` → caches Artisan y
`lb:migrate-legacy --force`.

Tiempo: minutos de git/composer/archivos + lo que tarde el import MySQL
(eso suele ser lo lento).

Después: apuntar DNS o usar la URL de emergencia. El laboratorio queda con
datos de **como máximo ~1 hora** de atraso (el último dump + el espejo de
archivos de ese mismo ciclo).

Prueba controlada (sin cortar producción): `--dry-run`, o una restauración
real sobre el VPS de reserva y entrar por su URL.

---

## 5. Scripts

| Script | Servidor | Frecuencia |
|--------|----------|------------|
| `scripts/emergencia/respaldar-archivos.sh` | Producción | Horario (después del dump) |
| `scripts/emergencia/diagnostico.sh` | Emergencia | Cuando algo falla |
| `scripts/emergencia/preparar-vps.sh` | Emergencia | Una vez |
| `scripts/emergencia/restaurar.sh` | Emergencia | Solo ante caída (o ensayo) |

Config: `scripts/emergencia/config.env` o `/etc/silavet/config.env`  
Overlay: `scripts/emergencia/env.emergencia` o `/etc/silavet/env.emergencia`  
Override de ruta: variable `SILAVET_EMERGENCIA_CONF`.

---

## 6. Qué no hacer

- Cron horario en el VPS de emergencia (git pull, sync o import).
- Subir `vendor/` o todo el proyecto a S3.
- Restaurar el `.env` de producción sin overlay (`APP_URL` / `DB_HOST` del
  servidor caído).
- Usar un `S3_DUMP_FILTER` que coincida con dumps de **otro** proyecto del
  mismo prefijo.
- En DirectAdmin: `extension=pdo_mysql` (u otras) en el `php.ini` CLI; ni
  `php scripts/emergencia/preparar-vps.sh` (es bash).
- Correr `preparar-vps.sh` como **root** (los archivos quedarían fuera del
  usuario `admin` y PHP-FPM no escribe `storage/`).

---

## 7. Archivos de código

- `scripts/emergencia/lib.sh`
- `scripts/emergencia/diagnostico.sh`
- `scripts/emergencia/respaldar-archivos.sh`
- `scripts/emergencia/preparar-vps.sh`
- `scripts/emergencia/restaurar.sh`
- `scripts/emergencia/config.example.env`
- `scripts/emergencia/env.emergencia.example`
