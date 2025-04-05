#!/usr/bin/env bash
#
# developer.sh
#
# Zesk PHP developer tools for Bash
#
# Copyright &copy; 2025 Market Acumen, Inc.

zeskPHPDevelopmentLink() {
  local usage="_${FUNCNAME[0]}"
  local home

  local desiredTarget="/usr/local/zesk"

  home=$(buildHome)
  if [ ! -d "$home/vendor" ]; then
    __throwEnvironment "$usage" "No vendor directory" || return $?
  fi

  local vendorZesk="$home/vendor/zesk/zesk"
  local verb="Writing"

  if [ -d "$vendorZesk" ] && [ ! -L "$vendorZesk" ]; then
    verb="Backing up and writing"
    __catchEnvironment "$usage" mv "$vendorZesk" "$home/vendor/zesk/zesk.BACKUP" || return $?
  fi
  if [ -L "$vendorZesk" ]; then
    local linkTarget
    linkTarget=$(__catchEnvironment "$usage" readlink "$vendorZesk") || return $?
    if [ "$linkTarget" = "$desiredTarget" ]; then
      decorate info "Zesk is linked locally"
      return 0
    fi
    verb="Rewriting"
  fi
  [ ! -e "$vendorZesk" ] || __catchEnvironment "$usage" rm -f "$vendorZesk" || return $?
  decorate info "$verb Zesk link to $(decorate file "$desiredTarget")"
  __catchEnvironment "$usage" ln -s "$desiredTarget" "$vendorZesk" || return $?
}
_zeskPHPDevelopmentLink() {
  # _IDENTICAL_ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
