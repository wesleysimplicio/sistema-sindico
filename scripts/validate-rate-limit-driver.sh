#!/usr/bin/env bash
set -euo pipefail

DRIVER="${1:-mysql}"
PORT="${APP_PORT:-8010}"
BASE_URL="${BASE_URL:-http://127.0.0.1:${PORT}}"
EMAIL_SUFFIX="$(date +%s)"
SERVER_LOG="$(mktemp -t sistema-sindico-rate-limit.XXXXXX.log)"
SERVER_PID=""

cleanup() {
  if [[ -n "${SERVER_PID}" ]] && kill -0 "${SERVER_PID}" >/dev/null 2>&1; then
    kill "${SERVER_PID}" >/dev/null 2>&1 || true
    wait "${SERVER_PID}" >/dev/null 2>&1 || true
  fi
  docker compose down -v --remove-orphans >/dev/null 2>&1 || true
}

show_logs() {
  if [[ -f "${SERVER_LOG}" ]]; then
    cat "${SERVER_LOG}" || true
  fi
  docker compose ps -a || true
  docker compose logs --no-color db redis || true
}

wait_for_health() {
  for _ in $(seq 1 45); do
    if curl -fsS "${BASE_URL}/api/health" >/dev/null 2>&1; then
      return 0
    fi
    sleep 1
  done

  echo "App did not expose ${BASE_URL}/api/health in time." >&2
  return 1
}

start_stack() {
  if [[ "${DRIVER}" == "redis" ]]; then
    docker compose --profile redis up -d db redis

    for _ in $(seq 1 20); do
      if docker compose exec -T redis redis-cli ping 2>/dev/null | grep -q '^PONG$'; then
        break
      fi
      sleep 1
    done

    if ! docker compose exec -T redis redis-cli ping 2>/dev/null | grep -q '^PONG$'; then
      echo "Redis did not become ready in time." >&2
      return 1
    fi
  else
    docker compose up -d db
  fi

  for _ in $(seq 1 45); do
    if docker compose exec -T db mysqladmin ping -h 127.0.0.1 -uroot -proot --silent >/dev/null 2>&1; then
      return 0
    fi
    sleep 1
  done

  echo "MySQL did not become ready in time." >&2
  return 1
}

start_php_server() {
  APP_ENV=local \
  APP_DEBUG=true \
  APP_URL="${BASE_URL}" \
  DB_HOST=127.0.0.1 \
  DB_PORT=3307 \
  DB_DATABASE=sistema_sindico \
  DB_USERNAME=sistema_sindico \
  DB_PASSWORD=sistema_sindico \
  JWT_SECRET=local-dev-jwt-secret-not-for-prod-32 \
  RATE_LIMIT_DRIVER="${DRIVER}" \
  REDIS_URL=redis://127.0.0.1:6380/0 \
    php -S "127.0.0.1:${PORT}" -t public >"${SERVER_LOG}" 2>&1 &

  SERVER_PID="$!"
}

trap 'status=$?; if [[ $status -ne 0 ]]; then show_logs; fi; cleanup; exit $status' EXIT

cleanup
start_stack
start_php_server
wait_for_health

npx -y newman run tests/api/rate-limit.postman_collection.json \
  --env-var "baseUrl=${BASE_URL}" \
  --env-var "rateEmail=rate-limit-${DRIVER}-${EMAIL_SUFFIX}@example.com" \
  --env-var "allowedAttempts=3" \
  --env-var "totalAttempts=4"
