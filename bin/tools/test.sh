#!/usr/bin/env bash
#
# Run Zesk test interactively while debugging and fixing things
# Run usually inside a container
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

# Run tests for Zesk for PHP
#
# Usage: {fn} [ --coverage ] [ --stop ] testBinaryArguments ...
# Argument: --coverage - Flag. Optional. Run coverage tests and output results to `./test-coverage`
# Argument: --stop - Flag. Optional. Stop on any error.
# Additional arguments passed directly through to PHPUnit
#
zeskTest() {
  local usage="_${FUNCNAME[0]}"
  local top

  local cleanFlag=false doCoverage=false doStop=false

  # _IDENTICAL_ argument-case-header 5
  local __saved=("$@") __count=$#
  while [ $# -gt 0 ]; do
    local argument="$1" __index=$((__count - $# + 1))
    [ -n "$argument" ] || __throwArgument "$usage" "blank #$__index/$__count ($(decorate each quote "${__saved[@]}"))" || return $?
    case "$argument" in
      # _IDENTICAL_ --help 4
      --help)
        "$usage" 0
        return $?
        ;;
      --clean)
        cleanFlag=true
        ;;
      --coverage)
        doCoverage=true
        ;;
      --stop)
        doStop=true
        ;;
      *)
        break
        ;;
    esac
    # _IDENTICAL_ argument-esac-shift 1
    shift
  done

  set -eou pipefail

  top=$(__catchEnvironment "$usage" "buildHome") || return $?

  #  export PATH

  # pathConfigure --last "$top/vendor/bin"

  if [ -f "$top/.env" ]; then
    __catch "$usage" environmentFileLoad "$top/.env" || return $?
    statusMessage decorate info "Loaded $(decorate file "$top/.env")"
  fi

  if $cleanFlag; then
    statusMessage decorate warning "Cleaning all caches (not vendor) ..."
    for item in "$top/cache/" "$(buildCacheDirectory)" "$top/.fileCache" "$top/test-results/" "$top/test-reports/"; do
      if [ -e "$item" ]; then
        local thing=file
        [ ! -d "$item" ] || thing=directory
        statusMessage decorate warning "Deleted temporary $thing $(decorate file "$item")"
        __catchEnvironment "$usage" rm -rf "$item" || return $?
      fi
    done
  fi
  __catchEnvironment "$usage" muzzle pushd "$top" || return $?

  local needPaths=() args=()
  args+=("--disallow-test-output")
  args+=("--display-warnings" "--colors=auto")
  if $doCoverage; then
    local home
    home=$(__catch "$usage" buildHome) || return $?

    # coverage_cache="./.zesk-coverage"
    # needPaths+=("$coverage_cache" "$home/test-coverage")
    needPaths+=("$home/test-coverage")
    args+=("--coverage-html" "$home/test-coverage")
    export XDEBUG_MODE=coverage
    statusMessage decorate info "Enabling XDEBUG_MODE=coverage"
  else
    args+=("--no-coverage")
  fi
  if $doStop; then
    args+=("--stop-on-defect" "--stop-on-failure")
    export XDEBUG_MODE=debug
    #    statusMessage decorate info "Enabling XDEBUG_MODE=coverage"
  fi
  args+=("--display-skipped")

  args+=("--log-events-text" "$top/phpunit.log")
  __verifyDatabase "$usage" || return $?

  local testBinary="vendor/bin/phpunit" testResultPath="$top/test-results"

  cacheDirectory=$(__catch "$usage" buildCacheDirectory "zeskTest") || return $?
  args+=("--log-junit" "$testResultPath/junit.xml")
  args+=("--order-by=defects" "--cache-result"  "--cache-directory" "$cacheDirectory" )

  [ -x "$testBinary" ] || __throwEnvironment "$usage" "$testBinary does not exist or is not executable" || return $?

  needPaths+=("$testResultPath")

  muzzle __catch "$usage" directoryRequire "${needPaths[@]}" || return $?

  __execute "$testBinary" "${args[@]}" "$@" || return $?
}
_zeskTest() {
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

__query() {
  local user
  export DATABASE_HOST DATABASE DATABASE_ROOT_PASSWORD DATABASE_ROOT_USER
  user=${DATABASE_ROOT_USER-root}
  mariadb -u "$user" -p"$DATABASE_ROOT_PASSWORD" -h "$DATABASE_HOST" -N
}

__verifyDatabase() {
  local usage="$1" version user

  export DATABASE_HOST DATABASE DATABASE_ROOT_PASSWORD DATABASE_ROOT_USER DATABASE_TEST_NAME

  DATABASE_TEST_NAME=${DATABASE_TEST_NAME-zeskTest}
  if ! whichExists mariadb; then
    __throwEnvironment "$usage" "Missing mariadb" || return $?
  fi
  user=${DATABASE_ROOT_USER-root}
  DATABASE_ROOT_USER=$user

  version=$(printf "%s\n" "SELECT VERSION()" | __query 2>/dev/null || :)
  if [ -z "$version" ]; then
    __throwEnvironment "$usage" "Unable to connect to $DATABASE_HOST as $user" || return $?
  fi
  printf "%s\n" "CREATE DATABASE IF NOT EXISTS $DATABASE_TEST_NAME" | __catch "$usage" __query || return $?
}
