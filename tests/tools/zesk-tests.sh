#!/usr/bin/env bash
#
# Zesk Tests
#
# Test the CLI
#
# Copyright &copy; 2025, Market Acumen, Inc.
#

testZeskCLI() {
  local handler="returnMessage"

  local home

  home=$(catchReturn "$handler" buildHome) || return $?

  local zeskBin

  local tempFile clean=()

  local zz=(--cd "$home")

  mockEnvironmentStart XDEBUG_ENABLED ""

  tempFile=$(fileTemporaryName "$handler") || return $?
  clean+=("$tempFile" "$tempFile.1")
  zeskBin="$home/bin/zesk.sh"
  statusMessage decorate info "Testing $zeskBin"
  catchReturn "$handler" "$zeskBin" "${zz[@]}" module >"$tempFile" || return $?
  assertFileContains "$tempFile" "cron:       false" "daemon:     false" "csv:        false" || return $?
  catchReturn "$handler" "$zeskBin" "${zz[@]}" module cron daemon -- module >"$tempFile.1" || return $?
  assertFileContains "$tempFile.1" "cron:       true" "daemon:     true" "csv:        false" || return $?
  catchEnvironment "$handler" rm -f "${clean[@]}" || return $?

  mockEnvironmentStop XDEBUG_ENABLED
}
