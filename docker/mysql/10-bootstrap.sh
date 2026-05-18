#!/bin/sh
set -eu

DB_DIR="/docker-bootstrap/database"

echo "[docker-bootstrap] importing schema.sql"
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" < "${DB_DIR}/schema.sql"

for migration in "${DB_DIR}"/migrations/*.sql; do
  echo "[docker-bootstrap] applying $(basename "${migration}")"
  mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" < "${migration}"
done

echo "[docker-bootstrap] importing seed.sql"
mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" "${MYSQL_DATABASE}" < "${DB_DIR}/seed.sql"
