#!/usr/bin/env bash
#
# mariadb-client.sh
#
# Depends: apt
#
# mariadb-client install if needed
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
err_env=1

me=$(basename "$0")
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $err_env; pwd)"
quietLog="$top/.build/$me.log"
mariadb=$(which mariadb)
set -eo pipefail
. "$top/bin/build/colors.sh"

if [ -z "$mariadb" ]; then
  "$top/bin/build/apt-utils.sh"
  [ -d "$(dirname "$quietLog")" ] || mkdir -p "$(dirname "$quietLog")"
  consoleInfo -n "Install mariadb-client ... "
  start=$(beginTiming)
  if ! apt-get install -y mariadb-client > "$quietLog" 2>&1; then
    failed "$quietLog"
  fi
  reportTiming "$start" OK
fi
