#!/usr/bin/env bash
#
# apt-utils.sh
#
# apt-utils base setup
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
ERR_ENV=1

me=$(basename "$0")
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $ERR_ENV; pwd)"
quietLog="$top/.build/$me.log"
markerFile="$top/.build/.$me.marker"
packages=(apt-utils figlet)
apt=$(which apt-get)

set -eo pipefail

. "$top/bin/build/colors.sh"

if [ -f "$markerFile" ]; then
  exit 0
fi

if [ -z "$apt" ]; then
  consoleMagenta "No apt, continuing"
  exit 0
fi

[ -d "$(dirname "$quietLog")" ] || mkdir -p "$(dirname "$quietLog")"

export DEBIAN_FRONTEND=noninteractive

start=$(($(date +%s) + 0))
consoleWhite
echo -n "Updating apt-get ... "
if ! apt-get update >> "$quietLog" 2>&1; then
  failed "$quietLog"
  exit $ERR_ENV
fi
echo -n "Installing ${packages[*]} ... "
if ! apt-get install -y "${packages[@]}" >> "$quietLog" 2>&1; then
  failed "$quietLog"
  exit $ERR_ENV
fi
date > "$markerFile"
consoleBlue "$(($(date +%s) - start)) seconds"
consoleReset
