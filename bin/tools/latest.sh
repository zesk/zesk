#!/usr/bin/env bash
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

# Get the latest version of Zesk and install it at `./vendor/zesk/zesk`
# Exit Code: 0 - Up to date
# Exit Code: 1 - Failed
zeskLatest() {
  local handler="_${FUNCNAME[0]}"

  local vendorZesk="./vendor/zesk"
  local repo="https://github.com/zesk/zesk"

  local zeskHome="$vendorZesk/zesk" old="$vendorZesk/zesk.COMPOSER" new="$vendorZesk/zesk.GIT"

  [ -d $vendorZesk ] || throwEnvironment "$handler" "$(decorate file "$vendorZesk") must be a directory. Stopping." || return $?
  if [ -d "$zeskHome/.git" ]; then
    catchEnvironment "$handler" git -C "$zeskHome" pull origin main || return $?
    decorate success "Up to date: $(decorate file "$vendorZesk")"
    return 0
  fi

  [ ! -d "$old" ] || catchEnvironment "$handler" rm -rf "$old" || return $?

  catchEnvironment "$handler" git clone "$repo" "$new" || return $?
  [ -d "$new" ] || throwEnvironment "$handler" "Directory not created: $(decorate file "$new")" || return $?
  catchEnvironment "$handler" mv "$zeskHome" "$old" || return $?
  catchEnvironment "$handler" mv "$new" "$zeskHome" || return $?
  decorate success 'Zesk now linked to the latest'
  return 0
}
_zeskLatest() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
