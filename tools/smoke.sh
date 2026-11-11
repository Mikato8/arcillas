#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════════════════════
# Arcillas MX · smoke test de producción
#
#   ./tools/smoke.sh                       # usa SITE_URL de .deploy.env
#   ./tools/smoke.sh https://tu-dominio    # URL explícita
#
# Verifica rutas públicas, 404, protecciones de directorios internos,
# búsqueda facetada, sitemap y el endpoint de calculadoras.
# Devuelve 1 si algo falla.
# ═══════════════════════════════════════════════════════════════════════
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE="${1:-}"
if [ -z "$BASE" ] && [ -f "$ROOT/.deploy.env" ]; then
  # shellcheck disable=SC1091
  source "$ROOT/.deploy.env"
  BASE="${SITE_URL:-}"
fi
BASE="${BASE:-https://floralwhite-meerkat-876567.hostingersite.com}"
BASE="${BASE%/}"

TMP="$(mktemp -d "${TMPDIR:-/tmp}/arcillas-smoke.XXXXXX")"
trap 'rm -rf "$TMP"' EXIT
FALLOS=0

ok()   { printf '  \033[32m✓\033[0m %-72s %s\n' "$1" "$2"; }
fail() { printf '  \033[31m✗\033[0m %-72s %s\n' "$1" "$2"; FALLOS=$((FALLOS + 1)); }

check_status() {
  local ruta="$1" esperado="$2"
  local code
  code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 25 "$BASE$ruta" || echo "000")
  if [ "$code" = "$esperado" ]; then ok "$ruta" "$code"; else fail "$ruta" "esperado $esperado, obtenido $code"; fi
}

echo "▶ Smoke test: $BASE"
echo "  Rutas públicas"
for ruta in / /materiales /materiales/caolin-ceramico-grado-1 /industrias /proveedores \
            /calculadoras /calculadoras/umf-esmalte /comparar /como-funciona /nosotros \
            /contacto /soy-comprador /soy-proveedor /aviso-de-privacidad /terminos \
            /sitemap.xml /admin/login; do
  check_status "$ruta" 200
done

echo "  Búsqueda y filtros"
check_status "/materiales?q=caolin" 200
check_status "/materiales?al2o3_min=35" 200
check_status "/materiales?categoria=bentonita" 200
check_status "/materiales?q=xyzabc" 200

echo "  Errores y protecciones"
check_status /no-existe 404
check_status /calculadoras/no-existe 404
for ruta in /app/bootstrap.php /.env /database/seed.sql /tools/migrate.php /config/routes.php; do
  check_status "$ruta" 403
done

echo "  Contenido"
curl -s --max-time 25 "$BASE/materiales" -o "$TMP/catalogo.html" || true
if grep -q 'tarjeta-material' "$TMP/catalogo.html"; then
  ok "catálogo renderiza tarjetas" "ok"
else
  fail "catálogo renderiza tarjetas" "sin tarjetas"
fi

URLS=$(curl -s --max-time 25 "$BASE/sitemap.xml" | grep -c '<url>' || true)
if [ "${URLS:-0}" -ge 90 ]; then ok "sitemap.xml" "$URLS URLs"; else fail "sitemap.xml" "$URLS URLs"; fi

echo "  Calculadora UMF (CSRF + JSON)"
curl -s -c "$TMP/cookies" "$BASE/calculadoras/umf-esmalte" -o "$TMP/calc.html"
TOKEN=$(grep -o 'name="_csrf" value="[^"]*"' "$TMP/calc.html" | head -1 | sed 's/.*value="//;s/"//' || true)
if [ -n "${TOKEN:-}" ]; then
  RESPUESTA=$(curl -s -b "$TMP/cookies" -H 'Content-Type: application/json' -H "X-CSRF-Token: $TOKEN" \
    -X POST "$BASE/api/calculadoras/umf-esmalte" \
    -d '{"mat_feldespato_potasico":40,"mat_caolin":20,"mat_silice":20,"mat_carbonato_calcio":20}' || true)
  if printf '%s' "$RESPUESTA" | grep -q '"ok":true'; then
    ok "POST /api/calculadoras/umf-esmalte" "ok:true"
  else
    fail "POST /api/calculadoras/umf-esmalte" "respuesta inesperada"
  fi
else
  fail "token CSRF de la calculadora" "no encontrado"
fi

echo
if [ "$FALLOS" -eq 0 ]; then
  echo "✓ Smoke test OK — sin fallos"
  exit 0
fi
echo "✗ Smoke test con $FALLOS fallo(s)"
exit 1
