#!/usr/bin/env bash
#
# version-last.sh
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
ERR_ENV=1

top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $ERR_ENV; pwd)"

if [ ! -d "$top/.git" ]; then
	echo "No .git directory at $top, stopping" 1>&2
	exit $ERR_ENV
fi

cd "$top" || exit "$ERR_ENV"
git tag | sort -t. -k 1.2,1n -k 2,2n -k 3,3n -k 4,4n | tail -1
