#!/usr/bin/env bash
# Copyright &copy; 2025 Market Acumen, Inc.

# IDENTICAL __zeskTools EOF
__zeskTools() {
  # shellcheck source=/dev/null
  if ! source "${BASH_SOURCE[0]%/*}/tools.sh"; then
    printf -- "tools.sh failed\n" 1>&2
    return 99
  fi
}
