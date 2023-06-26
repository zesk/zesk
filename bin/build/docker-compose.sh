#!/usr/bin/env bash
#
# docker-compose.sh
#
# Depends: pip3 python3
#
# install docker-compose and requirements
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
err_env=1

me=$(basename "$0")
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $err_env; pwd)"
quietLog="$top/.build/$me.log"
set -eo pipefail
. "$top/bin/build/colors.sh"

if which docker-compose 2> /dev/null 1>&2; then
  exit 0
fi

[ -d "$(dirname "$quietLog")" ] || mkdir -p "$(dirname "$quietLog")"

"$top/bin/build/pip.sh"

consoleCyan "Installing docker-compose ... "
pip install docker-compose > "$quietLog" 2>&1
if ! which docker-compose 2> /dev/null; then
  consoleError "docker-compose not found after install"
  failed "$quietLog"
fi
