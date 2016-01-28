#!/bin/bash
echo "Killing daemontools and starting from scratch ..."
ps aux | grep svscan | grep -v grep | awk '{ print $2 }' | xargs kill -9
svc -dx /service/* /service/*/log
. /etc/rc.local
echo "Waiting 5 seconds ..."
sleep 5
svstat /service
echo "Done."
