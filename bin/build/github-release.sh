#!/usr/bin/env bash
#
# docker-compose.sh
#
# Depends: pip python
#
# install docker-compose and requirements
#
# Copyright &copy; 2023 Market Acumen, Inc.

#
#  ▞▀▖      ▗▀▖▗             ▐  ▗
#  ▌  ▞▀▖▛▀▖▐  ▄ ▞▀▌▌ ▌▙▀▖▝▀▖▜▀ ▄ ▞▀▖▛▀▖
#  ▌ ▖▌ ▌▌ ▌▜▀ ▐ ▚▄▌▌ ▌▌  ▞▀▌▐ ▖▐ ▌ ▌▌ ▌
#  ▝▀ ▝▀ ▘ ▘▐  ▀▘▗▄▘▝▀▘▘  ▝▀▘ ▀ ▀▘▝▀ ▘ ▘
requireEnvironment=(GITHUB_ACCESS_TOKEN GITHUB_REPOSITORY_OWNER GITHUB_REPOSITORY_NAME)

#
# Exit codes
#
err_env=1
err_arg=2

me=$(basename "$0")
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $err_env; pwd)"
quietLog="$top/.build/$me.log"
set -eo pipefail
. "$top/bin/build/colors.sh"

usage() {
  local rs=$1
  shift
  consoleRed "$*"
  echo
  consoleInfo "$me descriptionFilePath releaseName - Generate a release on GitHub using API"
  echo
  exit "$rs"
}
[ -d "$(dirname "$quietLog")" ] || mkdir -p "$(dirname "$quietLog")"

if [ ! -f "$1" ]; then
  usage "$err_arg" "Pass in description file as first argument"
fi
export descriptionFile="$1"
shift

if [ -z "$1" ]; then
  usage "$err_arg" "Pass in release name file"
fi
export releaseName="$1"
shift

#
# Preflight our environment to make sure we have the basics defined in the calling script
#
for e in "${requireEnvironment[@]}"; do
  if [ -z "${!e}" ]; then
    consoleError "Need to have $e defined in pipeline" 1>&2
    exit $err_env
  fi
done

if ! which curl 2> /dev/null 1>&2; then
  "$top/bin/build/apt-utils.sh"
  if ! apt-get install -q curl > "$quietLog"; then
    consoleError "Failed to install curl"
    failed "$quietLog"
  fi
fi

JSON='{"draft":false,"prerelease":false,"generate_release_notes":false}'
JSON="$(echo "$JSON" | jq --arg name "$releaseName" --rawfile desc "$descriptionFile" '. + {body: $desc, tag_name: $name, name: $name}')"

curl -L \
  -X POST \
  -H "Accept: application/vnd.github+json" \
  -H "Authorization: token $GITHUB_ACCESS_TOKEN"\
  -H "X-GitHub-Api-Version: 2022-11-28" \
  "https://api.github.com/repos/$GITHUB_REPOSITORY_OWNER/$GITHUB_REPOSITORY_NAME/releases" \
  -d "$JSON"
