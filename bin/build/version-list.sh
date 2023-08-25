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
me=$(basename "${BASH_SOURCE[0]}")
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

# versionSort works on vMMM.NNN.PPP
# skip any versions with extensions like v1.0.1d2
git tag | grep -e '^v[0-9.]*$' | versionSort
