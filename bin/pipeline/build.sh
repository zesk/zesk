#!/usr/bin/env bash
#
# pipeline-setup.sh
#
# Set up Zesk build
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
set -eou pipefail

#  ▞▀▖      ▗▀▖▗             ▐  ▗
#  ▌  ▞▀▖▛▀▖▐  ▄ ▞▀▌▌ ▌▙▀▖▝▀▖▜▀ ▄ ▞▀▖▛▀▖
#  ▌ ▖▌ ▌▌ ▌▜▀ ▐ ▚▄▌▌ ▌▌  ▞▀▌▐ ▖▐ ▌ ▌▌ ▌
#  ▝▀ ▝▀ ▘ ▘▐  ▀▘▗▄▘▝▀▘▘  ▝▀▘ ▀ ▀▘▝▀ ▘ ▘
# Must be defined and non-empty to run this script

#
# Exit codes
#
errorBuild=1000
me=$(basename "$0")

# Project root
cd "$(dirname "${BASH_SOURCE[0]}")/../.."

[ -d ./bin/build ] || ./bin/pipeline/install-bin-build.sh

# shellcheck source=/dev/null
. ./bin/build/tools.sh

usage() {
  usageDocument "./bin/pipeline/$me" "zeskPipelineSetup" "$@"
  return $?
}

# fn: {base}
# Usage: {fn}
# Build and test Zesk, and tag release
# Argument: --clean - Optional. Do a clean build.
# Argument: --develop - Optional. Development build.
#
zeskPipelineSetup() {
  local startSetup quietLog clean versionArgs
  local requireEnvironment=(DEPLOYMENT GITHUB_REPOSITORY_OWNER GITHUB_REPOSITORY_NAME)
  # Will be exported to the environment file
  local buildEnvironment=(GITHUB_ACCESS_TOKEN "${requireEnvironment[@]}")

  startSetup=$(beginTiming)

  quietLog=$(buildQuietLog "$me")

  export DATABASE_PORT
  export DATABASE_HOST
  export DATABASE_ROOT_PASSWORD
  export CONTAINER_DATABASE_HOST
  export GITHUB_REPOSITORY_OWNER
  export GITHUB_REPOSITORY_NAME

  #
  # Additional test configuration
  #
  DATABASE_PORT=${DATABASE_PORT:-3306}
  DATABASE_HOST=${DATABASE_HOST:-127.0.0.1}
  DATABASE_ROOT_PASSWORD=${DATABASE_ROOT_PASSWORD:-hard-to-guess}
  CONTAINER_DATABASE_HOST=${CONTAINER_DATABASE_HOST:-host.docker.internal}
  GITHUB_REPOSITORY_OWNER=${GITHUB_REPOSITORY_OWNER:-zesk}
  GITHUB_REPOSITORY_NAME=${GITHUB_REPOSITORY_NAME:-zesk}

  #
  # Preflight our environment to make sure we have the basics defined in the calling script
  #
  usageEnvironment "${requireEnvironment[@]}"

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

  consoleInfo "Started on $(date)"

  #==========================================================================================
  #
  # Install
  #
  aptInstall
  mariadbInstall

  usageWhich docker mariadb

    testShellScripts ! -path '*/vendor/*' ! -path '*/DEPRECATED/*'
  #==========================================================================================
  # Generate the .env artifact
  statusMessage consoleInfo "Generating environment artifact ... "
  export DEPLOY_USER_HOSTS=none
  export BUILD_TARGET=none
  bin/build/pipeline/make-env.sh "${buildEnvironment[@]}"

  #==========================================================================================
  #
  # Test our build assumptions
  #
  start=$(beginTiming)
  consoleInfo "Build bin smoke tests ... "
  ./bin/test-bin.sh

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
  } >>"$quietLog"
  reportTiming "$start" OK

  #==========================================================================================
  #
  # Load the test schema into our database
  #
  consoleInfo -n "Loading schema ... "
  start=$(beginTiming)
  if ! mariadb "${databaseArguments[@]}" <./docker/mariadb/schema.sql >>"$quietLog"; then
    failed "$quietLog"
    exit $errorBuild
  fi
  reportTiming "$start" OK

  #==========================================================================================
  #
  # Install vendor directory
  #
  if test $clean; then
    consoleInfo "Deleting ./vendor"
    [ ! -d "./vendor" ] || rm -rf "./vendor"
  fi
  ./bin/build/pipeline/composer.sh

  #==========================================================================================
  #
  # Build the Zesk docker image
  #
  cleanArgs=()
  if test "$clean"; then
    cleanArgs=("--no-cache" "--pull")
  fi
  ./bin/pipeline/zesk-container-latest.sh "${cleanArgs[@]}"

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
  docker run -v "$(pwd)/:/zesk" zesk:latest /zesk/bin/test-zesk.sh "$@"
  reportTiming "$start" Passed

  consoleInfo
  ./bin/build/pipeline/git-tag-version.sh "${versionArgs[@]}"
  consoleReset

  reportTiming "$startSetup" "Setup complete."
}

zeskPipelineSetup "$@"
