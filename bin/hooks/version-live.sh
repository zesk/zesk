#!/usr/bin/env bash
#
# Sample live version script, uses github API to fetch release version
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

# IDENTICAL githubVersionLive 23

# shellcheck source=/dev/null
if ! source "${BASH_SOURCE[0]%/*}/../tools.sh"; then
  printf "%s\n" "Unable to load ${BASH_SOURCE[0]%/*}/../tools.sh" 1>&2
else
  # Fetch the current live version of the software
  __hookVersionLive() {
    local handler="_${FUNCNAME[0]}"

    local name owner
    name=$(__catch "$handler" buildEnvironmentGet GITHUB_REPOSITORY_NAME) || return $?
    [ -n "$name" ] || __throwEnvironment "$handler" "GITHUB_REPOSITORY_NAME is blank" || return $?

    owner=$(__catch "$handler" buildEnvironmentGet GITHUB_REPOSITORY_OWNER) || return $?
    [ -n "$owner" ] || __throwEnvironment "$handler" "GITHUB_REPOSITORY_OWNER is blank" || return $?
    __catchEnvironment "$handler" githubLatestRelease "$owner/$name" "$@" || return $?
  }
  ___hookVersionLive() {
    # __IDENTICAL__ usageDocument 1
    usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
  }
  __hookVersionLive "$@"
fi
