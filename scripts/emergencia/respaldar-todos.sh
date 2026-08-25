#!/usr/bin/env bash
# Producción: recorre SILAVET_ROOT/*/artisan y ejecuta respaldar.sh en cada lab.
# Un cron para todos los laboratorios del hosting.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "$SCRIPT_DIR/lib.sh"

uso() {
    cat <<'EOF'
Uso: respaldar-todos.sh [--dry-run]

Requiere SILAVET_ROOT en el config del servidor (carpeta que contiene
un subdirectorio por tenant, cada uno con artisan).
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
require_vars SILAVET_ROOT
[[ -d "$SILAVET_ROOT" ]] || die "SILAVET_ROOT no es un directorio: $SILAVET_ROOT"

shopt -s nullglob
labs=("$SILAVET_ROOT"/*/artisan)
shopt -u nullglob
[[ ${#labs[@]} -gt 0 ]] || die "No hay labs (*/artisan) en $SILAVET_ROOT"

extra=()
[[ "$DRY_RUN" == "1" ]] && extra+=(--dry-run)

n=0
for artisan in "${labs[@]}"; do
    dir="$(dirname "$artisan")"
    script="$dir/scripts/emergencia/respaldar.sh"
    if [[ ! -f "$script" ]]; then
        log "AVISO: sin respaldar.sh, omitido $dir"
        continue
    fi
    log "==== respaldo $dir ===="
    /bin/bash "$script" "${extra[@]}"
    n=$((n + 1))
done

log "Labs respaldados: $n"
