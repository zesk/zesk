#!/usr/bin/env bash
#
# Run Zesk test interactively while debugging and fixing things
# Run usually inside a container
#
err_env=1

set -eo pipefail

PATH=$top/vendor/bin:$PATH

me=$(basename "$0")
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd || exit $err_env)"
phpunit_bin=vendor/bin/phpunit
coverage_path="$top/test-coverage"
junit_path="$top/test-results"

usage() {
  local code

  code=$1
  shift
  if [ $# -gt 0 ]; then
    echo $@
    echo
  fi
  exec 1>&2
  echo "$me: Run tests for zesk"
  echo
  echo "--coverage    Run coverage tests and output results into $coverage_path"
  echo "--stop        Stop on any error"
  echo
  echo "Additional arguments passed directly through to PHPUnit"
  exit "$code"
}

if [ ! -d "$top/vendor" ]; then
  if [ -f /usr/local/bin/zesk-bash.sh ]; then
    source /usr/local/bin/zesk-bash.sh
    if [ -z "$(which composer)" ]; then
      composer_install
    fi
  elif [ -z "$(which composer)" ]; then
    usage $err_env "No vendor directory or composer binary"
  fi
  cd "$top"
	composer install
  if [ ! -d "$top/vendor" ]; then
    echo "Possible read-only file system" 1>&2
    echo "Possible permissions issue" 1>&2
    usage "Composer did not create $top/vendor directory"
  fi
fi

need_paths=()
args=("--disallow-test-output")
doCoverage=
doStop=
coverage_cache=./.zesk-coverage
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

args+=("--display-warnings" "--colors=auto")
if test $doCoverage; then
  need_paths+=("$coverage_cache")
  args+=("--coverage-cache" "$coverage_cache")
  export XDEBUG_MODE=coverage
  echo "Enabling XDEBUG_MODE=coverage"
else
  args+=("--no-coverage")
fi
if test $doStop; then
  args+=("--stop-on-defect" "--stop-on-failure")
  export XDEBUG_MODE=coverage
  echo "Enabling XDEBUG_MODE=coverage"
fi
if [ ! -x "$phpunit_bin" ]; then
  echo "$phpunit_bin does not exist or is not executable" 1>&2
  exit $err_env
fi
need_paths+=("$junit_path")
for d in "${need_paths[@]}"; do
  [ -d "$d" ] || mkdir -p "$d"
done
args+=("--log-junit" "$junit_path/junit.xml")
echo "$phpunit_bin" "${args[@]}" "$@"
exec "$phpunit_bin" "${args[@]}" "$@"
