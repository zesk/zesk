#!/usr/bin/env bash

consoleReset() {
  echo -en '\033[0m' # Reset
}

consoleCode() {
  local start=$1 end=$2
  shift
  shift
  if [ -z "$*" ]; then
    echo -ne "$start"
  else
    echo -ne "$start"
    echo -n "$@"
    echo -ne "$end"
    echo
  fi
}
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
  return $err_env
}
