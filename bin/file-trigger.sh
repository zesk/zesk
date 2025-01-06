#!/bin/bash
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

errorEnvironment=1
errorArgument=2

me=$(basename "$0")

_fileTriggerUsage() {
  usageDocument "./bin/$me" fileTrigger "$@"
}

# fn: {base}
# Usage: {fn} file command ...
# command runs and checks file every 1 second for 60 seconds, then exits. Intended to be run as a cron task every minute.
# Ideally suited for permitting non-root processes to control root processes using a file existence as a signal.
# Argument: file - When this file appears, run command, then delete the file. Directory of file must exist.
# Argument: command - command to run with optional arguments.
# Exit code: 1 - file directory does not exist, command binary is not executable
# Exit code: 2 - file not supplied, command not supplied
# Exit code: Other - command exit code is returned if it fails
# Example: (add to the crontab of a privileged user):
# Example:
# Example:     * * * * * /usr/sbin/file-trigger.sh /var/www/app/.apache /usr/sbin/apache2ctl restart
# Example:
# Example: Permits your application to restart Apache by creating the above file
# Example: Ideally use a more complex script which checks Apache's configuration as valid and then restarts.
fileTrigger() {
  local watchFile command

  watchFile=${1-}
  shift || :
  command=${1-}
  shift || :

  if [ -z "$watchFile" ]; then
    _fileTriggerUsage "$errorArgument" "file not supplied"
  fi
  if [ -z "$command" ]; then
    _fileTriggerUsage "$errorArgument" "command not supplied"
  fi
  if [ ! -d "$(dirname "$watchFile")" ]; then
    _fileTriggerUsage "$errorEnvironment" "$watchFile directory does not exist"
  fi
  if [ ! -x "$command" ]; then
    _fileTriggerUsage "$errorEnvironment" "$command must be executable"
  fi

  exec 3>&2 # 3 is now a copy of 2
  for i in {0..59}; do
    if [ ! -f "$watchFile" ]; then
      # Added pipe to null because this occasionally gets terminated by parent process for some reason
      exec 2>/dev/null   # Redirect stderr to /dev/null
      sleep 1 2>dev/null # avoid Terminated messages, we hope
      exec 2>&3          # restore stderr to saved
      continue
    fi
    printf "%s @%s running %s %s" "$(date)" "$i" "$command" "$*"
    if ! "$command" "$*"; then
      return $errorEnvironment
    fi
    rm "$watchFile" >/dev/null || :
    break
  done
  exec 3>&- # close 3
  exit 0
}

fileTrigger "$@"
