#!/usr/bin/env bash
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

# Get the latest version of Zesk and install it at `./vendor/zesk/zesk`
# Exit Code: 0 - Up to date
# Exit Code: 1 - Failed
zeskLatest() {
  local usage="_${FUNCNAME[0]}"

  local vendorZesk="./vendor/zesk"
  local repo="https://github.com/zesk/zesk"

  local zeskHome="$vendorZesk/zesk" old="$vendorZesk/zesk.COMPOSER" new="$vendorZesk/zesk.GIT"

  [ -d $vendorZesk ] || __throwEnvironment "$usage" "$(decorate file "$vendorZesk") must be a directory. Stopping." || return $?
  if [ -d "$zeskHome/.git" ]; then
    __catchEnvironment "$usage" git -C "$zeskHome" pull origin main || return $?
    decorate success "Up to date: $(decorate file "$vendorZesk")"
    return 0
  fi

  [ ! -d "$old" ] || __catchEnvironment "$usage" rm -rf "$old" || return $?

  __catchEnvironment "$usage" git clone "$repo" "$new" || return $?
  [ -d "$new" ] || __throwEnvironment "$usage" "Directory not created: $(decorate file "$new")" || return $?
  __catchEnvironment "$usage" mv "$zeskHome" "$old" || return $?
  __catchEnvironment "$usage" mv "$new" "$zeskHome" || return $?
  decorate success 'Zesk now linked to the latest'
  return 0
}
_zeskLatest() {
  # _IDENTICAL_ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
