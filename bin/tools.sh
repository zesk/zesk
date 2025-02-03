#!/usr/bin/env bash
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

#
# Usage: {fn} databaseHost [ dockerBuildArgs ... ]
# Argument: databaseHost - String. Required. Database host.
# Argument: dockerBuildArgs - Any additional arguments are passed directly to `docker build`
#
zeskContainerLatest() {
  local usage="_${FUNCNAME[0]}"
  local host quietLog start

  quietLog=$(__catchEnvironment "$usage" buildQuietLog "$usage") || return $?
  start=$(__catchEnvironment "$usage" beginTiming) || return $?

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
      *)
        [ -z "$host" ] || __throwArgument "$usage" "Host already specified" || return $?
        host="$argument"
        ;;
    esac
    # IDENTICAL argument-esac-shift 1
    shift || __throwArgument "$usage" "missing argument #$argumentIndex: $argument (Arguments: $(_command "${usage#_}" "${saved[@]}"))" || return $?
  done

  [ -z "$host" ] || __throwArgument "$usage" "host requires" || return $?

  local name=zesk

  statusMessage decorate info -n "Build container $(decorate code "$name:latest") ... "
  figlet "Build container" >>"$quietLog"
  __catchEnvironment "$usage" docker build "$@" --build-arg "DATABASE_HOST=$host" -f ./docker/php.Dockerfile --tag "$name:latest" . >>"$quietLog" || buildFailed "$quietLog" || return $?
  statusMessage --last reportTiming "$start" "Zesk latest container built in"
}
_zeskContainerLatest() {
  # IDENTICAL usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

__zeskTools() {
  local usage="_${FUNCNAME[0]}"
  __catchEnvironment "$usage" bashSourcePath "${BASH_SOURCE[0]%/*}/tools/" || return $?
  if [ $# -gt 0 ]; then
    __catchEnvironment "$usage" "$@" || return $?
  fi
}
___zeskTools() {
  # IDENTICAL usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

# IDENTICAL __install 24
# Load build tools (installing if needed) and run command
# Usage: {fn} [ relativeHome installer source [ command ... ] ]
# Argument: installer - Required. File. Installation binary.
# Argument: source - Required. File. Include file which should exist after installation.
# Argument: relativeHome - Optional. Directory. Path to application home. Default is `..`.
# Argument: command ... - Optional. Callable. A command to run and optional arguments.
# Example:      __install bin/install-bin-build.sh bin/build/tools.sh ../../.. decorate info "$@"
__install() {
  local installer="${1-}" source="${2-}" relativeHome="${3:-".."}" me="${BASH_SOURCE[0]}"
  local here="${me%/*}" e=253 arguments=()
  local install="$here/$relativeHome/$installer" tools="$here/$relativeHome/$source"
  [ -n "$installer" ] || _return $e "blank installer" || return $?
  [ -n "$source" ] || _return $e "blank source" || return $?
  if [ ! -x "$tools" ]; then
    "$install" || _return $e "$install failed" || return $?
    [ -d "${tools%/*}" ] || _return $e "$install failed to create directory ${tools%/*}" || return $?
  fi
  [ -x "$tools" ] || _return $e "$install failed to create $tools" "$@" || return $?
  shift && shift && shift && while [ $# -gt 0 ]; do arguments+=("$1") && shift; done
  # shellcheck source=/dev/null
  source "$tools" || _return $e source "$tools" || return $?
  [ ${#arguments[@]} -gt 0 ] || return 0
  __execute "${arguments[@]}" || return $?
}

# IDENTICAL __build 9
# Load build tools (installing if needed) and run command
# Usage: {fn} [ relativeHome installerPath [ command ... ] ]
# Argument: installerPath - Optional. Directory. Path to `install-bin-build.sh` binary.
# Argument: relativeHome - Required. Directory. Path to application home.
# Argument: command ... - Optional. Callable. A command to run and optional arguments.
__build() {
  local relative="${1:-".."}" installerPath="${2:-"bin"}" && shift && shift
  __install "$installerPath/install-bin-build.sh" "bin/build/tools.sh" "$relative" "$@" || return $?
}

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

__build .. bin __zeskTools "$@"
