#!/usr/bin/env bash
#
# _compatibility.sh
#
# Set up Zesk Build with any needed fixes
#
# Copyright &copy; 2025 Market Acumen, Inc.

# Open a URL with a new PR
# Environment: BITBUCKET_WORKSPACE - Workspace to use
# Environment: BITBUCKET_REPO_SLUG - project name to use. If not specified uses project directory name.
bitbucketPRNew() {
  local handler="_${FUNCNAME[0]}"

  local name="BITBUCKET_WORKSPACE" value
  local value name="BITBUCKET_WORKSPACE"
  value=$(catchEnvironment "$handler" buildEnvironmentGet "$name") || return $?
  [ -n "$value" ] || throwEnvironment "$handler" "$name is blank" || return $?
  workspace="$value"
  name="BITBUCKET_REPO_SLUG"
  value=$(buildEnvironmentGet "$name" 2>/dev/null || :)
  if [ -z "$value" ]; then
    value="$(catchReturn "$handler" buildHome)" || return $?
    value="$(catchEnvironment "$handler" basename "$value")" || return $?
    [ -n "$value" ] || throwEnvironment "$handler" "$name is blank? $(buildHome)" || return $?
  fi
  open "$(catchEnvironment "$handler" bitbucketPRNewURL "$workspace" "$value")" || return $?
}
_bitbucketPRNew() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
