#!/usr/bin/env bash
# Informe de requisitos del VPS de emergencia (DirectAdmin / AlmaLinux).
# No cambia nada. Pegá la salida si algo falla.

set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck disable=SC1091
source "$SCRIPT_DIR/lib.sh"
set +e
set -u

ok() { echo "  OK   $*"; }
fail() { echo "  FAIL $*"; }
info() { echo "  INFO $*"; }

echo "=== SILAVET emergencia — diagnóstico ==="
echo "fecha: $(date -Iseconds 2>/dev/null || date)"
echo "host:  $(hostname)  user: $(whoami)"
echo "pwd:   $(pwd)"
echo

echo "--- PHP instalados ---"
shopt -s nullglob
phps=(/usr/local/php*/bin/php /usr/bin/php)
shopt -u nullglob
if [[ ${#phps[@]} -eq 0 ]]; then
    fail "no hay /usr/local/php*/bin/php ni /usr/bin/php"
fi

exts=(pdo_mysql mbstring xml curl zip tokenizer fileinfo ctype json gd)
for bin in "${phps[@]}"; do
    [[ -x "$bin" ]] || continue
    ver="$("$bin" -r 'echo PHP_VERSION;' 2>/dev/null || echo "?")"
    ini="$("$bin" -r 'echo php_ini_loaded_file() ?: "(ninguno)";' 2>/dev/null || echo "?")"
    echo "BIN $bin"
    echo "    version=$ver"
    echo "    ini=$ini"
    for ext in "${exts[@]}"; do
        if "$bin" -r "exit(extension_loaded('$ext') ? 0 : 1);" 2>/dev/null; then
            ok "$ext"
        else
            fail "$ext no cargada"
        fi
    done
    warns="$("$bin" -r 'echo 1;' 2>&1 >/dev/null | grep -c 'Unable to load dynamic library' || true)"
    if [[ "${warns:-0}" -gt 0 ]]; then
        fail "$warns warning(s) Unable to load dynamic library — sacá extension=... del ini CLI"
        "$bin" -r 'echo 1;' 2>&1 >/dev/null | grep 'Unable to load dynamic library' | head -n 8 | sed 's/^/    /'
        if [[ -n "$ini" && "$ini" != "(ninguno)" && -f "$ini" ]]; then
            echo "    líneas extension= en $ini:"
            grep -nE '^[[:space:]]*extension=' "$ini" | sed 's/^/    /' || true
        fi
    fi
    echo
done

echo "--- PATH php / composer / git / aws / mysql / flock ---"
for cmd in php composer git aws mysql flock gzip; do
    p="$(command -v "$cmd" 2>/dev/null || true)"
    if [[ -n "$p" ]]; then
        ok "$cmd → $p"
    else
        fail "$cmd no está en PATH"
    fi
done
echo

echo "--- config.env ---"
if [[ -n "${SILAVET_EMERGENCIA_CONF:-}" && -f "${SILAVET_EMERGENCIA_CONF}" ]]; then
    ok "SILAVET_EMERGENCIA_CONF=$SILAVET_EMERGENCIA_CONF"
elif [[ -f /etc/silavet/config.env ]]; then
    ok "/etc/silavet/config.env"
elif [[ -f "$SCRIPT_DIR/config.env" ]]; then
    ok "$SCRIPT_DIR/config.env"
else
    fail "no hay config.env (copiá config.example.env → scripts/emergencia/config.env)"
    echo "=== fin (sin config) ==="
    exit 0
fi

cargar_config
prepend_php_path
echo "    PROYECTO=$PROYECTO"
echo "    APP_DIR=$APP_DIR"
echo "    PHP_BIN=${PHP_BIN:-php}"
echo "    WEB_USER=${WEB_USER:-}  WEB_GROUP=${WEB_GROUP:-}"
echo "    GIT_URL=${GIT_URL:-}  GIT_BRANCH=${GIT_BRANCH:-}"
echo "    S3_BUCKET=$S3_BUCKET"
echo "    S3_PREFIX_DUMPS=$S3_PREFIX_DUMPS  S3_DUMP_FILTER=$S3_DUMP_FILTER"
echo "    S3_PREFIX_ARCHIVOS=$S3_PREFIX_ARCHIVOS"
echo "    MYSQL_HOST=$MYSQL_HOST  MYSQL_USER=$MYSQL_USER  MYSQL_DATABASE=$MYSQL_DATABASE"
if [[ "${MYSQL_PASSWORD:-}" == "__CAMBIAR__" || -z "${MYSQL_PASSWORD:-}" ]]; then
    fail "MYSQL_PASSWORD aún es placeholder"
else
    ok "MYSQL_PASSWORD definido (no se muestra)"
fi
echo

echo "--- overlay ---"
overlay="$(ruta_overlay)"
if [[ -f "$overlay" ]]; then
    ok "$overlay"
    if grep -q '__CAMBIAR__' "$overlay"; then
        fail "todavía hay __CAMBIAR__ en el overlay"
        grep '__CAMBIAR__' "$overlay" | sed 's/^/    /'
    else
        ok "sin placeholders"
    fi
    grep -E '^(APP_URL|DB_HOST|DB_DATABASE|DB_USERNAME)=' "$overlay" | sed 's/^/    /' || true
else
    fail "no existe $overlay"
fi
echo

echo "--- proyecto ---"
if [[ -d "$APP_DIR" ]]; then
    ok "APP_DIR existe"
    [[ -f "$APP_DIR/artisan" ]] && ok "artisan" || fail "falta artisan"
    [[ -d "$APP_DIR/.git" ]] && ok ".git" || fail "no es un clone git"
    [[ -d "$APP_DIR/vendor" ]] && ok "vendor/" || fail "falta vendor (preparar-vps / composer install)"
    ht="$APP_DIR/.htaccess"
    if [[ -f "$ht" ]]; then
        if grep -qE '^[[:space:]]*RewriteBase[[:space:]]+/silavet/alqu' "$ht"; then
            ok "RewriteBase /silavet/alqu"
        else
            fail "descomentá RewriteBase /silavet/alqu/ en $ht"
        fi
    else
        fail "falta $ht"
    fi
else
    fail "APP_DIR no existe: $APP_DIR"
fi
echo

echo "--- PHP_BIN del config ---"
php_bin="${PHP_BIN:-php}"
if command -v "$php_bin" >/dev/null 2>&1; then
    if php_ext_cargada "$php_bin" pdo_mysql && php_ext_cargada "$php_bin" mbstring && php_ext_cargada "$php_bin" curl; then
        ok "$php_bin carga pdo_mysql + mbstring + curl"
    else
        fail "$php_bin no carga las extensiones de Laravel. Este es el bloqueo de preparar-vps.sh."
    fi
else
    fail "PHP_BIN no ejecutable: $php_bin"
fi
echo

echo "--- AWS ---"
aws_ok=0
if [[ -n "${AWS_BIN:-}" ]]; then
    if [[ -x "$AWS_BIN" ]]; then
        aws_ok=1
        ok "AWS_BIN=$AWS_BIN"
    else
        fail "AWS_BIN no ejecutable: $AWS_BIN"
    fi
elif type -P aws >/dev/null 2>&1; then
    aws_ok=1
    ok "aws en PATH"
else
    fail "aws no instalado (AWS_BIN o PATH)"
fi
if [[ "$aws_ok" == "1" ]]; then
    if aws sts get-caller-identity >/dev/null 2>&1; then
        ok "aws autenticado"
        aws s3 ls "s3://${S3_BUCKET}/${S3_PREFIX_ARCHIVOS%/}/${PROYECTO}/" 2>&1 | head -n 5 | sed 's/^/    /' || fail "no listó archivos del proyecto"
        aws s3 ls "s3://${S3_BUCKET}/${S3_PREFIX_DUMPS%/}/" 2>&1 | grep -F "$S3_DUMP_FILTER" | tail -n 3 | sed 's/^/    /' || fail "ningún dump con S3_DUMP_FILTER=$S3_DUMP_FILTER"
    else
        fail "aws sts get-caller-identity falló (credenciales)"
    fi
fi
echo

echo "--- MySQL local ---"
if command -v mysql >/dev/null 2>&1; then
    if [[ "${MYSQL_PASSWORD:-}" != "__CAMBIAR__" && -n "${MYSQL_PASSWORD:-}" ]]; then
        cnf="$(mysql_cnf_tmp)"
        if mysql --defaults-extra-file="$cnf" -e "SELECT 1;" >/dev/null 2>&1; then
            ok "conexión MySQL $MYSQL_USER@$MYSQL_HOST"
            mysql --defaults-extra-file="$cnf" -e "SHOW DATABASES LIKE '${MYSQL_DATABASE}';" 2>/dev/null | sed 's/^/    /'
        else
            fail "no conecta con MYSQL_* de config.env (creá usuario/base en DirectAdmin → MySQL Management)"
        fi
        rm -f "$cnf"
    else
        info "saltado: MYSQL_PASSWORD placeholder"
    fi
else
    fail "no está el cliente mysql"
fi

echo
echo "=== fin ==="
echo "Si PHP_BIN tiene FAIL de extensiones: docs/13-backup-y-vps-emergencia.md §3.1 (DirectAdmin)."
