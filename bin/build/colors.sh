#!/usr/bin/env bash
#
# Shell colors
#
# Usage: source ./bin/build/colors.sh
#
# Depends: -
#
err_env=1
# err_arg=2

consoleReset() {
  echo -en '\033[0m' # Reset
}

consoleCode() {
  local start=$1 end=$2 nl=1
  shift
  shift
  if [ "$1" = "-n" ]; then
    nl=
    shift
  fi
  if [ -z "$*" ]; then
    echo -ne "$start"
  else
    echo -ne "$start"
    echo -n "$@"
    echo -ne "$end"
    if test "$nl"; then
      echo
    fi
  fi
}
#
# Color-based
#
consoleRed() {
  consoleCode '\033[31m' '\033[0m' "$@"
}
consoleGreen() {
  consoleCode '\033[92m' '\033[0m' "$@"
}
consoleCyan() {
  consoleCode '\033[36m' '\033[0m' "$@"
}
consoleBlue() {
  consoleCode '\033[94m' '\033[0m' "$@"
}
consoleBlackBackground() {
  consoleCode '\033[100m' '\033[0m' "$@"
}
consoleYellow() {
  consoleCode '\033[93m' '\033[0m' "$@"
}
# shellcheck disable=SC2120
consoleMagenta() {
  consoleCode '\033[35m' '\033[0m' "$@"
}
consoleBlack() {
  consoleCode '\033[30m' '\033[0m' "$@"
}
consoleWhite() {
  consoleCode '\033[37m' '\033[0m' "$@"
}
consoleBoldMagenta() {
  consoleCode '\033[1m\033[35m' '\033[0m' "$@"
}
consoleUnderline() {
  consoleCode '\033[4m' '\033[24m' "$@"
}
consoleBold() {
  consoleCode '\033[1m' '\033[21m' "$@"
}
consoleRedBold() {
  consoleCode '\033[31m' '\033[0m' "$@"
}
consoleNoBold() {
  echo -en '\033[21m'
}
consoleNoUnderline() {
  echo -en '\033[24m'
}
echoBar() {
  echo "======================================================="
}
#
# Semantics-based
#
consoleInfo() {
  consoleCyan "$@"
}
consoleSuccess() {
  consoleGreen "$@"
}
consoleDecoration() {
  consoleBoldMagenta "$@"
}
consoleError() {
  consoleCode '\033[1;31m' '\033[0m' "$@"
}
#
# When things go badly
#
failed() {
  local quietLog=$1
  shift
  echo; consoleRed
  consoleRed && echoBar
    echo "$(consoleBold "$quietLog")" "$(consoleBlack ": Last 50 lines ...")"
  consoleRed && echoBar
  consoleYellow; consoleBlackBackground
    tail -50 "$quietLog"
  consoleReset
    echo
  consoleRed
    echoBar
    figlet failed
  consoleRed "$(echoBar)"
    echo "$(consoleBold "$quietLog")" "$(consoleBlack ": Last 3 lines ...")"
  consoleRed "$(echoBar)"
  consoleMagenta
    tail -3 "$quietLog"
    echo
  consoleReset
  return "$err_env"
}

beginTiming() {
  echo "$(($(date +%s) + 0))"
}
plural() {
  if [ "$1" -eq 1 ]; then
    echo "$2";
  else
    echo "$3"
  fi
}
reportTiming() {
  local start delta
  start=$1
  shift
  if [ -n "$*" ]; then
    consoleGreen -n "$* "
  fi
  delta=$(($(date +%s) - start))
  consoleBoldMagenta "$delta $(plural $delta second seconds)"
}
versionSort() {
  sort -t. -k 1.2,1n -k 2,2n -k 3,3n -k 4,4n
}
