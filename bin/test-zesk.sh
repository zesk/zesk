#!/bin/bash
#
# Run Zesk test interactively while debugging and fixing things
#
export APPLICATION_ROOT="$(cd $(dirname "$BASH_SOURCE")/..; pwd)"
PATH=$APPLICATION_ROOT/vendor/bin:$PATH

conf=/etc/zesk.conf
N_SECONDS=2
opts=
#opts="$opts --debug"
#opts="$opts --verbose"
opts="$opts --sandbox"
opts="$opts --interactive"
#opts="$opts --debug-command"
go=1
[ -f $conf ] && source $conf
while ! $APPLICATION_ROOT/bin/zesk --zesk___Command_Test__travis test $opts $*; do
	echo "========================================================="
	echo "Running tests again after $N_SECONDS seconds ..."
	echo "========================================================="
	sleep $N_SECONDS
	clear;
done
