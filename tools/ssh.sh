#!/usr/bin/env bash
# Utilidades SSH/SFTP para Hostinger (usa SSH_ASKPASS para evitar TTY).
# Uso: tools/ssh.sh 'comando remoto'
#      tools/ssh.sh -t   # abre túnel MySQL local 3307 → remoto 3306 (bloqueante)
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
[ -f "$ROOT/.deploy.env" ] || { echo "Falta .deploy.env" >&2; exit 1; }
# shellcheck disable=SC1091
source "$ROOT/.deploy.env"

ASKPASS="$(mktemp "${TMPDIR:-/tmp}/arcillas-askpass.XXXXXX")"
cleanup() { rm -f "$ASKPASS"; }
trap cleanup EXIT
printf '#!/bin/sh\nprintf "%%s\\n" %q\n' "$HOSTINGER_PASS" > "$ASKPASS"
chmod 700 "$ASKPASS"
export SSH_ASKPASS="$ASKPASS" SSH_ASKPASS_REQUIRE=force DISPLAY="${DISPLAY:-:0}"

SSH_OPTS=(-T -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null \
  -o ConnectTimeout=30 -o PreferredAuthentications=password -o PubkeyAuthentication=no \
  -p "$HOSTINGER_PORT")

if [ "${1:-}" = "-t" ]; then
  exec ssh "${SSH_OPTS[@]}" -N -L 3307:127.0.0.1:3306 "${HOSTINGER_USER}@${HOSTINGER_HOST}"
fi
ssh "${SSH_OPTS[@]}" "${HOSTINGER_USER}@${HOSTINGER_HOST}" "$@" < /dev/null
