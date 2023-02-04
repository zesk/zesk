#!/usr/bin/env bash
#
# pipeline-release.sh
#
# Push a new tag to GitHub when then triggers a new release to Packagist automatically
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
ERR_ENV=1

#
# Assumptions
#
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." || exit $ERR_ENV; pwd)"

set -eo pipefail

if [ ! -d "$top/.git" ]; then
	echo "No .git directory at $top, stopping" 1>&2
	exit $ERR_ENV
fi

"$top/bin/build/git.sh"
"$top/bin/build/docker-compose.sh"

currentVersion=$("$top/bin/build/version-current.sh")
releaseDir=$top/docs/release/

currentChangeLog="$releaseDir/$currentVersion.md"
if [ ! -f "$currentChangeLog" ]; then
  echo "No $currentChangeLog" 1>&2
  exit "$ERR_ENV"
fi


figlet "zesk $currentVersion"
cat currentChangeLog
echo
echo "Tagging release in GitHub ..."
echo
yml="$top/docker-compose.yml"
{
  echo 'zesk\\GitHub\\Module::access_token="'"$GITHUB_ACCESS_TOKEN"'"'
  echo 'zesk\\GitHub\\Module::owner='"${GITHUB_REPOSITORY_OWNER:-zesk}"
  echo 'zesk\\GitHub\\Module::repository='"${GITHUB_REPOSITORY_NAME:-zesk}"
} > "$top/.github.conf"

docker-compose -f "$yml" -T -u www-data /zesk/bin/zesk --config /zesk/.github.conf GitHub --tag --description-file "$currentChangeLog"

echo
figlet "zesk $currentVersion OK"
