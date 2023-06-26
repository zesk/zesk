#!/usr/bin/env bash
#
# pipeline-release.sh
#
# Push a new tag to GitHub when then triggers a new release to Packagist automatically
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
err_env=1

#
# Assumptions
#
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." || exit $err_env; pwd)"

set -eo pipefail

source "$top/bin/build/colors.sh"

if [ -z "$GITHUB_ACCESS_TOKEN" ]; then
  exec 1>&2
  consoleRed "GITHUB_ACCESS_TOKEN is required";
  echo
  exit $err_env
fi
if [ ! -d "$top/.git" ]; then
	echo "No .git directory at $top, stopping" 1>&2
	exit $err_env
fi

"$top/bin/build/git.sh"
"$top/bin/build/docker-compose.sh"

currentVersion=$("$top/bin/build/version-current.sh")
releaseDir=$top/docs/release/

currentChangeLog="$releaseDir/$currentVersion.md"
if [ ! -f "$currentChangeLog" ]; then
  echo "No $currentChangeLog" 1>&2
  exit "$err_env"
fi

GITHUB_REPOSITORY_OWNER=${GITHUB_REPOSITORY_OWNER:-zesk}
GITHUB_REPOSITORY_NAME=${GITHUB_REPOSITORY_NAME:-zesk}

figlet "zesk $currentVersion"
cat currentChangeLog
echo
echo "Tagging release in GitHub ..."
echo
yml="$top/docker-compose.yml"
{
  echo 'zesk\\GitHub\\Module::access_token="'"$GITHUB_ACCESS_TOKEN"'"'
  echo 'zesk\\GitHub\\Module::owner='"$GITHUB_REPOSITORY_OWNER"
  echo 'zesk\\GitHub\\Module::repository='"$GITHUB_REPOSITORY_NAME"
} > "$top/.github.conf"

consoleGreen "Tagging $currentVersion and pushing ... "
git tag "$currentVersion"
git push --tags
consoleGreen OK && echo

git push --mirror "https://github.com/$GITHUB_REPOSITORY_OWNER/$GITHUB_REPOSITORY_NAME.git"
docker-compose -f "$yml" -T -u www-data /zesk/bin/zesk --config /zesk/.github.conf GitHub --tag --description-file "$currentChangeLog"

echo
figlet "zesk $currentVersion OK"
