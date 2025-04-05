#!/usr/bin/env bash
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

zeskPHPIdenticalRepair() {
  local home name start

  home=$(__environment buildHome) || return $?
  start=$(__environment timingStart) || return $?
  __environment "$home/bin/build/identical-repair.sh" "$@" --exclude '*/bin/build/tools/*' --exclude '*/bin/build/hooks/*' --ignore-singles || return $?
  name=$(buildEnvironmentGet APPLICATION_NAME)
  hookRun notify "$(decorate bold-blue "$name") $(timingReport "$start" "Identical repair completed in")"
}
