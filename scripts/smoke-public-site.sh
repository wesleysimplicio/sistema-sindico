#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-}"

if [[ -z "${BASE_URL}" ]]; then
  echo "usage: $0 https://example.com/sindico" >&2
  exit 1
fi

BASE_URL="${BASE_URL%/}"
FAILURES=0

check_code() {
  local label="$1"
  local url="$2"
  local expected="$3"
  local code

  code="$(curl -sS -o /dev/null -w '%{http_code}' "${url}")"
  echo "${label}: ${code} ${url}"

  case "${expected}" in
    ok)
      [[ "${code}" =~ ^(200|301|302|303|307|308)$ ]] || return 1
      ;;
    page)
      [[ "${code}" == "200" ]] || return 1
      ;;
    blocked)
      [[ "${code}" != "200" ]] || return 1
      ;;
    *)
      return 1
      ;;
  esac

  return 0
}

check_health_envelope() {
  local url="${BASE_URL}/api/health"
  local body

  body="$(curl -sS "${url}" || true)"
  echo "health body: ${body}"

  echo "${body}" | grep -q '"success":true' || return 1
  echo "${body}" | grep -q '"status":"ok"' || return 1
}

for spec in \
  "base|${BASE_URL}/|ok" \
  "login|${BASE_URL}/login|page" \
  "css|${BASE_URL}/assets/app.css|page" \
  "api-health|${BASE_URL}/api/health|page" \
  "env-blocked|${BASE_URL}/.env|blocked" \
  "schema-blocked|${BASE_URL}/database/schema.sql|blocked" \
  "changelog-blocked|${BASE_URL}/CHANGELOG.md|blocked" \
  "version-blocked|${BASE_URL}/VERSION|blocked" \
  "claude-blocked|${BASE_URL}/CLAUDE.md|blocked"; do
  IFS="|" read -r label url expected <<< "${spec}"
  if ! check_code "${label}" "${url}" "${expected}"; then
    FAILURES=$((FAILURES + 1))
  fi
done

if ! check_health_envelope; then
  echo "health envelope check failed" >&2
  FAILURES=$((FAILURES + 1))
fi

if [[ "${FAILURES}" -gt 0 ]]; then
  echo "Public smoke checks failed: ${FAILURES}" >&2
  exit 1
fi

echo "Public smoke checks passed for ${BASE_URL}"
