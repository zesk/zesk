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
"$top/bin/build/composer.sh"

currentVersion=$("$top/bin/build/version-current.sh")
releaseDir=$top/docs/release

currentChangeLog="$releaseDir/$currentVersion.md"
if [ ! -f "$currentChangeLog" ]; then
  echo "No $currentChangeLog" 1>&2
  exit "$err_env"
fi

GITHUB_REPOSITORY_OWNER=${GITHUB_REPOSITORY_OWNER:-zesk}
GITHUB_REPOSITORY_NAME=${GITHUB_REPOSITORY_NAME:-zesk}

remoteChangeLogName=".release-notes.md"
remoteChangeLog="$top/$remoteChangeLogName"
{
  figlet "zesk $currentVersion" | awk '{ print "    " $0 }'
  echo
  cat "$currentChangeLog"
} >> "$remoteChangeLog"
figlet "zesk $currentVersion"
echo
echo "Tagging release in GitHub ..."
echo
{
  echo 'zesk\\GitHub\\Module::access_token="'"$GITHUB_ACCESS_TOKEN"'"'
  echo 'zesk\\GitHub\\Module::owner='"$GITHUB_REPOSITORY_OWNER"
  echo 'zesk\\GitHub\\Module::repository='"$GITHUB_REPOSITORY_NAME"
} > "$top/.github.conf"

commitish=$(git rev-parse --short HEAD)
ssh-keyscan github.com >> "$HOME/.ssh/known_hosts" 2> /dev/null
git remote add github "git@github.com:$GITHUB_REPOSITORY_OWNER/$GITHUB_REPOSITORY_NAME.git"

consoleCyan "Pushing changes to GitHub ..."
consoleGreen "$(echoBar)"
start=$(($(date +%s) + 0))
git push github
consoleGreen "$(echoBar)"
consoleGreen "OK. " && consoleBoldMagenta $(($(date +%s) - start)) seconds && echo

start=$(($(date +%s) + 0))
consoleCyan "Building Zesk PHP Dockerfile ..."
image=$(docker build -q -f ./docker/php.Dockerfile .)
consoleGreen "OK. " && consoleBoldMagenta $(($(date +%s) - start)) seconds && echo

consoleCyan "Generated container $image, running github tag ..." && echo
consoleGreen "$(echoBar)"
start=$(($(date +%s) + 0))
docker run -u www-data "$image" /zesk/bin/zesk --config /zesk/.github.conf module GitHub -- github --tag --description-file "/zesk/$remoteChangeLogName" --commitish "$commitish"
consoleGreen "$(echoBar)"
consoleGreen "OK. " && consoleBoldMagenta $(($(date +%s) - start)) seconds && echo

consoleCyan "Pull github and push origin ... " && echo
consoleGreen "$(echoBar)"
consoleCyan "git pull github" && echo
start=$(($(date +%s) + 0))
git pull github
consoleCyan "git push origin" && echo
git push origin
consoleGreen "$(echoBar)"
consoleBoldMagenta $(($(date +%s) - start)) seconds

consoleGreen "Tagging $currentVersion and pushing ... "
start=$(($(date +%s) + 0))
consoleGreen "$(echoBar)"
git tag "$currentVersion"
git push origin --tags
git push github --tags
consoleGreen "OK. " && consoleBoldMagenta $(($(date +%s) - start)) seconds && echo

figlet "zesk $currentVersion OK"
