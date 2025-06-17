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
  local usage="_${FUNCNAME[0]}"

  # Run internal hook first

  local hookName
  hookName="$(basename "${BASH_SOURCE[0]}")"
  hookName="${hookName%.sh}"
  hookRun --next "${BASH_SOURCE[0]}" "$hookName" "$@"

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
      *)
        # _IDENTICAL_ argumentUnknown 1
        __throwArgument "$usage" "unknown #$__index/$__count \"$argument\" ($(decorate each code "${__saved[@]}"))" || return $?
        ;;
    esac
    # _IDENTICAL_ argument-esac-shift 1
    shift
  done

  local company

  company=$(__catchEnvironment "$usage" buildEnvironmentGet BUILD_COMPANY) || return $?

  while read -r file; do
    while read -r line; do
      copyrightExceptions+=("$line")
    done <"$file"
  done < <(find . -name ".skip-copyright" -type f ! -path "*/.*/*")

  local matches
  matches=$(fileTemporaryName "$usage") || return $?
  local year
  year="$(__catchEnvironment "$usage" date +%Y)" || return $?
  files=()
  if fileNotMatches "@copyright" "&copy; $year" "$company" -- "${copyrightExceptions[@]+"${copyrightExceptions[@]}"}" -- - >"$matches" < <(gitPreCommitListExtension php); then
    while IFS=":" read -r file pattern; do
      printf "%s: %s missing from %s\n" "$(decorate error "No pattern used")" "$(decorate value "$pattern")" "$(decorate code "$file")"
      files+=("$file")
    done <"$matches"
    decorate info "Opening $(decorate each code "${files[@]}") ..."
    muzzle contextOpen "${files[@]}" 2>&1 &
    __throwEnvironment "$usage" "File pattern check failed" || _clean $? "$matches" || return $?
  fi
  __catchEnvironment "$usage" rm -f "$matches" || return $?
}
___hookPreCommitPHP() {
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

__hookPreCommitPHP "$@"
