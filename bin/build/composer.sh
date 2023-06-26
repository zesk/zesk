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

. "$top/bin/build/apt-utils.sh"

[ -d "$top/.composer" ] || mkdir "$top/.composer"

vendorArgs=()
vendorArgs+=("-v" "$top:/app")
vendorArgs+=("-v" "$top/.composer:/tmp")
vendorArgs+=("composer:latest")
vendorArgs+=("--ignore-platform-reqs")
vendorArgs+=("install")

start=$(beginTiming)
consoleInfo -n "Install vendor ... "
figlet "Install vendor" >> "$quietLog"
echo Running: docker run "${vendorArgs[@]}" >> "$quietLog"
#DEBUGGING - remove, why no -q option?
docker run --help 2>&1 || :
if ! docker run "${vendorArgs[@]}" >> "$quietLog" 2>&1; then
  failed "$quietLog"
  exit $err_build
fi
reportTiming "$start" OK
