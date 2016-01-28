#!/bin/sh                                                                                                                                                                                                                                                                                                                  

### BEGIN INIT INFO
# Provides: pure-ftpd
# Required-Start: $all
# Required-Stop: $all
# Default-Start: 2 3 4 5
# Default-Stop: 0 1 6
# Short-Description: starts pure-ftpd server
# Description: starts pure-ftpd server
### END INIT INFO

DAEMON=/usr/local/sbin/pure-ftpd
LOGFILE={LOG_PATH}/pure-ftpd.log
NAME={NAME}
PATH=/usr/local/sbin:$PATH
PIDFILE=/var/run/pure-ftpd.pid
AUTHENTICATIONS="{AUTHENTICATIONS}"
EXTRA_ARGS="{PUREFTPD_EXTRA_ARGS}"
LOGS="--altlog w3c:$LOGFILE"
APACHE_RUN_USER={APACHE_RUN_USER}
APACHE_RUN_GROUP={APACHE_RUN_GROUP}

test -x $DAEMON || exit 0

chown_log() {
	if [ ! -z "$LOGFILE" ]; then
		touch $LOGFILE && chown $APACHE_RUN_USER:$APACHE_RUN_GROUP $LOGFILE
	fi
}

case "$1" in
	start)
		echo -n "Starting $DESC: "
		start-stop-daemon --start --pidfile "$PIDFILE" --exec  $DAEMON -- $AUTHENTICATIONS -A -B
		echo "$NAME."
		chown_log
		;;
	stop)
		echo -n "Stopping $DESC: "
		start-stop-daemon --stop --oknodo --pidfile "$PIDFILE"
		echo "$NAME."
		chown_log
		;;
	restart|force-reload)
		echo -n "Restarting $DESC: "
		start-stop-daemon --stop --oknodo --pidfile "$PIDFILE"
		sleep 1
		start-stop-daemon --start --pidfile "$PIDFILE" --exec  $DAEMON -- $AUTHENTICATIONS -A -B
		echo "$NAME."
		chown_log
		;;
	reload)
		echo -n "Reloading $DESC configuration: "
		start-stop-daemon --stop --signal HUP --quiet --pidfile "$PIDFILE" --exec $DAEMON -- $AUTHENTICATIONS -A -B
		echo "$NAME."
		chown_log
		;;
	*)
		N=/etc/init.d/$NAME
		echo "Usage: $N {start|stop|restart|force-reload}" >&2
		exit 1
		;;
esac

exit 0
