#!/usr/bin/env bash
#
# Application functions for all users on remote systems (live or staging)
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

applicationBashPrompt() {
  local colorScheme userColor colors=()

  colorScheme=$(bashPromptColorScheme light)
  [ "$(id -u)" = 0 ] && userColor="warning" || userColor="black-contrast"
  IFS=":" read -r -d "" -a colors <<<"$colorScheme" || :
  colors[2]="$userColor"
  colorScheme="$(listJoin ":" "${colors[@]}")"

  bashPrompt --colors "$colorScheme" --label "💰${1-none}" bashPromptModule_ApplicationPath bashPromptModule_binBuild
}
