#!/usr/bin/env bash
#
# pipeline-release.sh
#
# Depends: apt docker
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
# "$top/bin/build/docker-compose.sh"

"$top/bin/build/composer.sh"

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
cat "$currentChangeLog"
echo
echo "Tagging release in GitHub ..."
echo
yml="$top/docker-compose.yml"
{
  echo 'zesk\\GitHub\\Module::access_token="'"$GITHUB_ACCESS_TOKEN"'"'
  echo 'zesk\\GitHub\\Module::owner='"$GITHUB_REPOSITORY_OWNER"
  echo 'zesk\\GitHub\\Module::repository='"$GITHUB_REPOSITORY_NAME"
} > "$top/.github.conf"

ssh-keyscan github.com >> "$HOME/.ssh/known_hosts"
git remote add github "git@github.com:$GITHUB_REPOSITORY_OWNER/$GITHUB_REPOSITORY_NAME.git"
git push github
docker compose -f "$yml" -T -u www-data /zesk/bin/zesk --config /zesk/.github.conf GitHub --tag --description-file "$currentChangeLog"

consoleGreen "Pull github and push origin ... "
git pull github
git push origin

consoleGreen "Tagging $currentVersion and pushing ... "
git tag "$currentVersion"
git push origin --tags
git push github --tags
consoleGreen OK && echo

echo
figlet "zesk $currentVersion OK"
