#!/bin/bash
ME=$(basename "$0")

usage() {
	local e
	e=$1
	shift
	if [ ! -z "$*" ]; then
		echo ERROR: $* 1>&2
		echo 1>&2
	fi
	echo "Usage: $ME file command ..." 1>&2
	echo 1>&2
	echo "Command runs and checks file every 1 second for 60 seconds, then exits. Intended to be run as a cron task every minute." 1>&2
	echo 1>&2
	echo "file    - When this file appears, run command, then delete the file." 1>&2
	echo "          Directory of file must exist." 1>&2
	echo "command - Command to run with optional arguments." 1>&2
	echo 1>&2
	echo "Exit codes:" 1>&2
	echo 1>&2
	echo "10 - file not supplied" 1>&2
	echo "11 - command not supplied" 1>&2
	echo "12 - file directory does not exist" 1>&2
	echo "13 - command binary is not executable" 1>&2
	echo "\$? - command exit code is returned if it fails" 1>&2
	exit $e
}

CHANGED_FILE=$1
CHANGED_DIR=$(dirname "$CHANGED_FILE")
shift
COMMAND=$1
shift

if [ -z "$CHANGED_FILE" ]; then
	usage 10 "file not supplied" 
fi
if [ -z "$COMMAND" ]; then
	usage 11 "command not supplied" 
fi
if [ ! -d "$CHANGED_DIR" ]; then
	usage 12 "CHANGED_FILE ($CHANGED_FILE) directory does not exist"
fi
if [ ! -x "$COMMAND" ]; then
	usage 13 "$COMMAND must be executable" 
fi

exec 3>&2 			# 3 is now a copy of 2
for i in {0..59}; do
	if [ ! -f "$CHANGED_FILE" ]; then
		# Added pipe to null because this occasionally gets terminated by parent process for some reason
		exec 2> /dev/null 	# Redirect stderr to /dev/null
		sleep 1 			# avoid Terminated messages, we hope
		exec 2>&3			# restore stderr to saved
		continue
	fi
	echo $(date) Running $COMMAND $*
	if $COMMAND $*; then
		rm "$CHANGED_FILE"
	else
		exit $?
	fi
done
exec 3>&- 			# close 3
exit 0
