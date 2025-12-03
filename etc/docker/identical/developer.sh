#!/usr/bin/env bash
#
# Developer Identical Templates for php-container
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

__developerPHPContainer() {
  # IDENTICAL developerPHPContainer 5
  # php-container
  export PHP_CONTAINER_DEVELOPMENT_HOME
  buildEnvironmentLoad PHP_CONTAINER_DEVELOPMENT_HOME
  [ -z "${PHP_CONTAINER_DEVELOPMENT_HOME-}" ] || muzzle phpContainerSync
  # END OF IDENTICAL developerPHPContainer
}
