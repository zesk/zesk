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
export TERM=xterm
export DEBIAN_FRONTEND=noninteractive
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

. "$top/bin/build/colors.sh"

#
# Preflight our environment to make sure we have the basics defined in the calling script
#
for e in "${envs[@]}"; do
  if [ -z "${!e}" ]; then
    consoleMagenta "Need to have $e defined in pipeline" 1>&2
    exit $ERR_ENV
  fi
done

if [ -z "$docker" ]; then
  consoleMagenta "No docker found in $PATH" 1>&2
  exit $ERR_ENV
fi

#
# Eat --clean if it's an argument
#
clean=
while [ $# -gt 0 ]; do
  case $1 in
  --clean)
    clean=1
    consoleBlue "Clean install ..."
    shift
    ;;
  *)
    break
    ;;
  esac
done

# apt-get update and install figlet
"$top/bin/build/apt-utils.sh"

echo "Started on $(date)" > "$quietLog"

#
# Connect to the database and set up test schema
#
start=$(($(date +%s) + 0))
databaseArguments=("-u" "root" "-p$DATABASE_ROOT_PASSWORD" "-h" "$DATABASE_HOST" "--port" "$DATABASE_PORT")
consoleCyan
echo -n "Setting up database ... "
{
  figlet "Database"
  echoBar
  echo "COMMAND:"
  echo mariadb "${databaseArguments[@]}"
  echo "TODO TRY COMMAND:"
  echo docker run -t zesk:latest mariadb "${databaseArguments[@]}"
} >> "$quietLog"

"$top/bin/build/mariadb-client.sh"

echo -n "loading schema ... "
if ! mariadb "${databaseArguments[@]}" < ./docker/mariadb/schema.sql >> "$quietLog"; then
  failed "$quietLog"
  exit $ERR_BUILD
fi
consoleBoldMagenta $(($(date +%s) - start)) seconds
consoleReset

[ -d "$top/.composer" ] || mkdir "$top/.composer"

vendorArgs=("-v" "$top:/app" "-v" "$top/.composer:/tmp" "composer:latest" i "--ignore-platform-req=ext-calendar")

start=$(($(date +%s) + 0))
if test $clean; then
  consoleBlue "Deleting $top/vendor"
  [ -d "$top/vendor" ] && rm -rf "$top/vendor"
fi
consoleCyan
echo -n "Install vendor ... "
figlet "Install vendor" >> "$quietLog"
echo docker run "${vendorArgs[@]}" >> "$quietLog"

if ! docker run "${vendorArgs[@]}" >> "$quietLog" 2>&1; then
  failed "$quietLog"
  exit $ERR_BUILD
fi
consoleBoldMagenta $(($(date +%s) - start)) seconds
consoleReset

cleanArgs=()
if test "$clean"; then
  cleanArgs=("--no-cache" "--pull")
fi
start=$(($(date +%s) + 0))
consoleCyan
echo -n "Build container ... "
figlet "Build container" >> "$quietLog"
if ! docker build "${cleanArgs[@]}" --build-arg "DATABASE_HOST=docker.host.internal" -f ./docker/php.Dockerfile --tag zesk:latest . >> "$quietLog" 2>&1; then
  failed "$quietLog"
  exit $ERR_BUILD
fi
consoleBoldMagenta $(($(date +%s) - start)) seconds
consoleReset

start=$(($(date +%s) + 0))
consoleCyan "$(figlet Testing)"
for d in "test-results" ".zesk-coverage" "test-coverage" ".phpunit-cache"; do
  [ -d "$d" ] || mkdir -p "$d"
done
docker run -v "$top/:/zesk" zesk:latest /zesk/bin/test-zesk.sh "$@"
consoleBoldMagenta Testing took $(($(date +%s) - start)) seconds

consoleBlue
"$top/bin/build/release-check-version.sh"
consoleReset

env > "$envFile"
