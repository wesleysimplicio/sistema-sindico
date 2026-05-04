#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BUILD_DIR="${1:-${ROOT_DIR}/.deploy-build/hostgator-release}"
MANIFEST_PATH="${ROOT_DIR}/.deploy-build/hostgator-release.manifest.txt"

rm -rf "${BUILD_DIR}"
mkdir -p "${BUILD_DIR}"

copy_path() {
  local relative_path="$1"

  if [[ -e "${ROOT_DIR}/${relative_path}" ]]; then
  (
    cd "${ROOT_DIR}"
    rsync -aR "./${relative_path}" "${BUILD_DIR}/"
  )
  fi
}

# Application runtime
copy_path "src"
copy_path "routes"
copy_path "templates"
copy_path "config/app.php"

# Public document root
copy_path "public/index.php"
copy_path "public/assets"

# Front-controller redirects (created at repo root for shared-hosting layouts)
copy_path ".htaccess"
copy_path "public/.htaccess"

# Required runtime directories on the remote
mkdir -p "${BUILD_DIR}/storage/logs"
if [[ -f "${ROOT_DIR}/storage/logs/.gitkeep" ]]; then
  copy_path "storage/logs/.gitkeep"
fi

# Forbidden paths must never reach the release
for forbidden in \
  ".env" \
  "config/config.php" \
  "database" \
  "docs" \
  "tests" \
  "README.md" \
  "README.pt-BR.md" \
  "CHANGELOG.md" \
  "CLAUDE.md" \
  "VERSION"; do
  if [[ -e "${BUILD_DIR}/${forbidden}" ]]; then
    echo "error: forbidden path leaked into release: ${forbidden}" >&2
    exit 1
  fi
done

{
  echo "HostGator release manifest"
  echo "Generated at: $(date '+%Y-%m-%d %H:%M:%S %z')"
  echo "Source: ${ROOT_DIR}"
  echo
  echo "Included paths:"
  (
    cd "${BUILD_DIR}"
    find . -type f | sort
  )
  echo
  echo "Required remote directories:"
  echo "./storage/logs/"
  echo
  echo "Excluded by policy:"
  echo "./.env"
  echo "./database/"
  echo "./docs/"
  echo "./README.md"
  echo "./README.pt-BR.md"
  echo "./CHANGELOG.md"
  echo "./CLAUDE.md"
  echo "./VERSION"
} > "${MANIFEST_PATH}"

echo "Release prepared at ${BUILD_DIR}"
echo "Manifest written to ${MANIFEST_PATH}"
