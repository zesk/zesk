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
  local handler="_${FUNCNAME[0]}"

  # _IDENTICAL_ argumentNonBlankLoopHandler 6
  local __saved=("$@") __count=$#
  while [ $# -gt 0 ]; do
    local argument="$1" __index=$((__count - $# + 1))
    # __IDENTICAL__ __checkBlankArgumentHandler 1
    [ -n "$argument" ] || throwArgument "$handler" "blank #$__index/$__count ($(decorate each quote -- "${__saved[@]}"))" || return $?
    case "$argument" in
    # _IDENTICAL_ helpHandler 1
    --help) "$handler" 0 && return $? || return $? ;;
    --reset | --copy) ;;
    *)
      # _IDENTICAL_ argumentUnknownHandler 1
      throwArgument "$handler" "unknown #$__index/$__count \"$argument\" ($(decorate each code -- "${__saved[@]}"))" || return $?
      ;;
    esac
    shift
  done
  developerDevelopmentLink --handler "$handler" --composer "zesk/zesk" --variable "ZESK_PHP_DEVELOPMENT_HOME" "${__saved[@]+"${__saved[@]}"}"
}
_zeskPHPDevelopmentLink() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

zeskPR() {
  bitbucketPRNew "$@"
}

# Update the `vendor/bin` binaries with the latest from the vendor Zesk directory.
zeskVendorBin() {
  local handler="_${FUNCNAME[0]}"
  local home

  home=$(catchReturn "$handler" buildHome) || return $?

  local binDir="$home/vendor/bin"
  [ -d "$binDir" ] || throwEnvironment "$handler" "Missing $binDir" || return $?

  local zeskHome="$home/vendor/zesk/zesk"
  [ -d "$zeskHome" ] || throwEnvironment "$handler" "Missing $zeskHome" || return $?

  local binary
  for binary in "zesk.sh" "zesk-command.php"; do
    if [ ! -f "$binDir/$binary" ] || muzzle diff -q "$zeskHome/bin/$binary" "$binDir/$binary"; then
      catchEnvironment "$handler" cp "$zeskHome/bin/$binary" "$binDir/$binary" || return $?
      statusMessage --last decorate info "Updated $(decorate file "$binDir/$binary")"
    fi
  done
}

_zeskVendorBin() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
