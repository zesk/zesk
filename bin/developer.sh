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

  __zeskPHPDeveloperContext() {
    local home

    home=$(__environment buildHome) || return $?

    # shellcheck disable=SC2139
    alias z="$home/bin/zesk.sh"
    alias IdenticalRepair="zeskPHPIdenticalRepair"

    if whichExists docker; then
      # shellcheck disable=SC2139
      alias zeskBash="__dockerPHPExec \"$home\" www-data bash"
      # shellcheck disable=SC2139
      alias suZeskBash="__dockerPHPExec \"$home\" root bash"
    fi

    reloadChanges --stop --quiet
    reloadChanges --name "Zesk PHP Tools" bin/tools.sh bin/tools/
    developerAnnounce < <(__applicationToolsList)

    pathConfigure --last "$home/bin" "$home/vendor/bin"
  }

  __zeskPHPDeveloperContextUndo() {
    local home

    home=$(__environment buildHome) || return $?
    pathRemove "$home/bin" "$home/vendor/bin"
    developerUndo < <(__applicationToolsList)
  }

  __zeskPHPDeveloperContext

  unset __zeskPHPDeveloperContext
fi
