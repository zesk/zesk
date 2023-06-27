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

if [ -n "$GITHUB_ACCESS_TOKEN" ]; then
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
  for f in $(find . -type f -name '*.md' | cut -d / -f 2 | grep -e '^v' | versionSort -r); do
    cat "$f"
    rawVersion="$f"
    rawVersion=${rawVersion%%.md}
    if [ -n "$prevVersion" ]; then
      echo "[$rawVersion]: https://github.com/$GITHUB_REPOSITORY_OWNER/$GITHUB_REPOSITORY_NAME/compare/$prevVersion...$rawVersion" >> "$linksSuffix"
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
