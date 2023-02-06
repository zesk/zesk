#!/usr/bin/env bash
#
# pipeline-setup.sh
#
# Set up Zesk build
#
# Copyright &copy; 2023 Market Acumen, Inc.
#

#
# Exit codes
#
ERR_ENV=1
ERR_BUILD=1000

#
# Variables and constants
#
# Debug bash - set -x
export TERM=xterm-256color
me=$(basename "$0")
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." || exit $ERR_ENV; pwd)"
# Optional binaries in build image
docker=$(which docker)
envFile="$top/.env"
quietLog="$top/.build/$me.log"
envs=(DATABASE_ROOT_PASSWORD DATABASE_HOST DATABASE_PORT)
DATABASE_PORT=${DATABASE_PORT:-3306}
DATABASE_HOST=${DATABASE_HOST}

set -eo pipefail

#
# Preflight our environment to make sure we have the basics defined in the calling script
#
for e in "${envs[@]}"; do
  if [ -z "${!e}" ]; then
    echo "Need to have $e defined in pipeline" 1>&2
    exit $ERR_ENV
  fi
done

consoleReset() {
  echo -en '\033[0m' # Reset
}

consoleCode() {
  local start=$1 end=$2
  shift shift
  if [ -z "$*" ]; then
    echo -ne "$start"
  else
    echo -ne "$start"
    echo "$@"
    echo -ne "$end"
  fi
}
consoleRed() {
  consoleCode '\033[31m' '\033[0m' "$@"
}
consoleBlue() {
  consoleCode '\033[94m' '\033[0m' "$@"
}
# shellcheck disable=SC2120
consoleMagenta() {
  consoleCode '\033[35m' '\033[0m' "$@"
}
consoleWhite() {
  consoleCode '\033[47m' '\033[0m' "$@"
}
consoleBold() {
  consoleCode '\033[1m' '\033[21m' "$@"
}
consoleUnderline() {
  consoleCode '\033[4m' '\033[24m' "$@"
}
consoleNoBold() {
  echo -en '\033[21m'
}
consoleNoUnderline() {
  echo -en '\033[24m'
}
echobar() {
  echo "======================================================="
}

#
# When things go badly
#
failed() {
  consoleRed "$(echobar)"
    consoleWhite "Last 50 of $(consoleBold "$quietLog") ..."
  consoleRed "$(echobar)"
  consoleWhite
    tail -50 "$quietLog"
    echo
  consoleRed
    echobar
    figlet failed
  consoleRed "$(echobar)"
    consoleWhite "Last 3 of $(consoleBold "$quietLog") ..."
  consoleRed "$(echobar)"
  consoleMagenta
    tail -3 "$quietLog"
    echo
  consoleReset
  return $ERR_ENV
}

# apt-get update and install figlet
"$top/bin/build/apt-utils.sh"

# Connect to the database and set up test schema
databaseArguments=("-u" "root" "-p$DATABASE_ROOT_PASSWORD" "-h" "$DATABASE_HOST" "--port" "$DATABASE_PORT")
echo -n "Setting up database ..."
{
  consoleWhite "$(figlet "Database")"
  consoleWhite "$(echobar)"
  consoleBlue "COMMAND:"
  consoleWhite mariadb "${databaseArguments[@]}"
  consoleBlue "TODO TRY COMMAND:"
  consoleWhite docker run -t zesk:latest mariadb "${databaseArguments[@]}"
} >> "$quietLog"

"$top/bin/build/mariadb-client.sh"

if ! mariadb "${databaseArguments[@]}" < ./docker/mariadb/schema.sql >> "$quietLog"; then
  failed
  exit $ERR_BUILD
fi
}

if [ -z "$docker" ]; then
  consoleMagenta "No docker found in $PATH" 1>&2
  exit $ERR_ENV
fi

[ -d "$top/.composer" ] || mkdir "$top/.composer"

vendorArgs=("-v" "$top:/app" "-v" "$top/.composer:/tmp" "composer:latest" i "--ignore-platform-req=ext-calendar")

start=$(($(date +%s) + 0))
echo -n "Install vendor ... "
figlet "Install vendor" >> "$quietLog"
echo docker run "${vendorArgs[@]}" >> "$quietLog"

if ! docker run "${vendorArgs[@]}" >> "$quietLog" 2>&1; then
  failed
  exit $ERR_BUILD
fi
echo $(($(date +%s) - start)) seconds

start=$(($(date +%s) + 0))
echo -n "Build container ... "
figlet "Build container" >> "$quietLog"
if ! docker build --build-arg DATABASE_HOST=host.docker.internal -f ./docker/php.Dockerfile --tag zesk:latest . >> "$quietLog" 2>&1; then
  failed
  exit $ERR_BUILD
fi
echo $(($(date +%s) - start)) seconds

start=$(($(date +%s) + 0))
figlet Testing
set -x
docker run php:latest /zesk/bin/test-zesk.sh --coverage --testsuite core
echo Testing took $(($(date +%s) - start)) seconds

"$top/bin/release-check-version.sh"

env > "$envFile"
