#!/usr/bin/env bash
#
# test-bin.sh
#
# Sanity check on scripts - any functions in build should be tested here
#
# Copyright &copy; 2024 Market Acumen, Inc.
#

#
# Exit codes
#
err_build=1000

#
# Variables and constants
#
export TERM=xterm
export DEBIAN_FRONTEND=noninteractive

cd "$(dirname "${BASH_SOURCE[0]}")/.."

set -eo pipefail

# shellcheck source=/dev/null
. ./bin/build/tools.sh

results=$(mktemp)

cleanup() {
  [ -f "$results" ] && rm -f "$results"
}
trap cleanup EXIT

decorate info -n "Testing versionSort ... "
prefix=
for prefix in 0 1; do
  testFile="./bin/test/$prefix-versionSort.test.txt"
  versionSort <"$testFile" >"$results"
  if ! diff -q "$results" "./bin/test/$prefix-versionSort.txt"; then
    diff "$results" "./bin/test/$prefix-versionSort.txt"
    decorate error "versionSort failed: $testFile"
    exit $err_build
  fi
  versionSort -r <"$testFile" >"$results"
  if ! diff -q "$results" "./bin/test/$prefix-versionSort-r.txt"; then
    diff "$results" "./bin/test/$prefix-versionSort.txt"
    decorate error "versionSort reverse failed: $testFile"
    exit $err_build
  fi
done
decorate success OK

decorate success "Build tests passed ..."
