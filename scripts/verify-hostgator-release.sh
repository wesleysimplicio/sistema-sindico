#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD_DIR="${1:-${ROOT_DIR}/.deploy-build/hostgator-release}"

fail() {
  echo "error: $1" >&2
  exit 1
}

[[ -d "${BUILD_DIR}" ]] || fail "release directory not found: ${BUILD_DIR}"

[[ ! -d "${BUILD_DIR}/Users" ]] || fail "release directory contains an absolute-path nesting bug"

required_paths=(
  ".htaccess"
  "public/index.php"
  "public/.htaccess"
  "public/assets/app.css"
  "src/Core/Application.php"
  "src/Core/Autoload.php"
  "src/Core/Env.php"
  "src/Core/Router.php"
  "src/Core/Auth.php"
  "src/Core/Jwt.php"
  "src/Controllers/Web/LoginController.php"
  "src/Controllers/Web/DashboardController.php"
  "src/Controllers/Api/AuthController.php"
  "routes/web.php"
  "routes/api.php"
  "templates/layouts/app.php"
  "config/app.php"
)

for path in "${required_paths[@]}"; do
  [[ -e "${BUILD_DIR}/${path}" ]] || fail "missing required path in release: ${path}"
done

forbidden_paths=(
  ".env"
  ".env.example"
  "config/config.php"
  "database"
  "docs"
  "tests"
  "README.md"
  "README.pt-BR.md"
  "CHANGELOG.md"
  "CLAUDE.md"
  "VERSION"
  ".github"
  "scripts"
)

for path in "${forbidden_paths[@]}"; do
  [[ ! -e "${BUILD_DIR}/${path}" ]] || fail "forbidden path present in release: ${path}"
done

if find "${BUILD_DIR}/storage/logs" -type f ! -name '.gitkeep' | grep -q .; then
  fail "storage/logs contains runtime log files"
fi

echo "Release package looks safe: ${BUILD_DIR}"
