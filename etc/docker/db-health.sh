#!/usr/bin/env bash
#
# Copyright &copy; 2025, Market Acumen, Inc.
#
# Is the database running and doing ok?
#
export MARIADB_ROOT_PASSWORD
if [ -z "${MARIADB_ROOT_PASSWORD-}" ]; then
  echo "No MARIADB_ROOT_PASSWORD"
  exit 1
fi
mysqladmin -u root -p"${MARIADB_ROOT_PASSWORD}" status
