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

# IDENTICAL _return 27

# Return passed in integer return code and output message to `stderr` (non-zero) or `stdout` (zero)
# Argument: exitCode - Required. UnsignedInteger. Exit code to return. Default is 1.
# Argument: message ... - Optional. String. Message to output
# Exit Code: exitCode
# Requires: isUnsignedInteger printf _return
_return() {
  local code="${1:-1}" && shift 2>/dev/null
  isUnsignedInteger "$code" || _return 2 "${FUNCNAME[1]-none}:${BASH_LINENO[1]-} -> ${FUNCNAME[0]} non-integer \"$code\"" "$@" || return $?
  [ "$code" -eq 0 ] && printf -- "✅ %s\n" "${*-§}" && return 0 || printf -- "❌ [%d] %s\n" "$code" "${*-§}" 1>&2
  return "$code"
}

# Test if an argument is an unsigned integer
# Source: https://stackoverflow.com/questions/806906/how-do-i-test-if-a-variable-is-a-number-in-bash
# Credits: F. Hauri - Give Up GitHub (isnum_Case)
# Original: is_uint
# Usage: {fn} argument ...
# Exit Code: 0 - if it is an unsigned integer
# Exit Code: 1 - if it is not an unsigned integer
# Requires: _return
isUnsignedInteger() {
  [ $# -eq 1 ] || _return 2 "Single argument only: $*" || return $?
  case "${1#+}" in '' | *[!0-9]*) return 1 ;; esac
}

# <-- END of IDENTICAL _return

# Argument: usage - Function. Required. Call on failure
# Argument: path - Directory.  Required. Directory to generate a symlink.
# Argument: linkName - String. Required. Link short name.
# Argument: target - FileOrDirectory. File. Source file name or path.
__link() {
  local usage="$1" path="$2" linkName="$3" target="$4"

  path=$(usageArgumentDirectory "$usage" "path" "$path") || return $?
  linkName=$(usageArgumentString "$usage" "linkName" "$linkName") || return $?
  target=$(usageArgumentFileOrDirectory "$usage" "target" "$target") || return $?
  target=$(__catchEnvironment "$usage" basename "$target") || return $?

  [ -e "$path/$target" ] || __throwEnvironment "$usage" "$path/$target must be a file or directory" || return $?

  local link="$here/$linkName" source="$path/$target"
  if [ ! -L "$link" ]; then
    if [ -f "$link" ]; then
      __throwEnvironment "$usage" "$(decorate file "$link") exists and was not a link $(decorate code "$(betterType "$link")")" || :
      __catchEnvironment "$usage" mv "$link" "$link.$$.backup" || return $?
    fi
    __catchEnvironment "$usage" ln -s "$linkName" "$source" || return $?
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
  local here="${me%/*}"
  local tools="$here/build/tools.sh"
  local usage="_${FUNCNAME[0]}"

  # shellcheck source=/dev/null
  source "$tools" || _return 1 "Loading $tools" || return $?

  __catchEnvironment "$usage" bashSourcePath "$here/tools/" || return $?

  if [ "${me%.sh}" != "$me" ]; then
    __catchEnvironment "$usage" linkCreate --no-backup "${BASH_SOURCE[0]}" zesk || return $?
  fi

  local aa=() paths=() path debugFlag=false
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
        # looking for these
      "--cd" | "--search")
        shift
        path="$(usageArgumentDirectory "$usage" "$argument" "${1-}")" || return $?
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
        aa+=("$argument" "$(usageArgumentFile "$usage" "$argument" "${1-}")") || return $?
        ;;
      "--unset" | "--set" | "--define")
        shift
        aa+=("$argument" "$(usageArgumentString "$usage" "$argument" "${1-}")") || return $?
        ;;
        # --name=value
      "--[a-z]+=*")
        aa+=("$argument")
        ;;
      *)
        aa+=("$argument")
        ;;
    esac
    # _IDENTICAL_ argument-esac-shift 1
    shift
  done

  whichExists php || __throwArgument "$usage" "php is not installed in PATH $(decorate code "$PATH")" || return $?

  local home tried=() previous

  path="$(__catchEnvironment "$usage" pwd)" || return $?
  home="$(__catchArgument "$usage" buildHome)" || return $?
  paths+=("$path" "$home")

  set -- "${aa[@]+"${aa[@]}"}"
  for path in "${paths[@]}"; do
    path=$(__catchEnvironment "$usage" realPath "$path") || return $?
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
          __throwEnvironment "$usage" "No vendor directory exists at $(decorate file "$path"), run: composer require zesk/zesk && composer install" || return $?
        fi
        __throwEnvironment "$usage" "No vendor zesk installed at $(decorate file "$path")" || return $?
      fi
      tried+=("$path")
      previous="$path"
      path="$(__catchEnvironment "$usage" dirname "$previous")" || return $?
    done
  done
  __throwEnvironment "$usage" "No valid zesk application found" || return $?
}
___zesk() {
  # _IDENTICAL_ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

__zesk "$@"
