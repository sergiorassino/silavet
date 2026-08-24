#!/usr/bin/env bash
# Funciones compartidas: respaldo incremental de archivos y restauración de emergencia.
# No ejecutar este archivo solo; lo sourcean los scripts de esta carpeta.

set -euo pipefail

_EMERGENCIA_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DRY_RUN="${DRY_RUN:-0}"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"
}

die() {
    echo "ERROR: $*" >&2
    exit 1
}

# Hosting compartido: AWS CLI suele no estar en PATH. Usar AWS_BIN en config.env.
aws() {
    local bin="${AWS_BIN:-aws}"
    command "$bin" "$@"
}

require_aws() {
    local bin="${AWS_BIN:-aws}"
    if [[ "$bin" == */* || "$bin" == ./* ]]; then
        [[ -x "$bin" ]] || die "AWS_BIN no es ejecutable: $bin"
        return 0
    fi
    type -P aws >/dev/null 2>&1 || die "No está instalado: aws. En Hostinger/shared instalalo en tu home y poné AWS_BIN en config.env (docs/13-backup-y-vps-emergencia.md §2.1)."
}

require_cmd() {
    local cmd="$1"
    command -v "$cmd" >/dev/null 2>&1 || die "No está instalado: $cmd"
}

require_vars() {
    local var
    for var in "$@"; do
        if [[ -z "${!var:-}" ]]; then
            die "Falta la variable $var en el archivo de configuración."
        fi
    done
}

# Carga /etc/silavet/config.env o scripts/emergencia/config.env (o SILAVET_EMERGENCIA_CONF).
cargar_config() {
    local candidato=""
    if [[ -n "${SILAVET_EMERGENCIA_CONF:-}" ]]; then
        candidato="$SILAVET_EMERGENCIA_CONF"
    elif [[ -f /etc/silavet/config.env ]]; then
        candidato="/etc/silavet/config.env"
    elif [[ -f "$_EMERGENCIA_DIR/config.env" ]]; then
        candidato="$_EMERGENCIA_DIR/config.env"
    fi

    [[ -n "$candidato" && -f "$candidato" ]] || die "No hay configuración. Copiá config.example.env a /etc/silavet/config.env y completá los valores."

    # shellcheck disable=SC1090
    set -a
    source "$candidato"
    set +a

    CONFIG_CARGADO="$candidato"
    log "Config: $CONFIG_CARGADO"
}

ruta_overlay() {
    echo "${ENV_EMERGENCIA_FILE:-/etc/silavet/env.emergencia}"
}

s3_uri_archivos() {
    echo "s3://${S3_BUCKET}/${S3_PREFIX_ARCHIVOS%/}/${PROYECTO}"
}

s3_uri_dumps() {
    echo "s3://${S3_BUCKET}/${S3_PREFIX_DUMPS%/}"
}

# Carpetas relativas al proyecto que no están en git (o no de forma fiable).
# Array (no process substitution): en Hostinger no existe /dev/fd.
RUTAS_ARCHIVOS_LABORATORIO=(
    public/REPOSITORIO
    public/entorno
    public/adjuntos
    afipSE/cert
    storage/fonts
    storage/app/AUTOANALIZADORES
)

es_laravel_dir() {
    local dir="$1"
    [[ -f "$dir/artisan" && -f "$dir/composer.json" ]]
}

asegurar_directorios_laboratorio() {
    local dir="$1"
    mkdir -p \
        "$dir/public/REPOSITORIO" \
        "$dir/public/entorno" \
        "$dir/public/adjuntos" \
        "$dir/afipSE/cert" \
        "$dir/storage/fonts" \
        "$dir/storage/app/AUTOANALIZADORES" \
        "$dir/storage/app/livewire-tmp" \
        "$dir/storage/framework/cache" \
        "$dir/storage/framework/sessions" \
        "$dir/storage/framework/views" \
        "$dir/storage/logs" \
        "$dir/bootstrap/cache"
}

aplicar_permisos() {
    local dir="$1"
    local user="${WEB_USER:-www-data}"
    local group="${WEB_GROUP:-$user}"

    if [[ "$DRY_RUN" == "1" ]]; then
        log "[dry-run] chown ${user}:${group} storage bootstrap/cache public/{REPOSITORIO,entorno,adjuntos} afipSE/cert"
        return 0
    fi

    if ! id "$user" >/dev/null 2>&1; then
        log "AVISO: el usuario web '$user' no existe; no se cambia el dueño."
        return 0
    fi

    chown -R "${user}:${group}" \
        "$dir/storage" \
        "$dir/bootstrap/cache" \
        "$dir/public/REPOSITORIO" \
        "$dir/public/entorno" \
        "$dir/public/adjuntos" \
        "$dir/afipSE/cert" \
        2>/dev/null || log "AVISO: no se pudieron ajustar todos los permisos (¿hace falta root?)."
}

sync_archivos() {
    local sentido="$1" # subir | bajar
    local origen destino
    local uri
    [[ "$sentido" == "subir" || "$sentido" == "bajar" ]] || die "sentido inválido: $sentido (usar subir o bajar)"
    uri="$(s3_uri_archivos)"
    local dry=()
    [[ "$DRY_RUN" == "1" ]] && dry=(--dryrun)

    require_aws
    require_vars PROYECTO APP_DIR S3_BUCKET S3_PREFIX_ARCHIVOS
    es_laravel_dir "$APP_DIR" || die "APP_DIR no parece un proyecto Laravel: $APP_DIR"

    local extra=()
    if [[ -n "${AWS_REGION:-}" ]]; then
        extra+=(--region "$AWS_REGION")
    fi

    local rel
    for rel in "${RUTAS_ARCHIVOS_LABORATORIO[@]}"; do
        [[ -z "$rel" ]] && continue
        if [[ "$sentido" == "subir" ]]; then
            [[ -e "$APP_DIR/$rel" ]] || { log "Omitido (no existe): $rel"; continue; }
            origen="$APP_DIR/$rel"
            destino="${uri}/${rel}"
        else
            origen="${uri}/${rel}"
            destino="$APP_DIR/$rel"
            mkdir -p "$destino"
        fi
        log "s3 sync $origen → $destino"
        aws s3 sync "$origen" "$destino" \
            "${extra[@]}" \
            "${dry[@]}" \
            --exclude "*.tmp" \
            --exclude ".DS_Store" \
            --exclude "Thumbs.db"
    done

    if [[ "$sentido" == "subir" ]]; then
        if [[ -f "$APP_DIR/.env" ]]; then
            log "s3 cp .env → ${uri}/.env"
            aws s3 cp "$APP_DIR/.env" "${uri}/.env" "${extra[@]}" "${dry[@]}"
        else
            log "AVISO: no hay .env para subir."
        fi
    else
        log "s3 cp ${uri}/.env → $APP_DIR/.env"
        aws s3 cp "${uri}/.env" "$APP_DIR/.env" "${extra[@]}" "${dry[@]}"
    fi
}

# El dump más reciente del proyecto (por fecha de modificación en S3).
clave_dump_mas_reciente() {
    require_aws
    require_vars S3_BUCKET S3_PREFIX_DUMPS S3_DUMP_FILTER

    local extra=()
    if [[ -n "${AWS_REGION:-}" ]]; then
        extra+=(--region "$AWS_REGION")
    fi

    local listado linea clave
    listado="$(aws s3 ls "$(s3_uri_dumps)/" --recursive "${extra[@]}")" \
        || die "Falló aws s3 ls en $(s3_uri_dumps)/ (credenciales, región o prefijo)."

    linea="$(
        echo "$listado" \
            | grep -F "$S3_DUMP_FILTER" \
            | grep -E '\.(sql|sql\.gz|gz)$' \
            | sort \
            | tail -n 1
    )" || true

    [[ -n "$linea" ]] || die "No se encontró ningún dump en $(s3_uri_dumps)/ que coincida con S3_DUMP_FILTER='$S3_DUMP_FILTER'."

    clave="$(echo "$linea" | awk '{ $1=$2=$3=""; sub(/^ +/, ""); print }')"
    [[ -n "$clave" ]] || die "No se pudo interpretar la clave S3 del dump: $linea"
    echo "$clave"
}

mysql_cnf_tmp() {
    require_vars MYSQL_HOST MYSQL_USER MYSQL_DATABASE
    local tmp
    tmp="$(mktemp)"
    chmod 600 "$tmp"
    cat >"$tmp" <<EOF
[client]
host=${MYSQL_HOST}
port=${MYSQL_PORT:-3306}
user=${MYSQL_USER}
password=${MYSQL_PASSWORD:-}
EOF
    echo "$tmp"
}

# Pisa en .env las claves definidas en el overlay del VPS (APP_URL, DB_*, etc.).
aplicar_overlay_env() {
    local env_file="$1"
    local overlay
    overlay="$(ruta_overlay)"

    [[ -f "$env_file" ]] || die "No existe $env_file (¿falló la descarga de S3?)."
    [[ -f "$overlay" ]] || die "No existe el overlay $overlay. Ejecutá primero preparar-vps.sh y completá env.emergencia."

    if grep -q '__CAMBIAR__' "$overlay"; then
        die "El overlay $overlay todavía tiene placeholders __CAMBIAR__. Completalo antes de restaurar."
    fi

    if [[ "$DRY_RUN" == "1" ]]; then
        log "[dry-run] aplicaría overlay $overlay sobre $env_file"
        return 0
    fi

    local tmp key line
    tmp="$(mktemp)"
    cp "$env_file" "$tmp"

    while IFS= read -r line || [[ -n "$line" ]]; do
        [[ "$line" =~ ^[[:space:]]*# ]] && continue
        [[ "$line" =~ ^[[:space:]]*$ ]] && continue
        key="${line%%=*}"
        [[ -n "$key" ]] || continue
        grep -vE "^${key}=" "$tmp" >"${tmp}.new"
        mv "${tmp}.new" "$tmp"
        echo "$line" >>"$tmp"
    done <"$overlay"

    mv "$tmp" "$env_file"
    log "Overlay aplicado: $overlay → $env_file"
}

git_actualizar() {
    local dir="$1"
    local branch="${GIT_BRANCH:-main}"

    [[ -d "$dir/.git" ]] || die "No hay repositorio git en $dir. Ejecutá primero preparar-vps.sh."

    if [[ "$DRY_RUN" == "1" ]]; then
        log "[dry-run] git fetch && git checkout $branch && git pull --ff-only"
        return 0
    fi

    (
        cd "$dir"
        git fetch --prune origin
        git checkout "$branch"
        git pull --ff-only origin "$branch"
    )
}

composer_instalar() {
    local dir="$1"
    require_cmd composer
    if [[ "$DRY_RUN" == "1" ]]; then
        log "[dry-run] composer install --no-dev en $dir"
        return 0
    fi
    (
        cd "$dir"
        composer install --no-dev --optimize-autoloader --no-interaction
    )
}

laravel_post_restaurar() {
    local dir="$1"
    local php_bin="${PHP_BIN:-php}"

    if [[ "$DRY_RUN" == "1" ]]; then
        log "[dry-run] artisan config/view/route:clear + lb:migrate-legacy --force"
        return 0
    fi

    rm -f "$dir/public/hot"
    (
        cd "$dir"
        "$php_bin" artisan config:clear
        "$php_bin" artisan view:clear
        "$php_bin" artisan route:clear
        "$php_bin" artisan cache:clear || true
        "$php_bin" artisan lb:migrate-legacy --force || log "AVISO: lb:migrate-legacy falló o no aplica; revisá a mano."
    )
}

confirmar() {
    local mensaje="$1"
    if [[ "${SI:-0}" == "1" ]]; then
        return 0
    fi
    echo
    echo "$mensaje"
    read -r -p "¿Continuar? [y/N] " resp
    [[ "$resp" == "y" || "$resp" == "Y" || "$resp" == "si" || "$resp" == "sí" ]] || die "Cancelado."
}

php_ok() {
    local php_bin="${PHP_BIN:-php}"
    require_cmd "$php_bin"
    "$php_bin" -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' \
        || die "Se necesita PHP 8.2 o superior ($php_bin)."

    local ext
    for ext in pdo_mysql mbstring xml curl zip tokenizer fileinfo ctype json; do
        "$php_bin" -m | grep -qi "^${ext}$" || die "Falta la extensión PHP: $ext"
    done
    if ! "$php_bin" -m | grep -qi '^gd$'; then
        log "AVISO: no está la extensión gd (algunos informes/imágenes pueden fallar)."
    fi
}

verificar_preparacion() {
    local overlay
    overlay="$(ruta_overlay)"

    php_ok
    require_cmd git
    require_cmd composer
    require_cmd mysql
    require_aws

    es_laravel_dir "$APP_DIR" || die "APP_DIR no es un proyecto Laravel: $APP_DIR"
    [[ -d "$APP_DIR/.git" ]] || die "No hay .git en $APP_DIR."
    [[ -f "$overlay" ]] || die "Falta overlay $overlay"

    if grep -q '__CAMBIAR__' "$overlay"; then
        log "AVISO: $overlay todavía tiene placeholders. Completalo antes de restaurar."
    else
        log "Overlay $overlay sin placeholders."
    fi

    if aws sts get-caller-identity >/dev/null 2>&1; then
        log "AWS CLI autenticado."
    else
        log "AVISO: aws sts get-caller-identity falló. Configurá credenciales antes de restaurar."
    fi

    log "VPS listo para restaurar a demanda (sin sync horario en este servidor)."
}
