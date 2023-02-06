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
    echo "$@"
    echo -ne "$end"
  fi
}
consoleRed() {
  consoleCode '\033[31m' '\033[0m' "$@"
}
consoleBlue() {
  consoleCode '\033[94m' '\033[0m' "$@"
}
# shellcheck disable=SC2120
consoleMagenta() {
  consoleCode '\033[35m' '\033[0m' "$@"
}
consoleWhite() {
  consoleCode '\033[47m' '\033[0m' "$@"
}
consoleBold() {
  consoleCode '\033[1m' '\033[21m' "$@"
}
consoleUnderline() {
  consoleCode '\033[4m' '\033[24m' "$@"
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
# When things go badly
#
failed() {
  local quietLog=$1
  shift
  consoleRed "$(echoBar)"
    consoleWhite "Last 50 of $(consoleBold "$quietLog") ..."
  consoleRed "$(echoBar)"
  consoleWhite
    tail -50 "$quietLog"
    echo
  consoleRed
    echoBar
    figlet failed
  consoleRed "$(echoBar)"
    consoleWhite "Last 3 of $(consoleBold "$quietLog") ..."
  consoleRed "$(echoBar)"
  consoleMagenta
    tail -3 "$quietLog"
    echo
  consoleReset
  return $ERR_ENV
}
