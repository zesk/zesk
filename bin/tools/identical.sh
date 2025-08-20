#!/usr/bin/env bash
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

# Run identical repair with settings specifically for Zesk for PHP
zeskPHPIdenticalRepair() {
  local handler="_${FUNCNAME[0]}"
  local home name start

  home=$(__catch "$handler" buildHome) || return $?
  start=$(__catch "$handler" timingStart) || return $?
  __catch "$handler" "$home/bin/build/identical-repair.sh" "$@" --exclude '*/bin/build/tools/*' --exclude '*/bin/build/hooks/*' --ignore-singles || return $?
  name=$(__catch "$handler" buildEnvironmentGet APPLICATION_NAME) || return $?
  __catch "$handler" hookRun notify "$(decorate bold-blue "$name") $(timingReport "$start" "Identical repair completed in")" || return $?
}
_zeskPHPIdenticalRepair() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
