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

set -eo pipefail

if [ -f "$markerFile" ]; then
  exit 0
fi

[ -d "$(dirname "$quietLog")" ] || mkdir -p "$(dirname "$quietLog")"

echo "Updating apt-get ..."
apt-get update >> "$quietLog"
echo "Installing ${packages[*]} ..."
apt-get install -y "${packages[@]}" >> "$quietLog" 2>&1

date > "$markerFile"
