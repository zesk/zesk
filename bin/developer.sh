#!/usr/bin/env bash
#
# developer.sh
#
# Copyright &copy; 2025 Market Acumen, Inc.
#
# Zesk PHP Developer scripts

# source this file to get additional tools within your Bash environment
__zeskPHPAnnounce() {
  decorate info "Added aliases $(decorate each code z zeskBash suZeskBash)"
  decorate info "Available functions $(decorate each code zeskPHPIdenticalRepair)"
}

zeskPHPIdenticalRepair() {
  local home name start

  home=$(__environment buildHome) || return $?
  start=$(__environment beginTiming) || return $?
  __environment "$home/bin/build/identical-repair.sh" "$@" --exclude '*/bin/build/tools/*' --exclude '*/bin/build/hooks/*' --ignore-singles || return $?
  name=$(buildEnvironmentGet APPLICATION_NAME)
  hookRun notify "$(decorate bold-blue "$name") $(reportTiming "$start" "Identical repair completed in")"
}

__zeskPHPAliases() {
  local home

  home=$(__environment buildHome) || return $?

  # shellcheck disable=SC2139
  alias z="$home/bin/zesk.sh"
  alias IdenticalRepair="zeskPHPIdenticalRepair"

  # shellcheck disable=SC2139
  alias zeskBash="__dockerPHPExec \"$home\" www-data bash"
  # shellcheck disable=SC2139
  alias suZeskBash='__dockerPHPExec \"$home\" root bash'
}
__zeskPHPAliasesUndo() {
  unalias z 2>/dev/null
  unalias IdenticalRepair 2>/dev/null
  unalias zeskBash 2>/dev/null
  unalias suZeskBash 2>/dev/null
}

__zeskPHPAliases
__zeskPHPAnnounce

unset __zeskPHPAliases __zeskPHPAnnounce

__dockerPHPExec() {
  local home="$1" user="$2" ttyArg=() && shift 2
  [ -t 0 ] || ttyArg+=(-T)
  docker compose -f "$home/docker-compose.yml" exec "${ttyArg[@]}" -u "${user}" php "$@"
}
