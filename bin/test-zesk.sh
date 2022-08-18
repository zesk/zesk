#!/bin/bash
#
# Run Zesk test interactively while debugging and fixing things
# Run usually inside a container
#
set -eo pipefail
PATH=$top/vendor/bin:$PATH

top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd || exit)"
err_env=1
err_arg=2
phpunit_bin=vendor/bin/phpunit
coverage_path=./test-coverage
junit_path=./test-results
junit_results_file=$junit_path/junit.xml

usage() {
  local code

  code=$1
  shift
  if [ $# -gt 0 ]; then
    echo $@
    echo
  fi
  echo $me: Run tests for zesk
  echo
  echo "--coverage    Run coverage tests and output results into $coverage_path"
  exit $code
}

if [ ! -d $top/vendor ]; then
  source /usr/local/bin/zesk-bash.sh
	composer_install
	cd "$top"
	composer install
fi

need_paths=
args=
coverage_path=./test-coverage
coverage_cache=./.zesk-coverage
cd "$top"
while [ $# -ge 1 ]; do
  case $1 in
    --coverage)
      need_paths="$need_paths $coverage_cache $coverage_path"
      args="$args --coverage-cache $coverage_cache --coverage-filter ./classes --coverage-filter ./command --coverage-filter ./modules --coverage-html $coverage_path"
      export XDEBUG_MODE=coverage
      echo "Running coverage and storing results in $coverage_path"
      ;;
    *)
      usage $err_arg "Unknown arg $1"
      ;;
  esac
  shift
done

if [ ! -x "$phpunit_bin" ]; then
  echo "$phpunit_bin does not exist or is not executable" 1>&2
  exit $err_env
fi
need_paths="$need_paths $junit_path"
for d in $need_paths; do
  [ -d $d ] || mkdir -p $d
done

"$phpunit_bin" $args --log-junit "$junit_results_file"
