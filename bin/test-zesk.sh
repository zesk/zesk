#!/usr/bin/env bash
#
# Run Zesk test interactively while debugging and fixing things
# Run usually inside a container
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

set -eou pipefail

# shellcheck source=/dev/null
source "${BASH_SOURCE[0]%/*}/build/tools.sh" || exit 99

# Run tests for zesk"
# Usage: {fn} [ --coverage ] [ --stop ] testBinaryArguments ...
# Argument: --coverage - Flag. Optional. Run coverage tests and output results to `./test-coverage`
# Argument: --stop - Flag. Optional. Stop on any error.
# Additional arguments passed directly through to PHPUnit
#
zeskTest() {
  local usage="_${FUNCNAME[0]}"
  local top

  top=$(__usageEnvironment "$usage" "buildHome") || return $?

  export PATH

  pathAppend "$top/vendor/bin"

  local doCoverage=false doStop=false coverage_cache="./.zesk-coverage"

  __usageEnvironment "$usage" composerInstall || return $?
  __usageEnvironment "$usage" pushd "$top" || return $?

  # IDENTICAL argument-case-header 5
  local saved=("$@") nArguments=$#
  while [ $# -gt 0 ]; do
    local argument argumentIndex=$((nArguments - $# + 1))
    argument="$(usageArgumentString "$usage" "argument #$argumentIndex (Arguments: $(_command "${usage#_}" "${saved[@]}"))" "$1")" || return $?
    case "$argument" in
      # IDENTICAL --help 4
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
    # IDENTICAL argument-esac-shift 1
    shift || __failArgument "$usage" "missing argument #$argumentIndex: $argument (Arguments: $(_command "${usage#_}" "${saved[@]}"))" || return $?
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

  [ -x "$testBinary" ] || __failEnvironment "$usage" "$testBinary does not exist or is not executable" || return $?

  needPaths+=("$testResultPath")

  __usageEnvironment "$usage" requireDirectory "${needPaths[@]}" || return $?

  __execute "$testBinary" "${args[@]}" "$@"
}
_zeskTest() {
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

zeskTest "$@"
