#!/usr/bin/env bash
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

# Run identical repair with settings specifically for Zesk for PHP
zeskIdenticalRepair() {
  local handler="_${FUNCNAME[0]}"
  local home name start

  home=$(catchReturn "$handler" buildHome) || return $?
  start=$(catchReturn "$handler" timingStart) || return $?

  # PHP
  local phpRepairDirectories=() eof=false
  while ! $eof; do
    read -r directory || eof=true
    [ ! -d "$directory" ] || phpRepairDirectories+=(--repair "$directory")
  done < <(find "$home" -path "*/identical/*" -name '*.php' -exec dirname {} \; | sort -u)

  local ee=()

  ee+=(--exclude "*/vendor/*")
  ee+=(--exclude "*/bin/build/*")
  ee+=(--exclude "*/etc/docker/*")
  ee+=(--exclude "*/.*/*")
  statusMessage decorate info "Running PHP identical updates ..."
  catchReturn "$handler" identicalCheck --cd "$home" --extension php --prefix '// IDENTICAL' "${phpRepairDirectories[@]+"${phpRepairDirectories[@]}"}" "${ee[@]}" "$@" || return $?
  name=$(catchReturn "$handler" buildEnvironmentGet APPLICATION_NAME) || return $?

  statusMessage decorate info "Running Bash identical updates ..."
  # Shell
  catchReturn "$handler" identicalCheck --cd "$home" --extension sh --prefix '# __IDENTICAL__' --prefix '# _IDENTICAL_' --prefix '# IDENTICAL' --repair "$home/bin/build/identical" --repair "$home/etc/docker/identical" --exclude '*/bin/build/tools/*' --exclude '*/bin/build/hooks/*' --exclude '*/bin/build/*.sh' --exclude '*/install-bin-build.sh' --ignore-singles "$@" || return $?

  catchReturn "$handler" hookRun notify "$(decorate bold-blue "$name") $(timingReport "$start" "Identical repair completed in")" || return $?
}
_zeskIdenticalRepair() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
