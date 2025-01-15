#!/bin/bash
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

if [ -x "/usr/local/bin/zesk-bash.sh" ]; then
  # shellcheck source=/dev/null
  source /usr/local/bin/zesk-bash.sh

  zeskBashPrompt
  zeskBashSettings
fi

maria() {
  local usage="_${FUNCNAME[0]}"
  [ -n "${MARIADB_ROOT_PASSWORD-}" ] || __failEnvironment "$usage" "No MARIADB_ROOT_PASSWORD" || return $?
  mysql -u root -p"$MARIADB_ROOT_PASSWORD" "$@"
}
_maria() {
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
my() {
  maria "$@"
}
