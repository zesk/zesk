#!/usr/bin/env bash
#
# Run this if you want to upgrade your scripts
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

# Clean up deprecated code automatically. This can be dangerous (uses `cannon`) so use it on
# a clean build checkout and examine changes manually each time.
#
# - Replaces deprecated code in shell and other files
# - Finds tokens which are no longer in use and reports on the files found
# - Does common misspellings and replaces them
#
# Usage: {fn}
# fn: {base}
# Exit Code: 0 - All cleaned up
# Exit Code: 1 - If fails or validation fails
#
# There are three flags to control the three processes, you can set them using arguments (all three cleanups are by default enabled)
#
# Argument: --no-configuration - Optional. Flag. Do not fix any configuration issues from past versions.
# Argument: --just-configuration - Optional. Flag. Just fix any configuration issues from past versions. (Sets all other flags to false)
# Argument: --configuration - Optional. Flag. Do the fix any configuration issues from past versions. (other flags remain unchanged)
# Argument: --no-cannon - Optional. Flag. Do not do the cannon to replace tokens in code.
# Argument: --just-cannon - Optional. Flag. Just do the cannon to replace tokens in code. (Sets all other flags to false)
# Argument: --cannon - Optional. Flag. Do the cannon  to replace tokens in code. (other flags remain unchanged)
# Argument: --no-tokens - Optional. Flag. Report on deprecated tokens found in the code.
# Argument: --just-tokens - Optional. Flag. Just report on deprecated tokens found in the code. (Sets all other flags to false)
# Argument: --tokens - Optional. Flag. Report on deprecated tokens found in the code. (other flags remain unchanged)
# Argument: --no-spelling - Optional. Flag. Search for common (wink) misspellings and fix them.
# Argument: --just-spelling - Optional. Flag. Just search for common (wink) misspellings and fix them. (Sets all other flags to false)
# Argument: --find findArgs -- - Optional. ArgumentList. Terminated with `--` or end of arguments.
# MOST RECENT STUFF at the top as it will likely have more hits
# See: docs/_templates/deprecated.md
__deprecatedCleanup() {
  local handler="_${FUNCNAME[0]}" exitCode=0
  local ignoreExtras=()

  # _IDENTICAL_ argumentNonBlankLoopHandler 6
  local __saved=("$@") __count=$#
  while [ $# -gt 0 ]; do
    local argument="$1" __index=$((__count - $# + 1))
    # __IDENTICAL__ __checkBlankArgumentHandler 1
    [ -n "$argument" ] || throwArgument "$handler" "blank #$__index/$__count ($(decorate each quote "${__saved[@]}"))" || return $?
    case "$argument" in
    # _IDENTICAL_ helpHandler 1
    --help) "$handler" 0 && return $? || return $? ;;
    --find)
      shift
      while [ $# -gt 0 ]; do
        if [ "$1" = "--" ]; then
          break
        fi
        ignoreExtras+=("$1")
        shift
      done
      [ $# -eq 0 ] || shift
      ;;
    *)
      # _IDENTICAL_ argumentUnknownHandler 1
      throwArgument "$handler" "unknown #$__index/$__count \"$argument\" ($(decorate each code "${__saved[@]}"))" || return $?
      ;;
    esac
    shift
  done

  export __BUILD_DEPRECATED_EXTRAS
  __BUILD_DEPRECATED_EXTRAS=("${ignoreExtras[@]+"${ignoreExtras[@]}"}")

  __BUILD_DEPRECATED_EXTRAS+=(! -path '*/vendor/*' ! -path '*/.composer/*')

  start=$(catchEnvironment "returnMessage" timingStart) || return $?
  local home

  home=$(catchEnvironment "returnMessage" buildHome) || return $?

  statusMessage decorate info "Deprecated cannon in \"$home\" ..."
  __deprecatedCannonsByVersion "$home" || exitCode=$?

  [ "$exitCode" -ne 0 ] || statusMessage --last timingReport "$start" "$(decorate code "deprecated.sh") completed in"
  return "$exitCode"
}
___deprecatedCleanup() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

#
#  ▛▀▖                  ▐       ▌ ▞▀▖               ▐
#  ▌ ▌▞▀▖▛▀▖▙▀▖▞▀▖▞▀▖▝▀▖▜▀ ▞▀▖▞▀▌ ▚▄ ▌ ▌▛▀▖▛▀▖▞▀▖▙▀▖▜▀
#  ▌ ▌▛▀ ▙▄▘▌  ▛▀ ▌ ▖▞▀▌▐ ▖▛▀ ▌ ▌ ▖ ▌▌ ▌▙▄▘▙▄▘▌ ▌▌  ▐ ▖
#  ▀▀ ▝▀▘▌  ▘  ▝▀▘▝▀ ▝▀▘ ▀ ▝▀▘▝▀▘ ▝▀ ▝▀▘▌  ▌  ▝▀ ▘   ▀
#

#
# --cannons
#
__deprecatedCannonsByVersion() {
  deprecatedCannonFile deprecatedIgnore "$1/bin/deprecated.txt"
}
