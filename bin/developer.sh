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
    local home

    home=$(__catch "$handler" buildHome) || return $?

    local name
    name=$(__catch "$handler" buildEnvironmentGet APPLICATION_NAME) || return $?
    [ -n "$name" ] || name=$(basename "$home")
    bigText "$name"

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
    fi

    pathConfigure --last "$home/bin" "$home/vendor/bin"

    __catch "$handler" muzzle reloadChanges --stop --quiet || return $?
    __catch "$handler" reloadChanges --name "$name" "$home/bin/tools.sh" "$home/bin/tools/" || return $?

    developerAnnounce < <(developerTrack)

    export BUILD_PROJECT_DEACTIVATE

    BUILD_PROJECT_DEACTIVATE="__zeskPHPDeveloperContextUndo"
  }

  __zeskPHPDeveloperContextUndo() {
    local home

    home=$(__environment buildHome) || return $?
    pathRemove "$home/bin" "$home/vendor/bin"
    developerUndo < <(developerTrack)
  }

  __zeskPHPDeveloperContext

  unset __zeskPHPDeveloperContext
fi
