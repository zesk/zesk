#!/usr/bin/env bash
#
# docker-compose.sh
#
# Depends: pip python
#
# install docker-compose and requirements
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
err_env=1

me=$(basename "$0")
relTop="../.."
if ! cd "$(dirname "${BASH_SOURCE[0]}")/$relTop"; then
  echo "$me: Can not cd to $relTop" 1>&2
  exit $err_env
fi
top="$(pwd)"
quietLog="$top/.build/$me.log"
set -eo pipefail
. "$top/bin/build/colors.sh"

if which docker-compose 2> /dev/null 1>&2; then
  exit 0
fi

[ -d "$(dirname "$quietLog")" ] || mkdir -p "$(dirname "$quietLog")"

"$top/bin/build/python.sh"

consoleInfo -n "Installing docker-compose ... "
start=$(beginTiming)
if ! pip install docker-compose > "$quietLog" 2>&1; then
  consoleError "pip install docker-compose failed $?"
  failed "$quietLog"
fi
if ! which docker-compose 2> /dev/null; then
  consoleError "docker-compose not found after install"
  failed "$quietLog"
fi
reportTiming "$start" OK
