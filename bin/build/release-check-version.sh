#!/usr/bin/env bash
#
# release-check-version.sh
#
# Depends: apt git docker
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
err_env=1
err_arg=2

top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $err_env; pwd)"

"$top/bin/build/git.sh"
source "$top/bin/build/colors.sh"

previousVersion=$("$top/bin/build/version-last.sh")
currentVersion=$("$top/bin/build/version-current.sh")
artifactReleaseNotes="$top/.release-notes.md"
me=$(basename "${BASH_SOURCE[0]}")

usage() {
  local rs

  rs=$1
  shift
  exec 1>&2
  if [ -z "$*" ]; then
      consoleRed "$@" && echo
      echo
  fi
  echo "$me: Check version and optionally tag development version"
  echo
  echo "--develop    Tag a development version as {current}d{nextIndex}"
  echo
  exit "$rs"
}

develop=
while [ $# -gt 0 ]; do
  case $1 in
  --develop)
    develop=1
    shift
    ;;
  *)
    usage $err_arg "Unknown argument: $1"
    break
    ;;
  esac
done

if git show-ref --tags "$currentVersion" --quiet; then
	echo "Version $currentVersion already exists, already tagged." 1>&2
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
	consoleError "Version $currentVersion no release notes \"$releaseNotes\" found, stopping." 1>&2
	echo 1>&2
  exit 18
fi
cp "$releaseNotes" "$artifactReleaseNotes"

maximumTagsPerVersion=1000
if test $develop; then
  label=development
  versionSuffix=d
else
  label=release
  versionSuffix=rc
fi
tagPrefix="${currentVersion}${versionSuffix}"
index=0
while true; do
  tryVersion="$tagPrefix$index"
  if ! git show-ref --tags "$tryVersion" --quiet; then
    break;
  fi
  index=$((index + 1))
  if [ $index -gt $maximumTagsPerVersion ]; then
    consoleError "Tag $label version exceeded maximum of $maximumTagsPerVersion" 1>&2
    exit 19
  fi
done

consoleGreen "Tagging $label version $tryVersion and pushing ... "
git tag "$tryVersion"
git push --tags
consoleGreen OK && echo
