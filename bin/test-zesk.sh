#!/usr/bin/env bash
#
# Run Zesk test interactively while debugging and fixing things
# Run usually inside a container
#
# Copyright &copy; 2024 Market Acumen, Inc.
#

set -eou pipefail

# shellcheck source=/dev/null
source "${BASH_SOURCE[0]%/*}/build/tools.sh" || exit 99

# Run tests for zesk"
#
# Argument: --coverage - Flag. Optional. Run coverage tests and output results to `./test-coverage`
# Argument: --coverage - Flag. Optional. Stop on any error.
# Additional arguments passed directly through to PHPUnit
#
zeskTest() {
  local usage="_${FUNCNAME[0]}"
  local top testBinary testResultPath

  top=$(__usageEnvironment "$usage" "buildHome") || return $?

  export PATH
  pathAppend "$top/vendor/bin"

  testBinary=vendor/bin/phpunit
  testResultPath="$top/test-results"

  __usageEnvironment "$usage" composerInstall || return $?

  local need_paths=() args=() doCoverage=false doStop=false coverage_cache="./.zesk-coverage"

  cd "$top"
  while [ $# -ge 1 ]; do
    case $1 in
      --coverage)
        doCoverage=1
        ;;
      --stop)
        doStop=1
        ;;
      *)
        break
        ;;
    esac
    shift
  done

  args+=("--disallow-test-output")
  args+=("--display-warnings" "--colors=auto")
  if test $doCoverage; then
    need_paths+=("$coverage_cache")
    args+=("--coverage-cache" "$coverage_cache")
    export XDEBUG_MODE=coverage
    statusMessage decorate info "Enabling XDEBUG_MODE=coverage"
  else
    args+=("--no-coverage")
  fi
  if test $doStop; then
    args+=("--stop-on-defect" "--stop-on-failure")
    export XDEBUG_MODE=coverage
    statusMessage decorate info "Enabling XDEBUG_MODE=coverage"
  fi
  [ -x "$testBinary" ] || __failEnvironment "$usage" "$testBinary does not exist or is not executable" || return $?
  need_paths+=("$testResultPath")
  __usageEnvironment "$usage" requireDirectory "${need_paths[@]}" || return $?
  args+=("--log-junit" "$testResultPath/junit.xml")
  __execute "$testBinary" "${args[@]}" "$@"
}
_zeskTest() {
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

zeskTest "$@"
