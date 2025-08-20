#!/usr/bin/env bash
#
# developer.sh
#
# Zesk PHP developer tools for Bash
#
# Copyright &copy; 2025 Market Acumen, Inc.

# Add a development link to the local version of Zesk Build for testing in local projects
# Argument: --reset - Flag. Optional. Revert the link and reinstall using the original binary.
zeskPHPDevelopmentLink() {
  local usage="_${FUNCNAME[0]}"

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
      --reset | --copy) ;;

      *)
        # _IDENTICAL_ argumentUnknown 1
        __throwArgument "$usage" "unknown #$__index/$__count \"$argument\" ($(decorate each code "${__saved[@]}"))" || return $?
        ;;
    esac
    # _IDENTICAL_ argument-esac-shift 1
    shift
  done
  developerDevelopmentLink --handler "$usage" --composer "zesk/zesk" --variable "ZESK_PHP_DEVELOPMENT_HOME" "${__saved[@]+"${__saved[@]}"}"
}
_zeskPHPDevelopmentLink() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
