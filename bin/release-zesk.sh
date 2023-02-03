#!/usr/bin/env bash
#
# release-zesk.sh
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
	echo "No .git directory at $ZESK_ROOT, stopping" 1>&2
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
$ZESK github --tag --description-file "$currentChangeLog"

echo
figlet "zesk $currentVersion OK"
