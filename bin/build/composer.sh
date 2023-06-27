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

dockerImage=composer:latest
composerArgs=()
composerArgs+=("-v" "$top:/app")
composerArgs+=("-v" "$top/.composer:/tmp")
composerArgs+=("$dockerImage")

start=$(beginTiming)
consoleInfo -n "Composer ... "
figlet "Install vendor" >> "$quietLog"
#DEBUGGING - remove, why no -q option?
echo Running: docker pull $dockerImage >> "$quietLog"

if ! docker pull $dockerImage >> "$quietLog" 2>&1; then
  consoleError "Failed to pull image $dockerImage"
  failed "$quietLog"
  exit $err_build
fi
consoleInfo -n "validating ... "
echo Running: docker run "${composerArgs[@]}" validate >> "$quietLog"
if ! docker run "${composerArgs[@]}" install >> "$quietLog" 2>&1; then
  failed "$quietLog"
  exit $err_build
fi

composerArgs+=("--ignore-platform-reqs")
composerArgs+=("install")
consoleInfo -n "installing ... "
echo Running: docker run "${composerArgs[@]}" >> "$quietLog"
if ! docker run "${composerArgs[@]}" >> "$quietLog" 2>&1; then
  failed "$quietLog"
  exit $err_build
fi
reportTiming "$start" OK
