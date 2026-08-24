#!/usr/bin/env bash
# Producción: UNA rutina horaria — dump MySQL + archivos de laboratorio.
# No correr en el VPS de emergencia (hace falta HABILITAR_RESPALDO=1).
#
#   5 * * * * /bin/bash /ruta/silavet/scripts/emergencia/respaldar.sh >> /var/log/silavet-respaldo.log 2>&1
#
# Desactivar el cron viejo de dumps el mismo día.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "$SCRIPT_DIR/lib.sh"

SOLO_DUMP=0
SOLO_ARCHIVOS=0

uso() {
    cat <<'EOF'
Uso: respaldar.sh [--dry-run] [--solo-dump] [--solo-archivos]

En el hosting de producción (HABILITAR_RESPALDO=1):
  1. mysqldump de la BD del .env → s3://…/backupHora/lN_{PROYECTO}_….sql.gz
  2. espejo incremental de archivos de laboratorio (sin --delete)

Opciones:
  --dry-run         no sube nada
  --solo-dump       solo el .sql.gz
  --solo-archivos   solo el espejo (equivalente al script anterior)
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --dry-run) DRY_RUN=1 ;;
        --solo-dump) SOLO_DUMP=1 ;;
        --solo-archivos) SOLO_ARCHIVOS=1 ;;
        -h|--help) uso; exit 0 ;;
        *) die "Opción desconocida: $1" ;;
    esac
    shift
done

[[ "$SOLO_DUMP" == "1" && "$SOLO_ARCHIVOS" == "1" ]] && die "No combines --solo-dump y --solo-archivos."

cargar_config
require_vars PROYECTO APP_DIR S3_BUCKET
require_aws
require_cmd flock
es_laravel_dir "$APP_DIR" || die "APP_DIR no parece Laravel: $APP_DIR"
[[ "${HABILITAR_RESPALDO:-}" == "1" ]] || die "HABILITAR_RESPALDO=1 en config.env (solo producción). Si esto es el VPS de emergencia, no ejecutes respaldar.sh."

LOCK="/tmp/silavet-${PROYECTO}-respaldar.lock"
exec 9>"$LOCK"
flock -n 9 || die "Ya hay un respaldo en curso."

if [[ "$SOLO_ARCHIVOS" != "1" ]]; then
    require_vars S3_PREFIX_DUMPS LAB_ORDEN
    log "Inicio dump MySQL"
    respaldar_mysql_hora
fi

if [[ "$SOLO_DUMP" != "1" ]]; then
    require_vars S3_PREFIX_ARCHIVOS
    log "Inicio respaldo de archivos → $(s3_uri_archivos)/"
    sync_archivos subir
fi

log "Fin respaldo."
