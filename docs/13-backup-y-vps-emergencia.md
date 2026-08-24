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

Stack: PHP 8.2+, extensiones (`pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`,
`gd` recomendada), MySQL, Apache o nginx, Composer, Git, AWS CLI, `flock`.

Clave de deploy de Git y credenciales AWS (mismo bucket `sistesco`, permiso de
lectura de dumps + archivos; en producción además escritura del prefijo
`archivos/`).

```bash
# 1) Clone (rama de producción)
sudo mkdir -p /var/www
sudo git clone -b main git@github.com:USUARIO/SILAVET.git /var/www/silavet

# 2) Config del operador (no va en git)
sudo mkdir -p /etc/silavet
sudo cp /var/www/silavet/scripts/emergencia/config.example.env /etc/silavet/config.env
sudo cp /var/www/silavet/scripts/emergencia/env.emergencia.example /etc/silavet/env.emergencia
sudo chmod 600 /etc/silavet/config.env /etc/silavet/env.emergencia
sudo nano /etc/silavet/config.env
sudo nano /etc/silavet/env.emergencia
```

En `env.emergencia` reemplazar todos los `__CAMBIAR__`: `APP_URL` del host de
emergencia, `DB_*` del MySQL **local**. El resto del `.env` de producción
(APP_KEY, mail, tenant, etc.) llega desde S3 en la restauración.

```bash
sudo bash /var/www/silavet/scripts/emergencia/preparar-vps.sh
```

Eso instala dependencias PHP, crea carpetas de escritura y **no** importa la
base ni baja archivos. **No** agregar cron de sync ni de dump en este VPS.

Virtual host: document root = carpeta donde está `artisan` (padre de `public/`),
igual que [09-despliegue-sin-public-en-url.md](09-despliegue-sin-public-en-url.md).
`APP_URL` del overlay debe ser exactamente la URL pública de emergencia.

Usuario MySQL local con permiso sobre `MYSQL_DATABASE` (puede estar vacía;
`restaurar.sh` hace `CREATE DATABASE IF NOT EXISTS`).

---

## 4. Cuando cae producción

En el VPS de emergencia:

```bash
sudo bash /var/www/silavet/scripts/emergencia/restaurar.sh
# o sin pregunta:
sudo bash /var/www/silavet/scripts/emergencia/restaurar.sh --yes
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
| `scripts/emergencia/preparar-vps.sh` | Emergencia | Una vez |
| `scripts/emergencia/restaurar.sh` | Emergencia | Solo ante caída (o ensayo) |

Config: `/etc/silavet/config.env`  
Overlay: `/etc/silavet/env.emergencia`  
Override de ruta: variable `SILAVET_EMERGENCIA_CONF`.

---

## 6. Qué no hacer

- Cron horario en el VPS de emergencia (git pull, sync o import).
- Subir `vendor/` o todo el proyecto a S3.
- Restaurar el `.env` de producción sin overlay (`APP_URL` / `DB_HOST` del
  servidor caído).
- Usar un `S3_DUMP_FILTER` que coincida con dumps de **otro** proyecto del
  mismo prefijo.

---

## 7. Archivos de código

- `scripts/emergencia/lib.sh`
- `scripts/emergencia/respaldar-archivos.sh`
- `scripts/emergencia/preparar-vps.sh`
- `scripts/emergencia/restaurar.sh`
- `scripts/emergencia/config.example.env`
- `scripts/emergencia/env.emergencia.example`
