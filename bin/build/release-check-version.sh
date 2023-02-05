#!/usr/bin/env bash
#
# release-check-version.sh
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
ERR_ENV=1

top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $ERR_ENV; pwd)"

"$top/bin/build/git.sh"

previousVersion=$("$top/bin/build/version-last.sh")
currentVersion=$("$top/bin/build/version-current.sh")
artifactReleaseNotes="$top/.release-notes.md"

exists=$(git tag | grep "$currentVersion")
if [ -n "$exists" ]; then
	echo "Version $exists already exists, already tagged." 1>&2
	exit 16
fi
if [ "$previousVersion" = "$currentVersion" ]; then
	echo "Version $currentVersion up to date, nothing to do." 1>&2
	exit 17
fi
echo "Zesk previous version is: $previousVersion"
echo " Zesk release version is: $currentVersion"
echo

releaseNotes=$top/docs/release/$currentVersion.md

if [ ! -f "$releaseNotes" ]; then
	echo "Version $currentVersion up to date, nothing to do." 1>&2
  exit 18
fi
cp "$releaseNotes" "$artifactReleaseNotes"
