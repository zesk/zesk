#!/bin/bash
#
# Run Zesk test interactively while debugging and fixing things
#
conf=/etc/zesk.conf
N_SECONDS=2
opts=
opts="$opts --debug"
opts="$opts --verbose"
opts="$opts --sandbox"
opts="$opts --interactive"
opts="$opts --debug-command"
go=1
[ -f $conf ] && source $conf
while ! ./bin/zesk --debug --zesk\\Command_Test::travis test $opts; do
	echo "========================================================="
	echo "Running tests again after $N_SECONDS seconds ..."
	echo "========================================================="
	sleep $N_SECONDS
	clear;
done
