#!/usr/bin/env bash
#
# developer-undo.sh
#
# Copyright &copy; 2025 Market Acumen, Inc.
#
# Zesk PHP Developer scripts (undo any changes to shell)

if isFunction __zeskPHPDeveloperUndo; then
  __zeskPHPDeveloperUndo
  unset __zeskPHPDeveloperUndo
fi
