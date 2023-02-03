#!/usr/bin/env bash
#
# release-prepare.sh
#
# Prepare the repository for the next release
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
ERR_ENV=1
ERR_ARG=2

#
# Assumptions
#
me=$(basename "$0")
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." || exit $ERR_ENV; pwd)"
yml="$top/docker-compose.yml"

set -eo pipefail

usage() {
  local exitCode
  exitCode=$((${1:-0} + 0))
  shift
  exec 1>&2
  if [ -n "$*" ]; then
    echo "ERROR: $*"
    echo
  fi
  echo "$me: Prepare repository for next release"
  echo
  echo "--force    Force generation of release markdown"
  exit "$exitCode"
}

for e in (REPOSITORY_VERSION_PREFIX); do
  if [ -z "${!e}" ]; then
    usage $ERR_ENV "Need $e defined in environment"
  fi
done

forceWrite=
while [ $# -gt 0 ]; do
  case $1 in
    --force)
      forceWrite=1
      ;;
    *)
      usage $ERR_ARG "Unknown argument $1"
      ;;
  esac
  shift
done

"$top/bin/build/release-check-version.sh"

pause() {
  echo -n "$@" " "
  read -r
}

currentVersion=$("$top/bin/build/version-current.sh")
previousVersion=$("$top/bin/build/version-last.sh")

#
# Sync with remote
#
echo "Synchronizing with remote ..."
git pull --tags > /dev/null 2>&1
git push --tags > /dev/null 2>&1

if ! docker compose -f "$yml" exec php bash -c 'cd /zesk && ./bin/cs-zesk.sh' > /dev/null < /dev/null; then
	echo "Clean failed" 1>& 2
	exit $ERR_ENV
fi

#
# Make sure repository state seems sane
#
nLines=$(($(git status --short | wc -l | awk '{ print $1 }') + 0))
if [ $nlines -gt 0 ]; then
	git status --short
	pause "Current git status, ok?"
fi

releaseDir=$top/docs/release/

if [ ! -d "$releaseDir" ]; then
  mkdir -p "$releaseDir"
fi
currentChangeLog=$releaseDir/$currentVersion.md
permanentChangeLog=$top/CHANGELOG.md
if [ ! -f "$currentChangeLog" ] || test "$forceWrite"; then
  #
  # Edit release notes
  #
  {
    echo '## Release {version}'
    echo
    git log --pretty=format:'- %s' "^$previousVersion^{}" HEAD | sort -u
    echo
    echo '<!-- Generated automatically by release-prepare.sh, beware editing! -->'
   } >> "$currentChangeLog"
   git add "$currentChangeLog" > /dev/null
fi

moreLines=100000
editedLog=$top/CHANGELOG.md.$$.edited

# CHANGELOG.md
#
# File structure with tokens in order:
# RELEASE-HERE
# currentVersion <- replaced up to next token
# previousVersion
# LINK-HERE
# ...currentVersion  <- line replaced
# rest of file

{
  grep -B $moreLines RELEASE-HERE "$permanentChangeLog"
  cat "$currentChangeLog"
  grep -B $moreLines LINK-HERE "$permanentChangeLog" | grep -A $moreLines "$previousVersion"
  echo "[$currentVersion]: $REPOSITORY_VERSION_PREFIX/$currentVersion...$previousVersion"
  grep -A $moreLines LINK-HERE "$permanentChangeLog" | grep -v "$currentVersion"
} > "$editedLog"

releaseDateFile=$top/etc/db/release-date
date > "$releaseDateFile"
git commit -m "Release $currentVersion" "$currentChangeLog" "$permanentChangeLog" "$releaseDateFile"

#
# Push to remote
#
git push --tags --all
#
# Good
#
figlet zesk $currentVersion ready
