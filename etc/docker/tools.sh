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
