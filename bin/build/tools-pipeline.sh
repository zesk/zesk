#!/usr/bin/env bash
#
# Tools for pipeline scripts (pipeline-*.sh)
#
# Copyright &copy; 2023 Market Ruler, LLC
#

err_env=1
relTop="../.."
if ! cd "$(dirname "${BASH_SOURCE[0]}")/$relTop"; then
  echo "$me: Can not cd to $relTop" 1>&2
  exit $err_env
fi
top="$(pwd)"
. "$top/bin/build/colors.sh"

#           ▗▀▖▗             ▐  ▗
#  ▞▀▖▞▀▖▛▀▖▐  ▄ ▞▀▌▌ ▌▙▀▖▝▀▖▜▀ ▄ ▞▀▖▛▀▖
#  ▌ ▖▌ ▌▌ ▌▜▀ ▐ ▚▄▌▌ ▌▌  ▞▀▌▐ ▖▐ ▌ ▌▌ ▌
#  ▝▀ ▝▀ ▘ ▘▐  ▀▘▗▄▘▝▀▘▘  ▝▀▘ ▀ ▀▘▝▀ ▘ ▘
#
aws_credentials=$HOME/.aws/credentials

# unused
dockerPHPExtensions() {
  local start
  start=$(beginTiming)
  consoleInfo -n "Installing PHP extensions ... "
  [ -d "$top/.build" ] || mkdir -p "$top/.build"
  docker-php-ext-install mysqli pcntl calendar >> "$top/.build/docker-php-ext-install.log"
  reportTiming "$start" Done
}

ipLookup() {
  if ! whichApt curl curl; then
    return $err_env
  fi
  curl -s "${IP_URL:-https://www.conversionruler.com/showip/?json}"
}

awsEnvironment() {
  set +u
  if [ -z "$AWS_ACCESS_KEY_ID" ]; then
    if [ -f "$aws_credentials" ]; then
      eval "$(awk -F= '/\[/{prefix=$0; next} $1 {print prefix " " $0}' "$aws_credentials" | grep "\[${RULER_AWS_GROUP}\]" | awk '{ print $2 $3 $4 }' OFS='')"
    fi
    if [ -n "$aws_access_key_id" ]; then
      export AWS_ACCESS_KEY_ID="${aws_access_key_id}"

      consoleInfo "Extracted identity from AWS credentials: ${AWS_ACCESS_KEY_ID}"
    fi
    if [ -n "${aws_secret_access_key}" ]; then
      export AWS_SECRET_ACCESS_KEY=$aws_secret_access_key
    fi
  fi
  set -u
}

veeGitTag() {
  local t="$1"

  if [ "$t" != "${t##v}" ]; then
    consoleRed "Tag is already veed: $t" 1>&2
    return 1
  fi
  git tag "v$t" "$t"
  git tag -d "$t"
  git push origin "v$t" ":$t"
  git fetch -q --prune --prune-tags
}
