#!/usr/bin/env bash
#
# apt-utils.sh
#
# Depends: apt
#
# apt-utils base setup
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
err_env=1

me=$(basename "$0")
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $err_env; pwd)"
quietLog="$top/.build/$me.log"
markerFile="$top/.build/.$me.marker"
packages=(apt-utils figlet)
set +e
apt=$(which apt-get)
set -eo pipefail

. "$top/bin/build/colors.sh"

if [ -f "$markerFile" ]; then
  exit 0
fi

[ -d "$(dirname "$quietLog")" ] || mkdir -p "$(dirname "$quietLog")"

if [ -z "$apt" ]; then
  consoleInfo "No apt, continuing"
  exit 0
fi

export DEBIAN_FRONTEND=noninteractive

start=$(beginTiming)
consoleInfo -n "Updating apt-get ... "
if ! apt-get update >> "$quietLog" 2>&1; then
  failed "$quietLog"
  exit $err_env
fi
reportTiming "$start" OK
start=$(beginTiming)
consoleInfo -n "Installing ${packages[*]} ... "
if ! apt-get install -y "${packages[@]}" >> "$quietLog" 2>&1; then
  failed "$quietLog"
  exit $err_env
fi
date > "$markerFile"
reportTiming "$start" OK
