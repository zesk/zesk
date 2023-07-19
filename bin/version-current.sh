#!/usr/bin/env bash
#
# version-current.sh
#
# Depends: docker
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
err_env=1

me=$(basename "$0")
relTop=".."
if ! cd "$(dirname "${BASH_SOURCE[0]}")/$relTop"; then
  echo "$me: Can not cd to $relTop" 1>&2
  exit $err_env
fi
top="$(pwd)"

set -eo pipefail

echo -n "v$(docker run -v "$top:/zesk" -w /zesk php:8 /zesk/bin/zesk version)"
