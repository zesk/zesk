#!/usr/bin/env bash
#
# version-last.sh
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
err_env=1

top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $err_env; pwd)"

cd "$top" || exit $err_env

echo -n "v$(docker run -v "$top/:/zesk" -w /zesk php:latest /zesk/bin/zesk version)"
