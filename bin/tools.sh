#!/usr/bin/env bash
#
# Copyright &copy; 2024 Market Acumen, Inc.
#

# IDENTICAL errorArgument 1
errorArgument=2

# IDENTICAL errorEnvironment 1
errorEnvironment=1

loadBuild() {
  local here

  if ! here="$(dirname "${BASH_SOURCE[0]}")"; then
    printf "%s\n" "dirname failed" 1>&2
    return $errorEnvironment
  fi
  if [ ! -d "$here/build" ]; then
    if ! "$here/install-bin-build.sh"; then
      printf "%s\n" "install-bin-build.sh failed" 1>&2
      return $errorEnvironment
    fi
    if [ ! -d "$here/build" ]; then
      printf "%s\n" "install-bin-build.sh did not create build directory" 1>&2
      return $errorEnvironment
    fi
  fi

  if [ "$(basename "${0##-}")" = "$(basename "${BASH_SOURCE[0]}")" ] && [ $# -gt 0 ]; then
    # Only require when running as a shell command
    set -eou pipefail
    # Run remaining command line arguments
    "$@"
  fi

}

#
# Usage: {fn} databaseHost [ dockerBuildArgs ... ]
# Argument: databaseHost - String. Required. Database host.
# Argument: dockerBuildArgs - Any additional arguments are passed directly to `docker build`
#
zeskContainerLatest() {
  local host quietLog start

  quietLog=$(buildQuietLog "${FUNCNAME[0]}")
  start=$(beginTiming)

  host="$1"
  shift || :
  if [ -z "$host" ]; then
    _zeskContainerLatest $errorArgument "Missing host"
  fi
  decorate info -n "Build container zesk:latest ... "
  figlet "Build container" >>"$quietLog"
  if ! docker build "$@" --build-arg "DATABASE_HOST=$host" -f ./docker/php.Dockerfile --tag zesk:latest . >>"$quietLog" 2>&1; then
    buildFailed "$quietLog"
    _zeskContainerLatest "$errorEnvironment" "docker build failed" || return $?
  fi
  reportTiming "$start" "Zesk latest container built in"
}

loadBuild "$@"
