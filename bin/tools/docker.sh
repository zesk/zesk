#!/usr/bin/env bash
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

#
# Usage: {fn} databaseHost [ dockerBuildArgs ... ]
# Argument: databaseHost - String. Required. Database host.
# Argument: dockerBuildArgs - Any additional arguments are passed directly to `docker build`
#
#zeskContainerLatest() {
#  local usage="_${FUNCNAME[0]}"
#  local host quietLog start
#
#  quietLog=$(catchReturn "$usage" buildQuietLog "$usage") || return $?
#  start=$(catchReturn "$usage" timingStart) || return $?
#
#  # _IDENTICAL_ argument-case-header 5
#  local __saved=("$@") __count=$#
#  while [ $# -gt 0 ]; do
#    local argument="$1" __index=$((__count - $# + 1))
#    [ -n "$argument" ] || throwArgument "$usage" "blank #$__index/$__count ($(decorate each quote "${__saved[@]}"))" || return $?
#    case "$argument" in
#      # _IDENTICAL_ --help 4
#      --help)
#        "$usage" 0
#        return $?
#        ;;
#      *)
#        [ -z "$host" ] || throwArgument "$usage" "Host already specified" || return $?
#        host="$argument"
#        ;;
#    esac
#    # _IDENTICAL_ argument-esac-shift 1
#    shift
#  done
#
#  [ -z "$host" ] || throwArgument "$usage" "host requires" || return $?
#
#  local name=zesk
#
#  statusMessage decorate info -n "Build container $(decorate code "$name:latest") ... "
#  figlet "Build container" >>"$quietLog"
#  catchEnvironment "$usage" docker build "$@" --build-arg "DATABASE_HOST=$host" -f ./docker/php.Dockerfile --tag "$name:latest" . >>"$quietLog" || buildFailed "$quietLog" || return $?
#  statusMessage --last timingReport "$start" "Zesk latest container built in"
#}
#_zeskContainerLatest() {
#  # __IDENTICAL__ usageDocument 1
#  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
#}

if whichExists docker; then
  __dockerPHPExec() {
    local home="$1" target="$2" user="$3" ttyArg=() && shift 3
    [ -t 0 ] || ttyArg+=(-T)
    docker compose -f "$home/docker-compose.yml" exec "${ttyArg[@]}" -u "${user}" "$target" "$@"
  }

  zeskCompose() {
    local envs=(
      --arg DATABASE_ROOT_PASSWORD=zesk-applications
      --env CONTAINER_PORT_WEB=4071
      --env CONTAINER_PORT_DATABASE=4072
      --env XDEBUG_ENABLED=true
      --env PHP_IDE_KEY="serverName=zesk"
      --env XDEBUG_CLIENT_HOST="host.docker.internal"
      --env DEVELOPMENT=1
    )
    muzzle pushd "$(buildHome)"
    dockerCompose "${envs[@]}" "$@"
  }
  _zeskCompose() {
    # __IDENTICAL__ usageDocument 1
    usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
  }
fi
