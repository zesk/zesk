#!/usr/bin/env bash
#
# version-last.sh
#
# Depends: git
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
err_env=1

relTop="../.."
if ! cd "$(dirname "${BASH_SOURCE[0]}")/$relTop"; then
  echo "$me: Can not cd to $relTop" 1>&2
  exit $err_env
fi
top="$(pwd)"
. "$top/bin/build/colors.sh"

if [ ! -d "$top/.git" ]; then
	echo "No .git directory at $top, stopping" 1>&2
	exit $err_env
fi

cd "$top" || exit $err_env
# versionSort works on vMMM.NNN.PPP
git tag | grep -e '^v[0-9.]*$' | versionSort | tail -1
