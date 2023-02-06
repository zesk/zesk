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

failed() {
  echo -e '\033[94m' # Blue
  echo -e '\033[1m' # Bold
  echo "Last 50 of $quietLog ..."
  echo -e '\033[21m' # Bold Off
  echo
  echo -e '\033[31m' # Red
  tail -50 "$quietLog"
  echo -e '\033[0m' # Reset
  echo
  figlet failed
  return $ERR_ENV
}
if [ -z "$docker" ]; then
  echo "No docker found in $PATH" 1>&2
  exit $ERR_ENV
fi

"$top/bin/build/apt-utils.sh"

start=$(($(date +%s) + 0))
echo -n "Install vendor ... "
figlet "Install vendor" >> "$quietLog"
if ! docker run -v "$(pwd):/app" -v "${COMPOSER_HOME:-$HOME/.composer}:/tmp" \
    composer:latest i --ignore-platform-req=ext-calendar >> "$quietLog" 2>&1; then
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

echo -n "Setting up database ..."
{
  figlet "Database" >> "$quietLog"
  echo "COMMAND:"
  echo mariadb -u root "-p$DATABASE_ROOT_PASSWORD" -h "$DATABASE_HOST" -p "$DATABASE_PORT"
  echo "TODO TRY COMMAND:"
  echo docker run -t zesk:latest mariadb -u root "-p$DATABASE_ROOT_PASSWORD" -h host.docker.internal
} >> "$quietLog"

"$top/bin/build/mariadb-client.sh"

if ! mariadb -u root "-p$DATABASE_ROOT_PASSWORD" -h "$DATABASE_HOST" -p "$DATABASE_PORT" < ./docker/mariadb/schema.sql >> "$quietLog"; then
  failed
  exit $ERR_BUILD
fi

start=$(($(date +%s) + 0))
figlet Testing
set -x
docker run php:latest /zesk/bin/test-zesk.sh --coverage --testsuite core
echo Testing took $(($(date +%s) - start)) seconds

"$top/bin/release-check-version.sh"

env > "$envFile"
