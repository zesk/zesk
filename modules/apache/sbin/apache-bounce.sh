#!/bin/bash
PATH=$PATH:/sbin:/usr/sbin:/usr/local/sbin
APACHECTL=${APACHECTL:=$(which apachectl)}
APACHECTL_RESTART=${APACHECTL_RESTART:=graceful}
if $APACHECTL configtest > /dev/null 2>&1; then
	if $APACHECTL $APACHECTL_RESTART; then
		echo $(date) Restarted apache
		exit 0
	else
		RS=$?
		echo $(date) Apache restart failed with error code $?
		exit $RS
	fi
fi

echo "Apache can not reboot - error in configuration" 1>&2
apachectl configtest 1>&2
exit 1
