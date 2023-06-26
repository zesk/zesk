#!/usr/bin/env bash
#
# composer.sh
#
# Depends: docker
#
# run composer install
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
err_env=1
err_build=1000

me=$(basename "$0")
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $err_env; pwd)"
quietLog="$top/.build/$me.log"
set -eo pipefail
. "$top/bin/build/colors.sh"

[ -d "$top/.composer" ] || mkdir "$top/.composer"

vendorArgs=()
vendorArgs+=("-v" "$top:/app")
vendorArgs+=("-v" "$top/.composer:/tmp")
vendorArgs+=("composer:latest")
vendorArgs+=("--ignore-platform-reqs")
vendorArgs+=("install")

start=$(($(date +%s) + 0))
consoleCyan
echo -n "Install vendor ... "
figlet "Install vendor" >> "$quietLog"
echo docker run "${vendorArgs[@]}" >> "$quietLog"

if ! docker run "${vendorArgs[@]}" >> "$quietLog" 2>&1; then
  failed "$quietLog"
  exit $err_build
fi
consoleBoldMagenta $(($(date +%s) - start)) seconds
consoleReset
exit 0
