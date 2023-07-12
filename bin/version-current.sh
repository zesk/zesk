#!/usr/bin/env bash
#
# version-current.sh
#
# Depends: docker
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
err_env=1
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." || exit $err_env; pwd)"

cd "$top" || exit $err_env

set -eo pipefail

echo -n "v$(docker run -v "$top/:/zesk" -w /zesk php:latest /zesk/bin/zesk version)"
