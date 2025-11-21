#!/usr/bin/env bash
#
# developer.sh
#
# Copyright &copy; 2025 Market Acumen, Inc.
#
# Zesk PHP Developer scripts

#
# source this file to get additional tools within your Bash environment
#

# shellcheck source=/dev/null
if source "${BASH_SOURCE[0]%/*}/tools.sh"; then

  # Zesk PHP shell
  # - `zeskCompose build` - build containers for testing etc.
  # - `zeskPrepareRelease`
  # - `zeskPHPIdenticalRepair`
  # - `zeskPR` - Open URL to new PR
  __zeskPHPDeveloperContext() {
    local handler="_return"
    local home

    home=$(catchReturn "$handler" buildHome) || return $?

    local name
    name=$(catchReturn "$handler" buildEnvironmentGet APPLICATION_NAME) || return $?
    [ -n "$name" ] || name=$(basename "$home")
    iTerm2Image -i --height 4 "$home/etc/zesk-logo.png" || :
    bigText "$name" || :

    # shellcheck disable=SC2139
    alias z="$home/bin/zesk.sh"
    alias IdenticalRepair="zeskPHPIdenticalRepair"

    if whichExists docker; then
      # shellcheck disable=SC2139
      alias zeskBash="__dockerPHPExec \"$home\" web www-data bash"
      # shellcheck disable=SC2139
      alias suZeskBash="__dockerPHPExec \"$home\" web root bash"
      # shellcheck disable=SC2139
      alias suZeskDB="__dockerPHPExec \"$home\" db root bash "
      # shellcheck disable=SC2139
      alias zeskDatabase="__dockerPHPExec \"$home\" db root /db-connect.sh"
    fi

    pathConfigure --last "$home/bin" "$home/vendor/bin"

    catchReturn "$handler" muzzle reloadChanges --stop --quiet || return $?
    catchReturn "$handler" muzzle reloadChanges --name "$name" "$home/bin/tools.sh" "$home/bin/tools/" || return $?

    export BUILD_PROJECT_DEACTIVATE="${FUNCNAME[0]}Undo"

    simpleMarkdownToConsole < <(bashFunctionComment "${BASH_SOURCE[0]}" "${FUNCNAME[0]}")

    unset "${FUNCNAME[0]}"
  }

  __zeskPHPDeveloperContextUndo() {
    local home

    home=$(catchEnvironment "returnMessage" buildHome) || return $?
    pathRemove "$home/bin" "$home/vendor/bin"

    unset "${FUNCNAME[0]}"
  }

  __zeskPHPDeveloperContext

  unset __zeskPHPDeveloperContext
fi
