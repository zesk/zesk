#!/usr/bin/env bash
#
# pre-commit.sh
#
# Copyright &copy; 2025 Market Acumen, Inc.

# shellcheck source=/dev/null
source "${BASH_SOURCE[0]%/*}/../../bin/build/tools.sh" || exit 99

# Make shell files executable
# Make all other files read by user and group
# Argument: --read-only - Flag. Optional. Make files read-only by owner (default is writeable by owner.)
__preCommit() {
  local usage="_${FUNCNAME[0]}"

  local readOnly=false

  # _IDENTICAL_ argument-case-header 5
  local __saved=("$@") __count=$#
  while [ $# -gt 0 ]; do
    local argument="$1" __index=$((__count - $# + 1))
    [ -n "$argument" ] || __throwArgument "$usage" "blank #$__index/$__count ($(decorate each quote "${__saved[@]}"))" || return $?
    case "$argument" in
      # _IDENTICAL_ --help 4
      --help)
        "$usage" 0
        return $?
        ;;
      --read-only)
        readOnly=true
        ;;
      *)
        # _IDENTICAL_ argumentUnknown 1
        __throwArgument "$usage" "unknown #$__index/$__count \"$argument\" ($(decorate each code "${__saved[@]}"))" || return $?
        ;;
    esac
    # _IDENTICAL_ argument-esac-shift 1
    shift
  done

  local home version mode

  home=$(__catchEnvironment "$usage" buildHome) || return $?

  version=$(hookVersionCurrent) || return $?
  statusMessage --last bigText "$(printf "%s %s\n" "$(buildEnvironmentGet APPLICATION_NAME)" "$version")" | decorate bold-blue

  mode=$(_choose "$readOnly" 0440 0640)
  __catchEnvironment "$usage" find "$home" -type f -name '*.php' ! -path '*/bin/*' ! -path '*/.*/*' ! -path '*/vendor/*' -exec chmod "$mode" {} \; || return $?
  mode=$(_choose "$readOnly" 0770 0550)
  __catchEnvironment "$usage" find "$home" -type f -name '*.php' -path '*/bin/*' ! -path '*/.*/*' ! -path '*/vendor/*' -exec chmod "$mode" {} \; || return $?
  local extensions=(conf classes css csv data gif html inc ini json less md png py router rst sql svg tpl ttf txt xls xml yml)

  local extension aa=() ee=()
  for extension in "${extensions[@]}"; do
    if ! hasHook "pre-commit-$extension"; then
      aa+=(-or -name "*.$extension")
      ee+=("$extension")
    else
      statusMessage decorate info "Skipping handled extension $(decorate code "$extension")"
    fi
  done
  unset "a[0]"
  aa=("${aa[@]}")
  mode=$(_choose "$readOnly" 0440 0640)
  __catchEnvironment "$usage" find "$home" -type f -name "${aa[@]}" ! -path '*/.*/*' -exec chmod -v "$mode" {} \; || return $?
  statusMessage decorate info "Converted mode for $(decorate code "${ee[@]}")"
}
___preCommit() {
  # _IDENTICAL_ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

__preCommit "$@"
