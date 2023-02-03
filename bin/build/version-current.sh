#!/usr/bin/env bash
#
# version-last.sh
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
ERR_ENV=1

top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $ERR_ENV; pwd)"
yml="$top/docker-compose.yml"

if [ ! -f "$yml" ]; then
	echo "No .git directory at $top, stopping" 1>&2
	exit $ERR_ENV
fi

docker-compose -f "$yml" exec php /zesk/bin/zesk version
