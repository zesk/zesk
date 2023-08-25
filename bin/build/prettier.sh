#!/usr/bin/env bash
#
# prettier.sh
#
# Depends: apt npm
#
# prettier install if needed
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
err_env=1

me="$(basename "$0")"
relTop="../.."
if ! cd "$(dirname "${BASH_SOURCE[0]}")/$relTop"; then
  echo "$me: Can not cd to $relTop" 1>&2
  exit $err_env
fi
top="$(pwd)"
quietLog="$top/.build/$me.log"

set -eo pipefail
. "$top/bin/build/colors.sh"

if ! which prettier 2> /dev/null 1>&2; then
  "$top/bin/build/apt-utils.sh"
  "$top/bin/build/npm.sh"

  [ -d "$(dirname "$quietLog")" ] || mkdir -p "$(dirname "$quietLog")"

  consoleInfo -n "Installing prettier ..."
  start=$(beginTiming)
  if ! npm install -g prettier >> "$quietLog" 2>&1; then
    failed "$quietLog"
  fi
  reportTiming "$start" OK
fi
