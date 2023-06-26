#!/usr/bin/env bash
#
# version-last.sh
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
err_env=1

top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $err_env; pwd)"

if [ ! -d "$top/.git" ]; then
	echo "No .git directory at $top, stopping" 1>&2
	exit $err_env
fi

cd "$top" || exit $err_env
git tag | sort -t. -k 1.2,1n -k 2,2n -k 3,3n -k 4,4n | tail -1
