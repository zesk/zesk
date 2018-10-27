#!/bin/bash
ME=$(basename "$0")

usage() {
	local e
	e=$1
	shift
	echo $* 1>&2
	echo $ME file command ... 1>&2
	echo 1>&2
	echo file - When this file appears, run command, then delete the file 1>&2
	echo command - Command to run with optional arguments 1>&2
	echo 1>&2
	exit $1
}

CHANGED_FILE=$1
CHANGED_DIR=$(dirname "$CHANGED_FILE")
shift
COMMAND=$1
shift

if [ ! -d "$CHANGED_DIR" ]; then
	usage 10 "CHANGED_FILE ($CHANGED_FILE) directory does not exist"
fi
if [ ! -x "$COMMAND" ]; then
	usage 11 "$COMMAND must be executable" 
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
		exit 100
	fi
done
exec 3>&- 			# close 3
exit 0