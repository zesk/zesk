#!/bin/sh
. /etc/environment.sh
PATH=/usr/local/bin:/usr/bin:/bin
HOST=`uname -n`
if [ `uname -s` = Linux ]; then
    HTTPD_BIN=/usr/sbin/apache2
	HTTPD_CONFIG=/etc/httpd/httpd.conf
elif [ `uname -s` = FreeBSD ]; then
    HTTPD_BIN=/usr/local/sbin/httpd
	HTTPD_CONFIG=/usr/local/etc/apache22/httpd.conf
else
	exit 1
fi
SVCLOG=$LOG_PATH/httpd/httpd.log
ENV="env -i LANG=C PATH=/usr/local/bin:/usr/bin:/bin HOST=$HOST"
umask 007
exec $ENV $HTTPD_BIN -f $HTTPD_CONFIG -DNO_DETACH >> $SVCLOG 2>&1
