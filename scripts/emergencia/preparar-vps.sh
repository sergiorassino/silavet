#!/usr/bin/env bash
# VPS de emergencia: preparación UNA VEZ. No instala cron horario.
# Deja código, Composer y overlay listos; los datos se bajan recién con restaurar.sh.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "$SCRIPT_DIR/lib.sh"

uso() {
    cat <<'EOF'
Uso: preparar-vps.sh [--dry-run]

Una sola vez en vpsEmergencia:
  - clona (o actualiza) el repo
  - composer install --no-dev
  - crea carpetas de laboratorio
  - instala /etc/silavet/env.emergencia si no existe
  - verifica PHP, MySQL client, git, aws, composer

NO descarga dumps ni archivos de S3. NO importa la base.
Cuando caiga producción: restaurar.sh
EOF
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --dry-run) DRY_RUN=1 ;;
        -h|--help) uso; exit 0 ;;
        *) die "Opción desconocida: $1" ;;
    esac
    shift
done

cargar_config
require_vars PROYECTO APP_DIR GIT_URL GIT_BRANCH WEB_USER

php_ok
require_cmd git
require_cmd composer
require_cmd mysql
if ! command -v aws >/dev/null 2>&1; then
    log "AVISO: aws no está instalado. Hace falta para restaurar.sh."
fi

if [[ -d "$APP_DIR/.git" ]]; then
    log "Repo ya presente en $APP_DIR"
    git_actualizar "$APP_DIR"
elif [[ -e "$APP_DIR" && -n "$(ls -A "$APP_DIR" 2>/dev/null || true)" ]]; then
    die "APP_DIR existe, no está vacío y no es un clone git: $APP_DIR"
else
    log "Clonando $GIT_URL ($GIT_BRANCH) → $APP_DIR"
    if [[ "$DRY_RUN" == "1" ]]; then
        log "[dry-run] git clone --branch $GIT_BRANCH $GIT_URL $APP_DIR"
    else
        mkdir -p "$(dirname "$APP_DIR")"
        git clone --branch "$GIT_BRANCH" "$GIT_URL" "$APP_DIR"
    fi
fi

if [[ "$DRY_RUN" != "1" ]]; then
    if [[ ! -f "$APP_DIR/.env" ]]; then
        cp "$APP_DIR/.env.example" "$APP_DIR/.env"
        log "Copiado .env.example → .env (placeholder hasta restaurar.sh)."
    fi
    asegurar_directorios_laboratorio "$APP_DIR"
fi

composer_instalar "$APP_DIR"

overlay="$(ruta_overlay)"
if [[ ! -f "$overlay" ]]; then
    if [[ "$DRY_RUN" == "1" ]]; then
        log "[dry-run] instalaría $overlay"
    else
        mkdir -p "$(dirname "$overlay")"
        cp "$SCRIPT_DIR/env.emergencia.example" "$overlay"
        chmod 600 "$overlay"
        log "Creado $overlay — completá APP_URL y DB_* antes de restaurar."
    fi
else
    log "Overlay ya existe: $overlay"
fi

if [[ "$DRY_RUN" != "1" ]]; then
    aplicar_permisos "$APP_DIR"
    verificar_preparacion
fi

cat <<EOF

Preparación lista. Completá a mano si aún no lo hiciste:
  1. $overlay
  2. Credenciales AWS (\`aws configure\` o IAM role)
  3. Virtual host Apache/nginx apuntando a $APP_DIR (ver docs/13-backup-y-vps-emergencia.md)
  4. Usuario MySQL local y base vacía $MYSQL_DATABASE

Este VPS NO debe tener cron de sync horario.
Cuando caiga el servidor original:

  sudo bash $APP_DIR/scripts/emergencia/restaurar.sh

EOF
