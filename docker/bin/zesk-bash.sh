#!/usr/bin/env bash
#
# Copyright &copy; 2025 Market Acumen, Inc.
#
# Generic Shell setup for ALL users, ALL environments

# Bash settings for all containers
zeskBashPrompt() {
  local role

  role="$1"
  if [ -z "$role" ]; then
    role=$(cat /etc/docker-role 2>/dev/null || echo -n "no-role")
  fi
  zeskPrompt --prefix "⚡️${role} $(decorate code "${BUILD_CODE:-no-code}")"
}

#
# Find text in any file in a project from current directory down.
#
#     phpFind CONSTANT
#
# Also try:
#
#     phpFind -l CONSTANT
#
phpFind() {
  local aa=()

  aa=()
  if [ "${1-}" = "-l" ]; then
    aa+=("${1-}")
    shift
  fi
  local ff extension extensions=(cfg classes conf css email htm html inc ini install j2 js jsx less list md module php4 php5 phpt po psql router scss sh sql tpl yml)

  for extension in "${extensions[@]}"; do
    ff+=(-or -name "*.$extension")
  done
  find . -type f \( -name '*.php' "${ff[@]}" \) ! -name '*.min.js' -print0 | xargs -0 grep "${aa[@]+"${aa[@]}"}" "$*"
}

zeskBashSettings() {
  export BLOCKSIZE=M
  export LS_OPTIONS='--color=auto'

  alias ls='ls $LS_OPTIONS'
  alias ll='ls $LS_OPTIONS -l'
  alias l='ls $LS_OPTIONS -lA'
  alias ..='cd ..'
}

zeskApplicationContext() {
  local usage="_${FUNCNAME[0]}"
  local conf=/etc/application.conf

  __usageEnvironment "$usage" environmentFileLoad "$conf" || return $?
}
_zeskApplicationContext() {
  # IDENTICAL usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
