#!/usr/bin/env bash
#
# pip.sh
#
# Depends: pip
#
# pip upgrade once
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
err_env=1

me=$(basename "$0")
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $err_env; pwd)"
quietLog="$top/.build/$me.log"
markerFile="$top/.build/.$me.marker"
set -eo pipefail
. "$top/bin/build/colors.sh"

if [ -f "$markerFile" ]; then
  exit 0
fi

[ -d "$(dirname "$quietLog")" ] || mkdir -p "$(dirname "$quietLog")"

start=$(beginTiming)
consoleInfo -n "Upgrading pip ... "
if ! pip install --upgrade pip > "$quietLog" 2>&1; then
  failed "$quietLog"
fi
date > "$markerFile"
reportTiming "$start" OK
