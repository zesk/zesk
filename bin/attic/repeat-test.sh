#!/bin/bash
if [ "$1" = "-t" ]; then
	shift
fi
cwd=`pwd`
OUTFILE=/tmp/repeat-test.$$.out
echo "Starting on `/bin/date` ..." > $OUTFILE
PID=$$
state_file=/tmp/repeat-test.$PID.state
status_file=/tmp/repeat-test.$PID.status
while true; do
	clear
	echo $*
	ALWAYS_FAIL=0
	if [ ! -x $1 ]; then
		echo "File $* is not executable, or is missing, adios."
		exit 1
	fi
	if grep -q ALWAYS_FAIL $1; then
		echo "### This test should always fail"
		ALWAYS_FAIL=1
	fi
	SUCCESS=0
	($* 2>&1; echo $? > $status_file) | tee $OUTFILE
	RS=`cat $status_file`
	rm $status_file
	if [ "$RS" = "0" ]; then
		SUCCESS=1
		if grep -q PHP-ERROR $OUTFILE; then
			SUCCESS=0
			if [ "`wc -l < $OUTFILE`" -gt 60 ]; then
				echo '********************************************************************************'
				echo 'Errors found:'
				echo '********************************************************************************'
				grep -A 5 PHP-ERROR $OUTFILE
				echo '********************************************************************************'
			fi
		fi
	fi
	if [ $ALWAYS_FAIL = 1 ] && [ $SUCCESS = 0 ]; then
		SUCCESS=1
	fi
	if [ $SUCCESS = 1 ]; then
		[ -f $state_file ] && rm $state_file
		rm $OUTFILE > /dev/null 2>&1
		exit
	else
		if [ ! -z "$TEST_EDITOR" ]; then
			if [ ! -f "$state_file" ]; then
				echo "$*" > $state_file
				echo "Running $TEST_EDITOR $* ..."
				$TEST_EDITOR $*
			elif [ "`cat $state_file`" != "$*" ]; then
				echo "Running $TEST_EDITOR $* ..."
				$TEST_EDITOR $*
			fi
		fi
	fi
	i=5
	sleep 0
	echo
	while [ $i -gt 0 ]; do
		echo -n "$i ... ";
		sleep 1
		i=$((i-1))
	done
	echo "Go!"
	echo "Repeating test on `/bin/date` ..." > $OUTFILE
done
[ -f $state_file ] && rm $state_file
