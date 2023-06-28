#!/usr/bin/env bash
#
# zesk-latest.sh
#
# Depends: docker
#
# Build zesk:latest
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

[ -d "$(dirname "$quietLog")" ] || mkdir -p "$(dirname "$quietLog")"

start=$(beginTiming)
consoleInfo -n "Build container zesk:latest ... "
figlet "Build container" >> "$quietLog"
if ! docker build "$@" --build-arg "DATABASE_HOST=$CONTAINER_DATABASE_HOST" -f ./docker/php.Dockerfile --tag zesk:latest . >> "$quietLog" 2>&1; then
  failed "$quietLog"
  exit $err_build
fi
reportTiming "$start" OK
exit 0
