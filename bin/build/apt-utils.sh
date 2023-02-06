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

if [ -f "$markerFile" ]; then
  exit 0
fi

if [ -z "$apt" ]; then
  echo "No apt, continuing"
  exit 0
fi

[ -d "$(dirname "$quietLog")" ] || mkdir -p "$(dirname "$quietLog")"

start=$(($(date +%s) + 0))
echo -n "Updating apt-get ... "
export DEBIAN_FRONTEND=noninteractive
apt-get update >> "$quietLog" 2>&1
echo -n "Installing ${packages[*]} ... "
apt-get install -y "${packages[@]}" >> "$quietLog" 2>&1
date > "$markerFile"
echo "$(($(date +%s) - start)) seconds"
