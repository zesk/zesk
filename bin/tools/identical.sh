#!/usr/bin/env bash
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

# Run identical repair with settings specifically for Zesk for PHP
zeskPHPIdenticalRepair() {
  local handler="_${FUNCNAME[0]}"
  local home name start

  home=$(catchReturn "$handler" buildHome) || return $?
  start=$(catchReturn "$handler" timingStart) || return $?
  catchReturn "$handler" "$home/bin/build/identical-repair.sh" "$@" --exclude '*/bin/build/tools/*' --exclude '*/bin/build/hooks/*' --ignore-singles || return $?
  name=$(catchReturn "$handler" buildEnvironmentGet APPLICATION_NAME) || return $?
  catchReturn "$handler" hookRun notify "$(decorate bold-blue "$name") $(timingReport "$start" "Identical repair completed in")" || return $?
}
_zeskPHPIdenticalRepair() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
