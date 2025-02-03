#!/usr/bin/env bash
#
# developer.sh
#
# Copyright &copy; 2025 Market Acumen, Inc.
#
# Zesk PHP Developer scripts

__zeskPHPAnnounce() {
  decorate info "Added aliases $(decorate each code z)"
  decorate info "Available functions $(decorate each code zeskPHPIdenticalRepair)"
}

zeskPHPIdenticalRepair() {
  local home

  home=$(__environment buildHome) || return $?

  __environment "$home/bin/build/identical-repair.sh" --exclude '*/bin/build/tools/*' --exclude '*/bin/build/hooks/*'
}

__zeskPHPAliases() {
  local home

  home=$(__environment buildHome) || return $?

  # shellcheck disable=SC2139
  alias z="$home/bin/zesk.sh"
  # shellcheck disable=SC2139
  alias IdenticalRepair="zeskPHPIdenticalRepair"
}
__zeskPHPAliasesUndo() {
  unalias z 2>/dev/null
  unalias IdenticalRepair 2>/dev/null
}

__zeskPHPAliases
__zeskPHPAnnounce

unset __zeskPHPAliases __zeskPHPAnnounce
