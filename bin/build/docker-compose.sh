#!/usr/bin/env bash
#
# docker-compose.sh
#
# install docker-compose and requirements
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
ERR_ENV=1

me=$(basename "$0")
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $ERR_ENV; pwd)"
quietLog="$top/.build/$me.log"

set -eo pipefail

if which docker-compose; then
  exit 0
fi

[ -d "$(dirname "$quietLog")" ] || mkdir -p "$(dirname "$quietLog")"

"$top/bin/build/pip.sh"

echo "Installing docker-compose ..."
pip install docker-compose > "$quietLog" 2>&1
if ! which docker-compose 2> /dev/null; then
  echo "docker-compose not found after install" 1>&2
  exit $ERR_ENV
fi
