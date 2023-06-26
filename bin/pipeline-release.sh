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

releaseStart=$(beginTiming)

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

#
#========================================================================
#
consoleInfo "Generating release notes ..."
start=$(beginTiming)
remoteChangeLogName=".release-notes.md"
remoteChangeLog="$top/$remoteChangeLogName"
{
  figlet "zesk $currentVersion" | awk '{ print "    " $0 }'
  echo
  cat "$currentChangeLog"
} >> "$remoteChangeLog"
reportTiming "$start" OK
echo
consoleMagenta
figlet "zesk $currentVersion"
consoleReset
echo

#
#========================================================================
#
start=$(beginTiming)
consoleInfo -n "Tagging release in GitHub ..."
{
  echo 'zesk\\GitHub\\Module::access_token="'"$GITHUB_ACCESS_TOKEN"'"'
  echo 'zesk\\GitHub\\Module::owner='"$GITHUB_REPOSITORY_OWNER"
  echo 'zesk\\GitHub\\Module::repository='"$GITHUB_REPOSITORY_NAME"
} > "$top/.github.conf"

commitish=$(git rev-parse --short HEAD)
ssh-keyscan github.com >> "$HOME/.ssh/known_hosts" 2> /dev/null
git remote add github "git@github.com:$GITHUB_REPOSITORY_OWNER/$GITHUB_REPOSITORY_NAME.git"
reportTiming "$start" OK

#
#========================================================================
#
consoleInfo "Pushing changes to GitHub ..."
consoleDecoration "$(echoBar)"
start=$(beginTiming)
git push github
consoleDecoration "$(echoBar)"
reportTiming "$start" OK
#
#========================================================================
#
start=$(beginTiming)
consoleInfo -n "Building Zesk PHP Dockerfile ..."
image=$(docker build -q -f ./docker/php.Dockerfile .)
reportTiming "$start" OK
#
#========================================================================
#
consoleInfo "Generated container $image, running github tag ..." && echo
consoleDecoration "$(echoBar)"
start=$(beginTiming)
docker run -u www-data "$image" /zesk/bin/zesk --config /zesk/.github.conf module GitHub -- github --tag --description-file "/zesk/$remoteChangeLogName" --commitish "$commitish"
consoleDecoration "$(echoBar)"
reportTiming "$start" OK
#
#========================================================================
#
consoleInfo "Pull github and push origin ... " && echo
consoleDecoration "$(echoBar)"
consoleInfo "git pull github" && echo
start=$(beginTiming)
git pull github
consoleInfo "git push origin" && echo
git push origin
consoleDecoration "$(echoBar)"
reportTiming "$start"
#
#========================================================================
#
consoleGreen "Tagging $currentVersion and pushing ... "
consoleDecoration "$(echoBar)"
start=$(beginTiming)
git tag "$currentVersion"
git push origin --tags
git push github --tags
consoleDecoration "$(echoBar)"
reportTiming "$start" OK
#
#========================================================================
#
figlet "zesk $currentVersion OK"
reportTiming "$releaseStart" "Release complete."
