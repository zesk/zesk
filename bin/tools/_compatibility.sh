#!/usr/bin/env bash
#
# _compatibility.sh
#
# Set up Zesk Build with any needed fixes
#
# Copyright &copy; 2025 Market Acumen, Inc.

#
# Simplistic URL parsing. Converts a `url` into values which can be parsed or evaluated:
#
# - `url` - URL
# - `host` - Database host
# - `user` - Database user
# - `password` - Database password
# - `port` - Database port
# - `name` - Database name
#
# Does little to no validation of any characters so best used for well-formed input.
#
# Now works on multiple URLs, output is separated by a blank line for new entries
#
# Exit Code: 0 - If parsing succeeds
# Exit Code: 1 - If parsing fails
# Summary: Simple Database URL Parsing
# Usage: urlParse url
# Argument: url - a Uniform Resource Locator used to specify a database connection
# Argument: --prefix prefix - String. Optional. Prefix variable names with this string.
# Argument: --uppercase - Flag. Optional. Output variable names in uppercase, not lowercase (the default).
# Example:     eval "$(urlParse scheme://user:password@host:port/path)"
# Example:     echo $name
urlParse() {
  local usage="_${FUNCNAME[0]}" upperCase=false prefix=""

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
    --uppercase)
      upperCase=true
      ;;
    --prefix)
      shift
      prefix=$(usageArgumentString "$usage" "$argument" "${1-}") || return $?
      ;;
    *)
      local u="${1-}"

      # parts
      local url="$u" path="" name="" user="" password="" host="" port="" error=""
      local scheme="${u%%://*}"

      if [ "$scheme" != "$url" ] && [ -n "$scheme" ]; then
        # Have a scheme
        u="${u#*://}"
        # u is user:pass@host:port/path...
        # path
        name="${u#*/}"
        if [ "$name" != "$u" ]; then
          path="/$name"
        else
          path="/"
          name=""
        fi
        u="${u%%/*}"
        # u now possibly: user:pass@host:port
        host="${u##*@}"
        if [ "$host" = "$u" ]; then
          user=""
          password=""
        else
          user="${u%@*}"
          password="${user#*:}"
          if [ "$password" = "$user" ]; then
            password=""
          else
            user="${user%%:*}"
          fi
        fi
        port="${host##*:}"
        if [ "$port" = "$host" ]; then
          port=""
        else
          host="${host%:*}"
        fi
        error=""
      else
        error="no-scheme"
        scheme=""
      fi
      local variable part
      for part in url path name scheme user password host port error; do
        variable="$part"
        ! $upperCase || variable=$(uppercase "$part")
        printf "%s%s=%s\n" "$prefix" "$variable" "$(quoteBashString "${!part}")"
      done
      printf -- "\n"
      : "$path" # usage warning
      # Exit code 1 if failed
      [ -z "$error" ] || return 1
      ;;
    esac
    # _IDENTICAL_ argument-esac-shift 1
    shift
  done
}
_urlParse() {
  # _IDENTICAL_ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
