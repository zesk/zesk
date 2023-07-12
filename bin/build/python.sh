#!/usr/bin/env bash
#
# python.sh
#
# Depends: apt
#
# python3 install if needed
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
. "$top/bin/build/colors.sh"

set -eo pipefail

if ! which python 2> /dev/null 1>&2; then
  "$top/bin/build/apt-utils.sh"

  [ -d "$(dirname "$quietLog")" ] || mkdir -p "$(dirname "$quietLog")"

  start=$(beginTiming)
  consoleCyan "Installing python3 python3-pip ..."
  export DEBIAN_FRONTEND=noninteractive
  if ! apt-get install -y python3 python3-pip > "$quietLog" 2>&1; then
    failed "$quietLog"
    exit "$err_env"
  fi
  reportTiming "$start" OK
fi
