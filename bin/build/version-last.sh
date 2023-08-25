#!/usr/bin/env bash
#
# version-last.sh
#
# Depends: git
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
listBin="$(dirname "${BASH_SOURCE[0]}")/version-list.sh"
if [ -n "$1" ]; then
  $listBin | grep -v "$1" | tail -1
else
  $listBin | tail -1
fi
