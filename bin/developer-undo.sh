#!/usr/bin/env bash
#
# developer-undo.sh
#
# Copyright &copy; 2025 Market Acumen, Inc.
#
# Zesk PHP Developer scripts (undo any changes to shell)

if isFunction __zeskPHPDeveloperContextUndo; then
  __zeskPHPDeveloperContextUndo
  unset __zeskPHPDeveloperContextUndo
fi
