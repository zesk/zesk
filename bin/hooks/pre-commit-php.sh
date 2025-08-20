#!/usr/bin/env bash
#
# Copyright &copy; 2025 Market Acumen, Inc.
#
# Pre commit for PHP
#
#

# IDENTICAL zesk-build-hook-header 3
set -eou pipefail
# shellcheck source=/dev/null
source "${BASH_SOURCE[0]%/*}/../tools.sh"

__hookPreCommitPHP() {
  local handler="_${FUNCNAME[0]}"

  # Run internal hook first

  local hookName
  hookName="$(basename "${BASH_SOURCE[0]}")"
  hookName="${hookName%.sh}"
  hookRun --next "${BASH_SOURCE[0]}" "$hookName" "$@"

  # _IDENTICAL_ argumentNonBlankLoopHandler 6
  local __saved=("$@") __count=$#
  while [ $# -gt 0 ]; do
    local argument="$1" __index=$((__count - $# + 1))
    # __IDENTICAL__ __checkBlankArgumentHandler 1
    [ -n "$argument" ] || __throwArgument "$handler" "blank #$__index/$__count ($(decorate each quote "${__saved[@]}"))" || return $?
    case "$argument" in
    # _IDENTICAL_ helpHandler 1
    --help) "$handler" 0 && return $? || return $? ;;
    *)
      # _IDENTICAL_ argumentUnknownHandler 1
      __throwArgument "$handler" "unknown #$__index/$__count \"$argument\" ($(decorate each code "${__saved[@]}"))" || return $?
      ;;
    esac
    shift
  done

  local company

  company=$(__catch "$handler" buildEnvironmentGet BUILD_COMPANY) || return $?

  local matches
  matches=$(fileTemporaryName "$handler") || return $?
  local year
  year="$(__catchEnvironment "$handler" date +%Y)" || return $?
  files=()
  if fileNotMatches 'declare(strict_types=1);' "@copyright" "&copy; $year" "$company" -- -- - >"$matches" < <(gitPreCommitListExtension php); then
    while IFS=":" read -r file pattern; do
      printf "%s: %s missing from %s\n" "$(decorate error "No pattern used")" "$(decorate value "$pattern")" "$(decorate code "$file")"
      files+=("$file")
    done <"$matches"
    decorate info "Opening $(decorate each code "${files[@]}") ..."
    muzzle contextOpen "${files[@]}" 2>&1 &
    __throwEnvironment "$handler" "File pattern check failed" || returnClean $? "$matches" || return $?
  fi
  __catchEnvironment "$handler" rm -f "$matches" || return $?
}
___hookPreCommitPHP() {
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

__hookPreCommitPHP "$@"
