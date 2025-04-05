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

export DEVELOPER_TRACK=true

# shellcheck source=/dev/null
if source "${BASH_SOURCE[0]%/*}/tools.sh"; then

  __zeskPHPDeveloper() {
    local home

    home=$(__environment buildHome) || return $?

    # shellcheck disable=SC2139
    alias z="$home/bin/zesk.sh"
    alias IdenticalRepair="zeskPHPIdenticalRepair"

    if whichExists docker; then
      # shellcheck disable=SC2139
      alias zeskBash="__dockerPHPExec \"$home\" www-data bash"
      # shellcheck disable=SC2139
      alias suZeskBash='__dockerPHPExec \"$home\" root bash'
    fi

    developerAnnounce < <(__applicationToolsList)
  }

  __zeskPHPDeveloperUndo() {
    unalias z 2>/dev/null
    unalias IdenticalRepair 2>/dev/null
    unalias zeskBash 2>/dev/null
    unalias suZeskBash 2>/dev/null
  }

  __zeskPHPDeveloper

  unset __zeskPHPDeveloper
fi
