#!/usr/bin/env bash
if [ -z "$MARIADB_ROOT_PASSWORD" ]; then
  echo "No MARIADB_ROOT_PASSWORD"
  exit 1
fi
mysqladmin -u root -p"${MARIADB_ROOT_PASSWORD}" status
