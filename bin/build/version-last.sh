#!/usr/bin/env bash
#
# version-last.sh
#
# Depends: git
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
err_env=1
me=$(basename "${BASH_SOURCE[0]}")
relTop="../.."
if ! cd "$(dirname "${BASH_SOURCE[0]}")/$relTop"; then
  echo "$me: Can not cd to $relTop" 1>&2
  exit $err_env
fi
top="$(pwd)"
. "./bin/build/colors.sh"

if [ ! -d ".git" ]; then
	echo "No .git directory at $top, stopping" 1>&2
	exit $err_env
fi

# Ignored any development versions: grep -e '^v[0-9.]*$' matches v1.2.3.4 but not v.1.2.3d4
# versionSort works on vMMM.NNN.PPP
# Last one is last version: tail -1
git tag | grep -e '^v[0-9.]*$' | versionSort | tail -1
