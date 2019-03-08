#!/bin/bash
ME=$(basename "$0")

usage() {
	local e
	e=$1
	shift
	exec 1>&2 # Copy fd 2 into slot fd 1 so writing to stdout will actually write to stderr
	if [ ! -z "$*" ]; then
		echo ERROR: $*
		echo
	fi
	echo "Usage: $ME file command ..."
	echo
	echo "Command runs and checks file every 1 second for 60 seconds, then exits. Intended to be run as a cron task every minute."
	echo "Ideally suited for permitting non-root processes to control root processes using a file existence as a signal."
	echo
	echo "file    - When this file appears, run command, then delete the file."
	echo "          Directory of file must exist."
	echo "command - Command to run with optional arguments."
	echo
	echo "Exit codes:"
	echo
	echo "10 - file not supplied"
	echo "11 - command not supplied"
	echo "12 - file directory does not exist"
	echo "13 - command binary is not executable"
	echo "\$? - command exit code is returned if it fails"
	echo ""
	echo "Example (add to the crontab of a privileged user):"
	echo ""
	echo "* * * * * $path_to_app/vendor/bin/$ME $path_to_app/.apache /usr/sbin/apache2ctl restart"
	echo ""
	echo "Permits your application to restart Apache by creating the above file."
	echo "Ideally use a more complex script which checks Apache's configuration as valid and then restarts."
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
		sleep 1 2> dev/null	# avoid Terminated messages, we hope
		exec 2>&3			# restore stderr to saved
		continue
	fi
	echo $(date) @$i Running $COMMAND $*
	if $COMMAND $*; then
		rm "$CHANGED_FILE"
	else
		exit $?
	fi
done
exec 3>&- 			# close 3
exit 0
