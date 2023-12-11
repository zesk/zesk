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
errorBuild=1000

me=$(basename "$0")
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
set -eo pipefail

# shellcheck source=/dev/null
. "./bin/build/tools.sh"

quietLog=$(buildQuietLog "$me")

start=$(beginTiming)
consoleInfo -n "Build container zesk:latest ... "
figlet "Build container" >>"$quietLog"
if ! docker build "$@" --build-arg "DATABASE_HOST=$CONTAINER_DATABASE_HOST" -f ./docker/php.Dockerfile --tag zesk:latest . >>"$quietLog" 2>&1; then
  failed "$quietLog"
  exit $errorBuild
fi
reportTiming "$start" OK
