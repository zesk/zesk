#!/usr/bin/env bash
#
#   _____         _
#  |__  /___  ___| | __
#    / // _ \/ __| |/ /
#   / /|  __/\__ \   <
#  /____\___||___/_|\_\
#
# Binary shell - finds PHP and a Zesk application and runs it.
# Supports all Zesk installations.
# Searches upwards from current directory to find a *.application.php file
# Supports parsing of `--cd` and `--search` to find Zesk
#
# Use sh to avoid having to install bash on virgin systems
#
# `zesk help` for more help
#
# Copyright &copy; 2025 Market Acumen, Inc.

# IDENTICAL returnMessage 39

# Return passed in integer return code and output message to `stderr` (non-zero) or `stdout` (zero)
# Argument: exitCode - Required. UnsignedInteger. Exit code to return. Default is 1.
# Argument: message ... - Optional. String. Message to output
# Return Code: exitCode
# Requires: isUnsignedInteger printf returnMessage
returnMessage() {
  local handler="_${FUNCNAME[0]}"
  local to=1 icon="✅" code="${1:-1}" && shift 2>/dev/null
  if [ "$code" = "--help" ]; then "$handler" 0 && return; fi
  isUnsignedInteger "$code" || returnMessage 2 "${FUNCNAME[1]-none}:${BASH_LINENO[1]-} -> ${handler#_} non-integer \"$code\"" "$@" || return $?
  if [ "$code" -gt 0 ]; then icon="❌ [$code]" && to=2; fi
  printf -- "%s %s\n" "$icon" "${*-§}" 1>&"$to"
  return "$code"
}
_returnMessage() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

# Test if an argument is an unsigned integer
# Source: https://stackoverflow.com/questions/806906/how-do-i-test-if-a-variable-is-a-number-in-bash
# Credits: F. Hauri - Give Up GitHub (isnum_Case)
# Original: is_uint
# Argument: value - EmptyString. Value to test if it is an unsigned integer.
# Usage: {fn} argument ...
# Return Code: 0 - if it is an unsigned integer
# Return Code: 1 - if it is not an unsigned integer
# Requires: returnMessage
isUnsignedInteger() {
  [ $# -eq 1 ] || returnMessage 2 "Single argument only: $*" || return $?
  case "${1#+}" in --help) usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]}" 0 ;; '' | *[!0-9]*) return 1 ;; esac
}
_isUnsignedInteger() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

# <-- END of IDENTICAL returnMessage

# Argument: handler - Function. Required. Call on failure
# Argument: path - Directory.  Required. Directory to generate a symlink.
# Argument: linkName - String. Required. Link short name.
# Argument: target - FileOrDirectory. File. Source file name or path.
__link() {
  local handler="$1" path="$2" linkName="$3" target="$4"

  path=$(usageArgumentDirectory "$handler" "path" "$path") || return $?
  linkName=$(usageArgumentString "$handler" "linkName" "$linkName") || return $?
  target=$(usageArgumentFileOrDirectory "$handler" "target" "$target") || return $?
  target=$(catchEnvironment "$handler" basename "$target") || return $?

  [ -e "$path/$target" ] || throwEnvironment "$handler" "$path/$target must be a file or directory" || return $?

  local link="$path/$linkName" source="$path/$target"
  if [ ! -L "$link" ]; then
    if [ -f "$link" ]; then
      throwEnvironment "$handler" "$(decorate file "$link") exists and was not a link $(decorate code "$(fileType "$link")")" || :
      catchEnvironment "$handler" mv "$link" "$link.$$.backup" || return $?
    fi
    catchEnvironment "$handler" ln -s "$linkName" "$source" || return $?
  fi
}

# {fn}: zesk
# Run commands in the PHP application context
# --cd directory - Directory. Optional. Change to this directory before looking for our application. Changes the current directory.
# --search directory - Directory. Optional. Search from this directory before looking for our application.
# --debug - Flag. Optional. Output additional debugging information.
# --set name=value - String. Optional. Set the global value `name` to `value`. Guesses the type unless quoted.
# --unset name - String. Optional. Unset the global value `name`.
# --define name=value - String. Optional. Define `name` as `value` prior to running anything else. Must not conflict.
# --name=value - Set the global value `name` to `value`. Guesses the type unless quoted.
# zeskCommand ... - The zesk command to run. Try `help` or `info`. Argument after the command are parsed by the command itself.
__zesk() {
  local me="${BASH_SOURCE[0]}"
  local home="${me%/*}"
  local handler="_${FUNCNAME[0]}"

  if [ "${me#*/vendor/bin/}" != "$me" ]; then
    home="$home/../zesk/zesk/bin"
  fi
  local tools="$home/build/tools.sh"

  # shellcheck source=/dev/null
  source "$tools" || _return 1 "Loading $tools" || return $?

  catchEnvironment "$handler" bashSourcePath "$home/tools/" || return $?

  if [ "${me%.sh}" != "$me" ]; then
    catchEnvironment "$handler" linkCreate --no-backup "${BASH_SOURCE[0]}" zesk || return $?
  fi

  local aa=() paths=() path debugFlag=false
  # _IDENTICAL_ argument-case-header 5
  local __saved=("$@") __count=$#
  while [ $# -gt 0 ]; do
    local argument="$1" __index=$((__count - $# + 1))
    [ -n "$argument" ] || throwArgument "$handler" "blank #$__index/$__count ($(decorate each quote "${__saved[@]}"))" || return $?
    case "$argument" in
    # _IDENTICAL_ --help 4
    --help)
      "$handler" 0
      return $?
      ;;
      # looking for these
    "--cd" | "--search")
      shift
      path="$(usageArgumentDirectory "$handler" "$argument" "${1-}")" || return $?
      aa+=("$argument" "$path")
      paths+=("$path")
      ;;
    --debug)
      debugFlag=true
      aa+=("$argument")
      ;;
      # --arg var
    "--config")
      shift
      aa+=("$argument" "$(usageArgumentFile "$handler" "$argument" "${1-}")") || return $?
      ;;
    "--unset" | "--set" | "--define")
      shift
      aa+=("$argument" "$(usageArgumentString "$handler" "$argument" "${1-}")") || return $?
      ;;
      # --name=value
    "--[a-z]+=*")
      aa+=("$argument")
      ;;
    -*)
      aa+=("$argument")
      ;;
    *)
      aa+=("$@")
      break
      ;;
    esac
    # _IDENTICAL_ argument-esac-shift 1
    shift
  done

  whichExists php || throwArgument "$handler" "php is not installed in PATH $(decorate code "$PATH")" || return $?

  local home tried=() previous

  path="$(catchEnvironment "$handler" pwd)" || return $?
  home="$(catchArgument "$handler" buildHome)" || return $?
  paths+=("$path" "$home")

  set -- "${aa[@]+"${aa[@]}"}"
  for path in "${paths[@]}"; do
    path=$(catchEnvironment "$handler" realPath "$path") || return $?
    if inArray "$path" "${tried[@]}"; then
      continue
    fi
    previous=""
    while [ "$path" != "$previous" ]; do
      ! $debugFlag || statusMessage decorate info "Searching $(decorate file "$path")" || return $?
      local appCount
      appCount=$(find "$path" -maxdepth 1 -type f -name '*.application.php' 2>/dev/null | wc -l)
      if [ "$appCount" -ge 1 ]; then
        for binary in "${path%/}/bin/zesk-command.php" "${path%/}/vendor/bin/zesk-command.php"; do
          if [ -x "$binary" ]; then
            ! $debugFlag || statusMessage --last decorate success "Running $(decorate file "$binary")" "$@" || return $?
            exec php "$binary" "$@"
          fi
          ! $debugFlag || statusMessage decorate info "No binary $(decorate file "$binary")" || return $?
        done
        if [ ! -d "$path/vendor/bin" ]; then
          throwEnvironment "$handler" "No vendor directory exists at $(decorate file "$path"), run: composer require zesk/zesk && composer install" || return $?
        fi
        throwEnvironment "$handler" "No vendor zesk installed at $(decorate file "$path")" || return $?
      fi
      tried+=("$path")
      previous="$path"
      path="$(catchEnvironment "$handler" dirname "$previous")" || return $?
    done
  done
  throwEnvironment "$handler" "No valid zesk application found" || return $?
}
___zesk() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

__zesk "$@"
