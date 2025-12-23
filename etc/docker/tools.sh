#!/usr/bin/env bash
#
# PHP Container related
#
# Copyright &copy; 2025 Market Acumen, Inc.
#
# Distribute: true
#

phpContainerSync() {
  local handler="_${FUNCNAME[0]}"

  export PHP_CONTAINER_DEVELOPMENT_HOME
  catchReturn "$handler" buildEnvironmentLoad PHP_CONTAINER_DEVELOPMENT_HOME || return $?
  developerDevelopmentLink --handler "$handler" --path "etc/docker" --binary echo --variable PHP_CONTAINER_DEVELOPMENT_HOME --development-path "etc/docker" --version-json "composer.json" --copy
}
_phpContainerSync() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

phpContainerInstall() {
  local handler="_${FUNCNAME[0]}"

  local home

  home=$(catchReturn "$handler" buildHome) || return $?
  code=$(catchReturn "$handler" buildEnvironmentGet APPLICATION_CODE) || return $?
  if [ "$code" != "${code#*php-container}" ]; then
    throwEnvironment "$handler" "Can not run in application $(decorate code "$code") at $(decorate file --no-app "$home")" || return $?
  fi
  export PHP_CONTAINER_DEVELOPMENT_HOME
  if ! muzzle buildEnvironmentFiles PHP_CONTAINER_DEVELOPMENT_HOME 2>&1; then
    catchReturn "$handler" environmentAddFile PHP_CONTAINER_DEVELOPMENT_HOME || return $?
  fi
  catchReturn "$handler" buildEnvironmentLoad PHP_CONTAINER_DEVELOPMENT_HOME || return $?
  [ -n "$PHP_CONTAINER_DEVELOPMENT_HOME" ] || throwEnvironment "$handler" "Need PHP_CONTAINER_DEVELOPMENT_HOME" || return $?

  developerDevelopmentLink --handler "$handler" --path "etc/docker" --binary echo --variable PHP_CONTAINER_DEVELOPMENT_HOME --development-path "etc/docker" --version-json "composer.json" --copy

  local f ff=("docker-compose.yml" ".php-cs-fixer.php" ".dockerignore" ".gitignore" "bin/tools/php-container.sh")
  for f in "${ff[@]}"; do
    source="$PHP_CONTAINER_DEVELOPMENT_HOME/$f"
    target="$home/$f"
    [ ! -f "$target" ] || continue
    [ -d "${target%/*}" ] || continue
    decorate info "Copying $(decorate file "$source") to $(decorate file "$target") ..."
    throwEnvironment "$handler" cp -f "$source" "$target" || return $?
  done
}
_phpContainerInstall() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
