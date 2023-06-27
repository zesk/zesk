#!/usr/bin/env bash
#
# pipeline-setup.sh
#
# Set up Zesk build
#
# Copyright &copy; 2023 Market Acumen, Inc.
#

#  ▞▀▖      ▗▀▖▗             ▐  ▗
#  ▌  ▞▀▖▛▀▖▐  ▄ ▞▀▌▌ ▌▙▀▖▝▀▖▜▀ ▄ ▞▀▖▛▀▖
#  ▌ ▖▌ ▌▌ ▌▜▀ ▐ ▚▄▌▌ ▌▌  ▞▀▌▐ ▖▐ ▌ ▌▌ ▌
#  ▝▀ ▝▀ ▘ ▘▐  ▀▘▗▄▘▝▀▘▘  ▝▀▘ ▀ ▀▘▝▀ ▘ ▘
buildEnvironment=(GITHUB_ACCESS_TOKEN DEPLOYMENT)

#
# Exit codes
#
err_env=1
err_build=1000

# Project root
if ! cd "$(dirname "${BASH_SOURCE[0]}")/.."; then
  echo "Unable to cd to parent directory, failing" 1>&2
  exit $err_env
fi
top="$(pwd)"

#
# Variables and constants
#
# Debug bash - set -x
export TERM=xterm
export DEBIAN_FRONTEND=noninteractive
me=$(basename "$0")
# Optional binaries in build image
envFile="$top/.env"
quietLog="$top/.build/$me.log"
envs=(DATABASE_ROOT_PASSWORD DATABASE_HOST CONTAINER_DATABASE_HOST DATABASE_PORT)
DATABASE_PORT=${DATABASE_PORT:-3306}
DATABASE_HOST=${DATABASE_HOST:-127.0.0.1}
DATABASE_ROOT_PASSWORD=${DATABASE_ROOT_PASSWORD:-hard-to-guess}
CONTAINER_DATABASE_HOST=${CONTAINER_DATABASE_HOST:-host.docker.internal}

set -eo pipefail

. "$top/bin/build/colors.sh"

startSetup=$(beginTiming)

#
# Preflight our environment to make sure we have the basics defined in the calling script
#
for e in "${envs[@]}" "${buildEnvironment[@]}"; do
  if [ -z "${!e}" ]; then
    consoleMagenta "Need to have $e defined in pipeline" 1>&2
    exit $err_env
  fi
done

if ! which docker > /dev/null 2>&1; then
  consoleMagenta "No docker found in $PATH" 1>&2
  exit $err_env
fi

#
# --clean - Do a clean build
# --develop - Development build, tag with a d-tag upon success
#
clean=
versionArgs=()
while [ $# -gt 0 ]; do
  case $1 in
  --clean)
    clean=1
    consoleBlue "Clean install ..."
    shift
    ;;
  --develop)
    versionArgs+=("--develop")
    consoleBlue "Development ..."
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

#==========================================================================================
#
# Generate the .env artifact
#
consoleInfo -n "Generating environment artifact ... "
start=$(beginTiming)
echo -n > "$envFile"
for e in "${buildEnvironment[@]}"; do
  echo "$e=\"${!e}\"" >> "$envFile"
done
reportTiming "$start" OK

#==========================================================================================
#
# Connect to the database and set up test schema
#
start=$(beginTiming)
databaseArguments=("-u" "root" "-p$DATABASE_ROOT_PASSWORD" "-h" "$DATABASE_HOST" "--port" "$DATABASE_PORT")
consoleInfo -n "Setting up database ... "
{
  figlet "Database"
  echoBar
  echo "COMMAND:"
  echo mariadb "${databaseArguments[@]}"
  echo "TODO TRY COMMAND:"
  echo docker run -t zesk:latest mariadb "${databaseArguments[@]}"
} >> "$quietLog"
reportTiming "$start" OK

#==========================================================================================
#
# Install mariadb client
#
"$top/bin/build/mariadb-client.sh"

#==========================================================================================
#
# Load the test schema
#
consoleInfo -n "Loading schema ... "
start=$(beginTiming)
if ! mariadb "${databaseArguments[@]}" < ./docker/mariadb/schema.sql >> "$quietLog"; then
  failed "$quietLog"
  exit $err_build
fi
reportTiming "$start" OK

#==========================================================================================
#
# Install vendor directory
#
if test $clean; then
  consoleInfo "Deleting $top/vendor"
  [ -d "$top/vendor" ] && rm -rf "$top/vendor"
fi
"$top/bin/build/composer.sh"

#==========================================================================================
#
# Build the Zesk docker image
#
cleanArgs=()
if test "$clean"; then
  cleanArgs=("--no-cache" "--pull")
fi
start=$(beginTiming)
consoleInfo -n "Build container ... "
figlet "Build container" >> "$quietLog"
if ! docker build "${cleanArgs[@]}" --build-arg "DATABASE_HOST=$CONTAINER_DATABASE_HOST" -f ./docker/php.Dockerfile --tag zesk:latest . >> "$quietLog" 2>&1; then
  failed "$quietLog"
  exit $err_build
fi
reportTiming "$start" OK

#==========================================================================================
#  ▀▛▘     ▐
#   ▌▞▀▖▞▀▘▜▀
#   ▌▛▀ ▝▀▖▐ ▖
#   ▘▝▀▘▀▀  ▀
start=$(beginTiming)
figlet Testing
for d in "test-results" ".zesk-coverage" "test-coverage" ".phpunit-cache"; do
  [ -d "$d" ] || mkdir -p "$d"
done
docker run -v "$top/:/zesk" zesk:latest /zesk/bin/test-zesk.sh "$@"
reportTiming "$start" Passed

consoleInfo
"$top/bin/build/release-check-version.sh" "${versionArgs[@]}"
consoleReset

reportTiming "$startSetup" "Setup complete."
