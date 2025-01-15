#!/usr/bin/env bash
#
# pipeline-setup.sh
#
# Set up Zesk build
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

# Create a link
#
# Argument: target - Exists. File. Source file name or path.
# Argument: linkName - String. Required. Link short name, created next to `target`.
linkCreate() {
  local usage="_${FUNCNAME[0]}"

  local target="" path="" linkName="" backupFlag=true

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
      --no-backup)
        backupFlag=false
        ;;
      *)
        if [ -z "$target" ]; then
          target=$(usageArgumentExists "$usage" "target" "$argument") || return $?
          path=$(__usageEnvironment "$usage" dirname "$target") || return $?
        elif [ -z "$linkName" ]; then
          linkName=$(usageArgumentString "$usage" "linkName" "$argument") || return $?
        else
          # IDENTICAL argumentUnknown 1
          __failArgument "$usage" "unknown argument #$argumentIndex: $argument (Arguments: $(_command "${saved[@]}"))" || return $?
        fi
        ;;
    esac
    # IDENTICAL argument-esac-shift 1
    shift || __failArgument "$usage" "missing argument #$argumentIndex: $argument (Arguments: $(_command "${usage#_}" "${saved[@]}"))" || return $?
  done

  [ -n "$target" ] || __failArgument "$usage" "Missing target" || return $?
  [ -n "$linkName" ] || __failArgument "$usage" "Missing linkName" || return $?

  [ ! -L "$target" ] || __failArgument "$usage" "Can not link to another link ($(decorate file "$target") is a link)" || return $?

  target=$(__usageEnvironment "$usage" basename "$target") || return $?

  [ -e "$path/$target" ] || __failEnvironment "$usage" "$path/$target must be a file or directory" || return $?

  local link="$path/$linkName"
  if [ ! -L "$link" ]; then
    if [ -e "$link" ]; then
      __failEnvironment "$usage" "$(decorate file "$link") exists and was not a link $(decorate code "$(betterType "$link")")" || :
      __usageEnvironment "$usage" mv "$link" "$link.createLink.$$.backup" || return $?
      clean+=("$link.$$.backup")
    fi
  else
    local actual
    actual=$(__usageEnvironment "$usage" readlink "$link") || return $?
    if [ "$actual" = "$target" ]; then
      return 0
    fi
    __usageEnvironment "$usage" mv "$link" "$link.createLink.$$.badLink" || return $?
    clean+=("$link.$$.badLink")
  fi
  __usageEnvironment "$usage" muzzle pushd "$path" || return $?
  __usageEnvironment "$usage" ln -s "$target" "$linkName" || _undo $? muzzle popd || return $?
  __usageEnvironment "$usage" muzzle popd || return $?
  $backupFlag || __usageEnvironment "$usage" rm -rf "${clean[@]}" || return $?
}
_linkCreate() {
  # IDENTICAL usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

# Validates a value is not blank and exists in the file system
# Upon success, outputs the file name
# Usage: {fn} usageFunction variableName variableValue [ noun ]
# Argument: usageFunction - Required. Function. Run if usage fails
# Argument: variableName - Required. String. Name of variable being tested
# Argument: variableValue - Required. String. Required only in that if it's blank, it fails.
# Argument: noun - Optional. String. Noun used to describe the argument in errors, defaults to `file or directory`
# Exit Code: 2 - Argument error
# Exit Code: 0 - Success
usageArgumentExists() {
  local usage="$1" args
  args=("$@")
  args[3]="${4-}"
  if [ ${#args[@]} -ne 4 ]; then
    __failArgument "$usage" "${FUNCNAME[0]} Need at least 3 arguments"
    return $?
  fi
  __usageArgumentHelper "file or directory" "${args[@]}" test -e
}
