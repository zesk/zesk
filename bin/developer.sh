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
    local handler="_return"
    local home name

    home=$(__catch "$handler" buildHome) || return $?

    name=$(__catch "$handler" buildEnvironmentGet APPLICATION_NAME) || return $?
    [ -n "$name" ] || name=$(basename "$home")
    bigText "$name"

    # shellcheck disable=SC2139
    alias z="$home/bin/zesk.sh"
    alias IdenticalRepair="zeskPHPIdenticalRepair"

    if whichExists docker; then
      # shellcheck disable=SC2139
      alias zeskBash="__dockerPHPExec \"$home\" www-data bash"
      # shellcheck disable=SC2139
      alias suZeskBash="__dockerPHPExec \"$home\" root bash"
    fi

    pathConfigure --last "$home/bin" "$home/vendor/bin"

    __catch "$handler" muzzle reloadChanges --stop --quiet || return $?
    __catch "$handler" reloadChanges --name "$name" bin/tools.sh bin/tools/ || return $?

    developerAnnounce < <(__applicationToolsList)

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
