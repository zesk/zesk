#!/bin/bash
#
# Run Zesk test interactively while debugging and fixing things
# Run usually inside a container
#
ERR_ENV=1

set -eo pipefail

PATH=$top/vendor/bin:$PATH

me=$(basename "$0")
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd || exit $ERR_ENV)"
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
    usage $ERR_ENV "No vendor directory or composer binary"
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
coverage_cache=./.zesk-coverage
cd "$top"
while [ $# -ge 1 ]; do
  case $1 in
    --coverage)
      doCoverage=1
      ;;
    *)
      break
      ;;
  esac
  shift
done

if test $doCoverage; then
  need_paths+=("$coverage_cache")
  args+=("--coverage-cache" "$coverage_cache")
  export XDEBUG_MODE=coverage
  echo "Enabling XDEBUG_MODE=coverage"
else
  args+=("--no-coverage")
fi
if [ ! -x "$phpunit_bin" ]; then
  echo "$phpunit_bin does not exist or is not executable" 1>&2
  exit $ERR_ENV
fi
need_paths+=("$junit_path")
for d in "${need_paths[@]}"; do
  [ -d "$d" ] || mkdir -p "$d"
done
echo "$phpunit_bin" "${args[@]}" "$@"
"$phpunit_bin" "${args[@]}" "$@"
