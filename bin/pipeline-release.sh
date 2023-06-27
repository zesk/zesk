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
me=$(basename "${BASH_SOURCE[0]}")

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
start=$(beginTiming)
consoleInfo -n "Adding remote ..."
ssh-keyscan github.com >> "$HOME/.ssh/known_hosts" 2> /dev/null
if git remote | grep -q github; then
  echo -n "$(consoleInfo Remote) $(consoleMagenta github) $(consoleInfo exists, not adding again.) "
else
  git remote add github "git@github.com:$GITHUB_REPOSITORY_OWNER/$GITHUB_REPOSITORY_NAME.git"
fi
reportTiming "$start" OK

#
#========================================================================
#
consoleInfo -n "Generating release notes and CHANGELOG ..."
start=$(beginTiming)
remoteChangeLogName=".release-notes.md"
remoteChangeLog="$top/$remoteChangeLogName"
{
  figlet "zesk $currentVersion" | awk '{ print "    " $0 }'
  echo
  echo "> Released on $(date)"
  echo
  cat "$currentChangeLog"
} > "$remoteChangeLog"

releaseNotesGenerate() {
  local f rawVersion prevVersion linksSuffix

  linksSuffix=$(mktemp)
  cd "$top/docs/release"
  cat header.md
  prevVersion=
  for f in $(find . -type f -name '*.md' | cut -d / -f 2 | grep -e '^v' | versionSort); do
    cat "$f"
    rawVersion="$f"
    rawVersion=${rawVersion%%.md}
    if [ -n "$prevVersion" ]; then
      echo "[$rawVersion]: https://github.com/zesk/zesk/compare/$prevVersion...$rawVersion" >> "$linksSuffix"
    fi
    prevVersion="$rawVersion"
  done
  cat footer.md
  cat "$linksSuffix"
  rm "$linksSuffix"
  cd "$top"
}

changeLog=$top/CHANGELOG.md
releaseNotesGenerate > "$changeLog"

reportTiming "$start" OK
echo
figlet "zesk $currentVersion"
echo

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
consoleGreen "Tagging $currentVersion and pushing ... "
consoleDecoration "$(echoBar)"
start=$(beginTiming)
git commit -m "$me automatic update of CHANGELOG.md" "$changeLog" || :
git tag -d "$currentVersion" 2> /dev/null || :
git push origin ":$currentVersion" 2> /dev/null || :
git push github ":$currentVersion" 2> /dev/null || :
git tag "$currentVersion"
git push origin --all
git push github --all
consoleDecoration "$(echoBar)"
reportTiming "$start" OK
#
#========================================================================
#
commitish=$(git rev-parse --short HEAD)
echo "$(consoleInfo "Generated container $image, running github tag")" "$(consoleRedBold "$commitish")" "$(consoleInfo "===")" "$(consoleRedBold "$currentVersion")" "$(consoleInfo "...")"
consoleDecoration "$(echoBar)"
start=$(beginTiming)
{
  echo 'zesk\GitHub\Module::accessToken="'"$GITHUB_ACCESS_TOKEN"'"'
  echo 'zesk\GitHub\Module::owner='"$GITHUB_REPOSITORY_OWNER"
  echo 'zesk\GitHub\Module::repository='"$GITHUB_REPOSITORY_NAME"
} > "$top/.github.conf"
docker run -u www-data "$image" /zesk/bin/zesk --config /zesk/.github.conf module GitHub -- github --tag --description-file "/zesk/$remoteChangeLogName"
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
figlet "zesk $currentVersion OK"
reportTiming "$releaseStart" "Release complete."
