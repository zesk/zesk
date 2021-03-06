#!/bin/bash
#
# This command makes svstat available to non-root programs, creating a file .svstat inside each service directory
#
# It should be run via crontab as root, e.g.
#
# * * * * * : DaemonTools service status helper ; /path/to/modules/daemontools/sbin/svstat-helper.sh
#
whoops() {
	echo $* 1>&2
	exit 1
}
NEED_BIN="svstat find xargs"
for b in $NEED_BIN; do
	wh=$(which $b)
	if [ -z "$wh" ]; then
		whoops $b is not installed
	fi
done
service_home=/etc
if [ ! -d "$service_home" ]; then
	whoops service home \($service_home\) is not a directory
fi
for d in $(find $service_home -name supervise -type d | xargs -n 1 dirname); do 
	svstat $d > $d/.svstat; 
done
