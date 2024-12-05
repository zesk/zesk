#!/usr/bin/env bash
#
# Copyright &copy; 2024 Market Acumen, Inc.
#
if [ -z "$MARIADB_ROOT_PASSWORD" ]; then
  echo "No MARIADB_ROOT_PASSWORD"
  exit 1
fi
mysqladmin -u root -p"${MARIADB_ROOT_PASSWORD}" status
