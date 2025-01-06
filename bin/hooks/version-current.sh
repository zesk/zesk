#!/usr/bin/env bash
#
# version-current.sh
#
# Depends: docker
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

# shellcheck source=/dev/null
source "${BASH_SOURCE[0]%/*}/../../bin/build/tools.sh" || exit 99

zeskVersionCurrent() {
  local usage="_${FUNCNAME[0]}"
  local home
  home=$(__usageEnvironment "$usage" buildHome) || return $?
  version=$(__usageEnvironment "$usage" docker run -q -v "$home:/zesk" -w /zesk php:8 /zesk/bin/zesk version) || return $?
  printf "v%s\n" "$version"
}
_zeskVersionCurrent() {
  # IDENTICAL usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

zeskVersionCurrent "$@"
