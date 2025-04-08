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

  set -eou pipefail

  top=$(__catchEnvironment "$usage" "buildHome") || return $?

  export PATH

  pathConfigure --last "$top/vendor/bin"

  local doCoverage=false doStop=false coverage_cache="./.zesk-coverage"

  __catchEnvironment "$usage" muzzle pushd "$top" || return $?

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

  local needPaths=() args=()
  args+=("--disallow-test-output")
  args+=("--display-warnings" "--colors=auto")
  if $doCoverage; then
    needPaths+=("$coverage_cache")
    args+=("--coverage-cache" "$coverage_cache")
    export XDEBUG_MODE=coverage
    statusMessage decorate info "Enabling XDEBUG_MODE=coverage"
  else
    args+=("--no-coverage")
  fi
  if $doStop; then
    args+=("--stop-on-defect" "--stop-on-failure")
    export XDEBUG_MODE=coverage
    statusMessage decorate info "Enabling XDEBUG_MODE=coverage"
  fi

  local testBinary="vendor/bin/phpunit" testResultPath="$top/test-results"

  args+=("--log-junit" "$testResultPath/junit.xml")

  [ -x "$testBinary" ] || __throwEnvironment "$usage" "$testBinary does not exist or is not executable" || return $?

  needPaths+=("$testResultPath")

  muzzle __catchEnvironment "$usage" requireDirectory "${needPaths[@]}" || return $?

  __execute "$testBinary" "${args[@]}" "$@" || return $?
}
_zeskTest() {
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
