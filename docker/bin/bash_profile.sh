#!/bin/bash
#
# Copyright &copy; 2025 Market Acumen, Inc.
#
if [ -x "/usr/local/bin/zesk-bash.sh" ]; then
  # shellcheck source=/dev/null
  source /usr/local/bin/zesk-bash.sh

  zeskBashPrompt
  zeskBashSettings
fi

if [ -d "/zesk" ]; then
  PATH=$PATH:/zesk/bin:/zesk/vendor/bin
fi

Xdebug_ON() {
  export XDEBUG_ENABLED=1
  echo "XDebug debugging enabled..."
}
Xdebug_off() {
  unset XDEBUG_ENABLED
  echo "XDebug debugging disabled..."
}
