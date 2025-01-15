#!/usr/bin/env bash
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

if [ -z "$ZESK_ROOT" ]; then
  ZESK_ROOT=$(dirname "$(dirname "${BASH_SOURCE[0]}")")
  export ZESK_ROOT
fi

#
# File Converters
#
export PATH="$PATH:$ZESK_ROOT/bin"
export ZESK_DOCKER_COMPOSE="${ZESK_ROOT}/docker-compose.yml"

_Zesk() {
  local user
  local ttyArg
  ttyArg=()
  if [ ! -t 0 ]; then
    ttyArg+=(-T)
  fi
  user=$1
  shift
  docker compose -f "${ZESK_DOCKER_COMPOSE}" exec "${ttyArg[@]}" -u "${user}" php "$@"
}

alias suZeskBash='_Zesk root bash'
alias ZeskBash='_Zesk www-data bash'
