#!/usr/bin/env bash
#
# release-check-version.sh
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
ERR_ENV=1

top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." || exit $ERR_ENV; pwd)"

"$top/bin/build/git.sh"
"$top/bin/build/docker-compose.sh"

id "$top" || exit "$ERR_ENV"

previousVersion=$("$top/bin/build/version-last.sh")
currentVersion=$("$top/bin/build/version-current.sh")

currentVersion="v$currentVersion"
exists=$(git tag | grep "$currentVersion")
if [ -n "$exists" ]; then
	echo "Version $exists already exists, already tagged."
	exit 16
fi
if [ "$previousVersion" = "$currentVersion" ]; then
	echo "Version $currentVersion up to date, nothing to do."
	exit 17
fi
echo "Zesk previous version is: $previousVersion"
echo " Zesk release version is: $currentVersion"
