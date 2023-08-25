#!/usr/bin/env bash
#
# aws-cli.sh
#
# Depends: apt
#
# aws cli install
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
set -eo pipefail

err_env=1

me=$(basename "$0")
relTop="../.."
if ! cd "$(dirname "${BASH_SOURCE[0]}")/$relTop"; then
  echo "$me: Can not cd to $relTop" 1>&2
  exit $err_env
fi
top="$(pwd)"
quietLog="$top/.build/$me.log"

. "$top/bin/build/colors.sh"

if ! which aws 2> /dev/null 1>&2; then
  "$top/bin/build/apt-utils.sh"

  [ -d "$(dirname "$quietLog")" ] || mkdir -p "$(dirname "$quietLog")"

  consoleInfo -n "Installing aws-cli ... "
  start=$(beginTiming)
  if ! curl -s "https://awscli.amazonaws.com/awscli-exe-linux-x86_64.zip" -o "awscliv2.zip" >> "$quietLog"; then
    failed "$quietLog"
  fi
  if ! unzip awscliv2.zip >> "$quietLog"; then
    failed "$quietLog"
  fi
  if ! ./aws/install >> "$quietLog"; then
    failed "$quietLog"
  fi
  rm -rf ./aws/ awscliv2.zip
  consoleValue -n "$(aws --version) "
  reportTiming "$start" OK
fi
