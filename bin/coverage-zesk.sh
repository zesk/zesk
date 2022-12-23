#!/bin/bash
#
# Run Zesk test interactively while debugging and fixing things
# Run usually inside a container
#
set -eo pipefail

ERR_ENV=1

top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd || exit)"
PATH=$top/vendor/bin:$PATH

if [ ! -d $top/vendor ]; then
  source /usr/local/bin/zesk-bash.sh
	composer_install
	cd "$top"
	composer install
fi
#opts=
#opts="$opts --debug"
#opts="$opts --verbose"
#opts="$opts --sandbox"
#opts="$opts --interactive"
#opts="$opts --debug-command"
# [ -d "$HOME/.zesk" ] || mkdir -p "$HOME/.zesk"
cd "$top"
phpunit_bin=vendor/bin/phpunit
coverage_path=./test-coverage
junit_path=./test-results
junit_results_file=$junit_path/junit.xml

if [ ! -x "$phpunit_bin" ]; then
  echo "$phpunit_bin does not exist or is not executable" 1>&2
  exit $ERR_ENV
fi
for d in "$coverage_path" "$junit_path"; do
  [ -d $d ] || mkdir -p $d
done
"$phpunit_bin" --coverage-filter ./classes \
  --coverage-filter ./modules \
  --coverage-filter ./theme \
  --coverage-filter ./theme \
  --coverage-html "$coverage_path"
