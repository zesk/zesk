#!/usr/bin/env bash
#
# pre-commit.sh
#
# Copyright &copy; 2025 Market Acumen, Inc.

# shellcheck source=/dev/null
source "${BASH_SOURCE[0]%/*}/../../bin/build/tools.sh" || exit 99

__preCommit() {
  local handler="_${FUNCNAME[0]}"

  local version

  version=$(catchReturn "$handler" hookVersionCurrent) || return $?
  printf "%s %s\n" "$(decorate success "$(buildEnvironmentGet APPLICATION_NAME)")" "$(decorate code "$version")"

  catchReturn "$handler" hookRunOptional --next "${BASH_SOURCE[0]}" pre-commit "$@" || return $?
}
___preCommit() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

__preCommit "$@"
