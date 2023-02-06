#!/usr/bin/env bash
#
# mariadb-client.sh
#
# mariadb-client install if needed
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
ERR_ENV=1

me=$(basename "$0")
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $ERR_ENV; pwd)"
quietLog="$top/.build/$me.log"
mariadb=$(which mariadb)

set -eo pipefail

"$top/bin/build/apt-utils.sh"

if [ -z "$mariadb" ]; then
  [ -d "$(dirname "$quietLog")" ] || mkdir -p "$(dirname "$quietLog")"
  apt-get install -y mariadb-client > "$quietLog"
fi
