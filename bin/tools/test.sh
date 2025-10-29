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
  local handler="_${FUNCNAME[0]}"
  local top

  local cleanFlag=false doCoverage=false doStop=false

  # _IDENTICAL_ argumentNonBlankLoopHandler 6
  local __saved=("$@") __count=$#
  while [ $# -gt 0 ]; do
    local argument="$1" __index=$((__count - $# + 1))
    # __IDENTICAL__ __checkBlankArgumentHandler 1
    [ -n "$argument" ] || throwArgument "$handler" "blank #$__index/$__count ($(decorate each quote "${__saved[@]}"))" || return $?
    case "$argument" in
    # _IDENTICAL_ helpHandler 1
    --help) "$handler" 0 && return $? || return $? ;;
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

  top=$(catchReturn "$handler" buildHome) || return $?

  #  export PATH

  # pathConfigure --last "$top/vendor/bin"

  if [ -f "$top/.env" ]; then
    catchReturn "$handler" environmentFileLoad "$top/.env" || return $?
    statusMessage decorate info "Loaded $(decorate file "$top/.env")"
  fi

  if $cleanFlag; then
    statusMessage decorate warning "Cleaning all caches (not vendor) ..."
    for item in "$top/cache/" "$(buildCacheDirectory)" "$top/.fileCache" "$top/test-results/" "$top/test-reports/"; do
      if [ -e "$item" ]; then
        local thing=file
        [ ! -d "$item" ] || thing=directory
        statusMessage decorate warning "Deleted temporary $thing $(decorate file "$item")"
        catchEnvironment "$handler" rm -rf "$item" || return $?
      fi
    done
  fi
  catchEnvironment "$handler" muzzle pushd "$top" || return $?

  local needPaths=() args=()
  args+=("--disallow-test-output")
  args+=("--display-warnings" "--colors=auto")
  if $doCoverage; then
    local home
    home=$(catchReturn "$handler" buildHome) || return $?

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
  __verifyDatabase "$handler" || return $?

  local testBinary="vendor/bin/phpunit" testResultPath="$top/test-results"

  cacheDirectory=$(catchReturn "$handler" buildCacheDirectory "zeskTest") || return $?
  args+=("--log-junit" "$testResultPath/junit.xml")
  args+=("--order-by=defects" "--cache-result" "--cache-directory" "$cacheDirectory")

  [ -x "$testBinary" ] || throwEnvironment "$handler" "$testBinary does not exist or is not executable" || return $?

  needPaths+=("$testResultPath")

  muzzle catchReturn "$handler" directoryRequire "${needPaths[@]}" || return $?

  execute "$testBinary" "${args[@]}" "$@" || return $?
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
  local handler="$1" version user

  export DATABASE_HOST DATABASE DATABASE_ROOT_PASSWORD DATABASE_ROOT_USER DATABASE_TEST_NAME

  DATABASE_TEST_NAME=${DATABASE_TEST_NAME-zeskTest}
  if ! whichExists mariadb; then
    throwEnvironment "$handler" "Missing mariadb" || return $?
  fi
  user=${DATABASE_ROOT_USER-root}
  DATABASE_ROOT_USER=$user

  version=$(printf "%s\n" "SELECT VERSION()" | __query 2>/dev/null || :)
  if [ -z "$version" ]; then
    throwEnvironment "$handler" "Unable to connect to $DATABASE_HOST as $user" || return $?
  fi
  printf "%s\n" "CREATE DATABASE IF NOT EXISTS $DATABASE_TEST_NAME" | catchReturn "$handler" __query || return $?
}
