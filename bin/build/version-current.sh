#!/usr/bin/env bash
#
# version-last.sh
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
ERR_ENV=1

top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $ERR_ENV; pwd)"

cd "$top" || exit $ERR_ENV

echo -n "v$(docker run php:latest /zesk/bin/zesk version)"
