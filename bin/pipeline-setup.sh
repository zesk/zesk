#!/usr/bin/env bash
#
# pipeline-setup.sh
#
# Set up Zesk build
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
ERR_ENV=1

# Debug bash - set -x
me=$(basename "$0")
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." || exit $ERR_ENV; pwd)"
# Optional binaries in build image
docker=$(which docker)
envFile="$top/.env"
quietLog="$top/.build/$me.log"
yml="$top/docker-compose.yml"

set -eo pipefail

failed() {
  echo
  cat "$quietLog"
  echo
  figlet failed
  return $ERR_ENV
}
if [ -z "$docker" ]; then
  echo "No docker found in $PATH" 1>&2
  exit $ERR_ENV
fi

"$top/bin/build/apt-utils.sh"
"$top/bin/build/docker-compose.sh"

start=$(($(date +%s) + 0))
echo -n "Install vendor ... "
if ! docker run -v "$(pwd):/app" composer:latest i --ignore-platform-req=ext-calendar >> "$quietLog" 2>&1; then
  exit "$(failed)"
fi
echo $(($(date +%s) - start)) seconds

start=$(($(date +%s) + 0))
echo -n "Build container ... "
if ! docker-compose -f "$yml" build --no-cache --pull >> "$quietLog" 2>&1; then
  exit "$(failed)"
fi
echo $(($(date +%s) - start)) seconds

echo Running container ...
docker-compose -f "$yml" up -d

start=$(($(date +%s) + 0))
figlet Testing
set -x
docker-compose -f "$yml" exec -T php /zesk/bin/test-zesk.sh --coverage --testsuite core
echo Testing took $(($(date +%s) - start)) seconds

"$top/bin/release-check-version.sh"

echo Stopping container ...
docker-compose down

env > "$envFile"
