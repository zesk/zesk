#!/usr/bin/env bash
# IDENTICAL __applicationToolsList.sh 15
#
# __applicationToolsList.sh
#
# Load this first and then track all functions loaded after this loads
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

# Start tracking - anything declared after this will be returned as a defined "new" thing
developerTrack "${BASH_SOURCE[0]}"

# Return the application tools added (functions globals and aliases)
__applicationToolsList() {
  developerTrack "${BASH_SOURCE[0]}" --list
}
