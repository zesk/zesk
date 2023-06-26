#!/usr/bin/env bash
#
# git.sh
#
# Depends: apt
#
# git install if needed
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
err_env=1

me=$(basename "$0")
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $err_env; pwd)"
quietLog="$top/.build/$me.log"

set -eo pipefail

if ! which git 2> /dev/null 1>&2; then
  "$top/bin/build/apt-utils.sh"

  [ -d "$(dirname "$quietLog")" ] || mkdir -p "$(dirname "$quietLog")"

  consoleInfo -n "Installing git ..."
  export DEBIAN_FRONTEND=noninteractive
  start=$(beginTiming)
  if ! apt-get install -y git > "$quietLog" 2>&1 then
    failed "$quietLog"
  fi
  reportTiming "$start" OK
fi
