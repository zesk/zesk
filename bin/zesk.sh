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

# IDENTICAL _return 24
# Usage: {fn} [ exitCode [ message ... ] ]
# Argument: exitCode - Optional. Integer. Exit code to return. Default is 1.
# Argument: message ... - Optional. String. Message to output to stderr.
# Exit Code: exitCode
_return() {
  local r="${1-:1}" && shift
  isUnsignedInteger "$r" || _return 2 "${FUNCNAME[1]-none}:${BASH_LINENO[1]-} -> ${FUNCNAME[0]} non-integer $r" "$@" || return $?
  printf "[%d] ❌ %s\n" "$r" "${*-§}" 1>&2 || : && return "$r"
}

# Test if an argument is an unsigned integer
# Source: https://stackoverflow.com/questions/806906/how-do-i-test-if-a-variable-is-a-number-in-bash
# Credits: F. Hauri - Give Up GitHub (isnum_Case)
# Original: is_uint
# Usage: {fn} argument ...
# Exit Code: 0 - if it is an unsigned integer
# Exit Code: 1 - if it is not an unsigned integer
#
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
  # IDENTICAL argument-case-header 5
  local saved=("$@") nArguments=$#
  while [ $# -gt 0 ]; do
    local argument argumentIndex=$((nArguments - $# + 1))
    argument="$(usageArgumentString "$usage" "argument #$argumentIndex (Arguments: $(_command "${usage#_}" "${saved[@]}"))" "$1")" || return $?
    case "$argument" in
      # IDENTICAL --help 4
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
    # IDENTICAL argument-esac-shift 1
    shift || __throwArgument "$usage" "missing argument #$argumentIndex: $argument (Arguments: $(_command "${usage#_}" "${saved[@]}"))" || return $?
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
      if muzzle find "$path" -maxdepth 1 -type f -name '*.application.php'; then
        for binary in "$path/bin/zesk-command.php" "$path/vendor/bin/zesk-command.php"; do
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
}
___zesk() {
  # IDENTICAL usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

__zesk "$@"
