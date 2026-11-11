#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════
# Arcillas MX · despliegue a Hostinger (SSH + rsync)
#
#   ./tools/deploy.sh              # código + .env + permisos
#   ./tools/deploy.sh --status     # solo consulta el estado remoto
#   ./tools/deploy.sh --seed       # además siembra el catálogo demo
#
# Requiere: rsync, ssh y .deploy.env (credenciales SSH, no se versiona).
# ═══════════════════════════════════════════════════════════════════════
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

MODO="deploy"
for arg in "$@"; do
  case "$arg" in
    --status) MODO="status" ;;
    --seed)   MODO="seed" ;;
    --help|-h) sed -n '2,12p' "$0"; exit 0 ;;
    *) echo "Opción desconocida: $arg" >&2; exit 1 ;;
  esac
done

[ -f .deploy.env ] || { echo "✖ Falta .deploy.env (credenciales SSH)." >&2; exit 1; }
# shellcheck disable=SC1091
source .deploy.env

: "${HOSTINGER_HOST:?}" "${HOSTINGER_PORT:?}" "${HOSTINGER_USER:?}" "${HOSTINGER_PASS:?}" "${REMOTE_DIR:?}"

ASKPASS="$(mktemp "${TMPDIR:-/tmp}/arcillas-askpass.XXXXXX")"
cleanup() { rm -f "$ASKPASS"; }
trap cleanup EXIT
printf '#!/bin/sh\nprintf "%%s\\n" %q\n' "$HOSTINGER_PASS" > "$ASKPASS"
chmod 700 "$ASKPASS"
export SSH_ASKPASS="$ASKPASS" SSH_ASKPASS_REQUIRE=force DISPLAY="${DISPLAY:-:0}"

SSH_OPTS=(-T -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \
  -o ConnectTimeout=30 -o PreferredAuthentications=password -o PubkeyAuthentication=no \
  -p "$HOSTINGER_PORT")
RSYNC_SSH="ssh -T -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null -o ConnectTimeout=30 -o PreferredAuthentications=password -o PubkeyAuthentication=no -p ${HOSTINGER_PORT}"

remote() {
  ssh "${SSH_OPTS[@]}" "${HOSTINGER_USER}@${HOSTINGER_HOST}" "$@" < /dev/null
}

REMOTE="${HOSTINGER_USER}@${HOSTINGER_HOST}:${REMOTE_DIR}"
echo "▶ Arcillas MX → ${SITE_URL:-https://$HOSTINGER_HOST}"
echo "  Host: ${HOSTINGER_HOST}:${HOSTINGER_PORT} · ${REMOTE_DIR}"

if [ "$MODO" = "status" ]; then
  remote "cd '${REMOTE_DIR}' && php tools/migrate.php --status 2>&1 | tail -20"
  exit 0
fi

if [ "$MODO" = "seed" ]; then
  remote "cd '${REMOTE_DIR}' && php database/seed.php --force"
  exit 0
fi

# ─── 1. Comprobaciones locales ────────────────────────────────────────
for dir in app config database public tools; do
  [ -d "$dir" ] || { echo "✖ Falta el directorio $dir" >&2; exit 1; }
done
[ -f .env.production ] || { echo "✖ Falta .env.production" >&2; exit 1; }

echo "▶ RSYNC public/ → public_html/"
rsync -az --delete --human-readable \
  --exclude '.DS_Store' --exclude '.tmp*' --exclude '.env*' \
  -e "$RSYNC_SSH" public/ "$REMOTE/"

echo "▶ RSYNC código de aplicación"
for dir in app config database tools docs; do
  [ -d "$dir" ] || continue
  rsync -az --delete --human-readable \
    --exclude '.DS_Store' --exclude '.tmp*' \
    -e "$RSYNC_SSH" "$dir/" "$REMOTE/$dir/"
done
rsync -az --human-readable -e "$RSYNC_SSH" database/schema.sql README.md "$REMOTE/" 2>/dev/null || true

echo "▶ Subiendo .env de producción (600)"
ssh "${SSH_OPTS[@]}" "${HOSTINGER_USER}@${HOSTINGER_HOST}" "cat > '${REMOTE_DIR}/.env' && chmod 600 '${REMOTE_DIR}/.env'" < .env.production

echo "▶ Directorios de escritura y permisos"
remote "cd '${REMOTE_DIR}' && mkdir -p storage/logs storage/cache storage/uploads public/uploads/materiales public/uploads && find . -type d -not -path './storage/*' -not -path './public/uploads*' -exec chmod 755 {} \; && find . -type f -not -name '.env' -not -path './storage/*' -exec chmod 644 {} \; && chmod -R 775 storage public/uploads && chmod 600 .env"

echo "▶ Verificación de base de datos y PHP"
remote "cd '${REMOTE_DIR}' && php -v | head -1 && php tools/migrate.php --status 2>&1 | tail -12"

echo "✓ Despliegue terminado: ${SITE_URL:-https://$HOSTINGER_HOST}"
echo "  Revisa: / (portada), /materiales, /calculadoras, /admin/login"
