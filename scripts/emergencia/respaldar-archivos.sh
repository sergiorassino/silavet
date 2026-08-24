#!/usr/bin/env bash
# Producción: espejo incremental en S3 de archivos de laboratorio (no git).
# Correr en el MISMO cron que el dump horario, DESPUÉS del dump de MySQL.
#
#   5 * * * * /ruta/silavet/scripts/emergencia/respaldar-archivos.sh >> /var/log/silavet-archivos.log 2>&1

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "$SCRIPT_DIR/lib.sh"

uso() {
    cat <<'EOF'
Uso: respaldar-archivos.sh [--dry-run]

Sube a S3 (espejo incremental, sin --delete):
  .env, public/REPOSITORIO, public/entorno, public/adjuntos,
  afipSE/cert, storage/fonts, storage/app/AUTOANALIZADORES

Config: /etc/silavet/config.env  (ver config.example.env)
EOF
}

SI=0
while [[ $# -gt 0 ]]; do
    case "$1" in
        --dry-run) DRY_RUN=1 ;;
        -h|--help) uso; exit 0 ;;
        *) die "Opción desconocida: $1" ;;
    esac
    shift
done

cargar_config
require_vars PROYECTO APP_DIR S3_BUCKET S3_PREFIX_ARCHIVOS
require_aws
require_cmd flock
es_laravel_dir "$APP_DIR" || die "APP_DIR no parece Laravel: $APP_DIR"

LOCK="/tmp/silavet-${PROYECTO}-respaldar-archivos.lock"
exec 9>"$LOCK"
flock -n 9 || die "Ya hay un respaldo de archivos en curso."

log "Inicio respaldo de archivos → $(s3_uri_archivos)/"
sync_archivos subir
log "Fin respaldo de archivos."
