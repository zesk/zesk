#
# test-bin.sh
#
# Sanity check on scripts - any functions in build should be tested here
#
# Copyright &copy; 2023 Market Acumen, Inc.
#

#
# Exit codes
#
err_env=1
err_build=1000

#
# Variables and constants
#
# Debug bash - set -x
export TERM=xterm
export DEBIAN_FRONTEND=noninteractive
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." || exit $err_env; pwd)"

set -eo pipefail

. "$top/bin/build/colors.sh"

results=$(mktemp)

cleanup() {
  [ -f "$results" ] && rm -f "$results"
}
trap cleanup EXIT

consoleInfo -n "Testing versionSort ... "
prefix=
for prefix in 0 1; do
  testFile="$top/bin/test/$prefix-versionSort.test.txt"
  versionSort < "$testFile" > "$results"
  if ! diff -q "$results" "$top/bin/test/$prefix-versionSort.txt"; then
    diff "$results" "$top/bin/test/$prefix-versionSort.txt"
    consoleError "versionSort failed: $testFile"
    exit $err_build
  fi
  versionSort -r < "$testFile" > "$results"
  if ! diff -q "$results" "$top/bin/test/$prefix-versionSort-r.txt"; then
    diff "$results" "$top/bin/test/$prefix-versionSort.txt"
    consoleError "versionSort reverse failed: $testFile"
    exit $err_build
  fi
done
consoleSuccess OK

consoleSuccess "Build tests passed ..."
