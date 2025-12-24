#!/usr/bin/env bash
#
# Copyright &copy; 2025, Market Acumen, Inc.
#
# Connect to the database
#
if [ -z "$MARIADB_ROOT_PASSWORD" ]; then
  echo "No MARIADB_ROOT_PASSWORD"
  exit 1
fi
mysql -u root -p"${MARIADB_ROOT_PASSWORD}" "$@"
