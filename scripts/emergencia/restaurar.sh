#!/usr/bin/env bash
# VPS de emergencia: restauración a demanda (última hora).
# No está pensado para cron. Pisa la BD local y los archivos de laboratorio.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "$SCRIPT_DIR/lib.sh"

uso() {
    cat <<'EOF'
Uso: restaurar.sh [--yes] [--dry-run]

En el VPS de emergencia (tras preparar-vps.sh):
  1. git pull (rama configurada)
  2. composer install --no-dev
  3. baja el espejo de archivos desde S3
  4. aplica /etc/silavet/env.emergencia sobre .env
  5. importa el dump MySQL más reciente de backupHora
  6. limpia caches Laravel

Opciones:
  --yes       no pide confirmación
  --dry-run   muestra qué haría (aws --dryrun; no importa BD)
EOF
}

SI=0
while [[ $# -gt 0 ]]; do
    case "$1" in
        --yes|--si|-y) SI=1 ;;
        --dry-run) DRY_RUN=1 ;;
        -h|--help) uso; exit 0 ;;
        *) die "Opción desconocida: $1" ;;
    esac
    shift
done

cargar_config
require_vars PROYECTO APP_DIR S3_BUCKET S3_PREFIX_ARCHIVOS S3_PREFIX_DUMPS S3_DUMP_FILTER
require_vars MYSQL_HOST MYSQL_USER MYSQL_DATABASE GIT_BRANCH
require_cmd aws
require_cmd mysql
require_cmd flock
php_ok

if [[ "${MYSQL_PASSWORD:-}" == "__CAMBIAR__" ]]; then
    die "Completá MYSQL_PASSWORD en $CONFIG_CARGADO"
fi

es_laravel_dir "$APP_DIR" || die "APP_DIR no parece Laravel: $APP_DIR"
[[ -d "$APP_DIR/.git" ]] || die "Falta el clone. Ejecutá primero preparar-vps.sh."

LOCK="/tmp/silavet-${PROYECTO}-restaurar.lock"
exec 9>"$LOCK"
flock -n 9 || die "Ya hay una restauración en curso."

confirmar "Esto PISA la base '${MYSQL_DATABASE}' y los archivos de laboratorio en ${APP_DIR} con el último dump y el espejo S3."

log "1/6 git pull"
git_actualizar "$APP_DIR"

log "2/6 composer install"
composer_instalar "$APP_DIR"
asegurar_directorios_laboratorio "$APP_DIR"

log "3/6 archivos de laboratorio desde S3"
sync_archivos bajar

log "4/6 overlay .env de emergencia"
aplicar_overlay_env "$APP_DIR/.env"

log "5/6 dump MySQL más reciente"
clave="$(clave_dump_mas_reciente)"
log "Dump elegido: s3://${S3_BUCKET}/${clave}"

if [[ "$DRY_RUN" == "1" ]]; then
    log "[dry-run] no se descarga ni importa el dump."
else
    tmp_dump="$(mktemp /tmp/silavet-dump.XXXXXX)"
    tmp_cnf="$(mysql_cnf_tmp)"
    cleanup() {
        rm -f "$tmp_dump" "$tmp_cnf"
    }
    trap cleanup EXIT

    extra=()
    if [[ -n "${AWS_REGION:-}" ]]; then
        extra+=(--region "$AWS_REGION")
    fi
    aws s3 cp "s3://${S3_BUCKET}/${clave}" "$tmp_dump" "${extra[@]}"

    mysql --defaults-extra-file="$tmp_cnf" -e \
        "CREATE DATABASE IF NOT EXISTS \`${MYSQL_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

    if [[ "$clave" == *.gz ]]; then
        gzip -dc "$tmp_dump" | mysql --defaults-extra-file="$tmp_cnf" "$MYSQL_DATABASE"
    else
        mysql --defaults-extra-file="$tmp_cnf" "$MYSQL_DATABASE" <"$tmp_dump"
    fi
    log "Importación MySQL terminada."
fi

log "6/6 permisos y caches"
aplicar_permisos "$APP_DIR"
laravel_post_restaurar "$APP_DIR"

log "Restauración lista."
echo "Probá la APP_URL definida en $(ruta_overlay)."
echo "Si el virtual host o el DNS de emergencia ya apuntan acá, el laboratorio debería responder con datos de la última hora."
