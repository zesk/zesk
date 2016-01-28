#!/bin/sh
conf_file=/etc/zesk.conf
sh_source="$0"
if [ "${sh_source##.*}" = "" ]; then
	sh_source=`pwd`/$0
fi
cd `dirname $sh_source`
if [ -f "$conf_file" ]; then
	. $conf_file
	if [ -z "$zesk_root" ]; then
		echo "zesk_root is not defined in $conf_file"
	fi
fi
if [ -z "$zesk_root" ]; then
	zesk_root=`dirname $sh_source`
	zesk_root=`dirname $zesk_root`
	zesk_root=`dirname $zesk_root`
	zesk_root=`dirname $zesk_root`
fi
if [ ! -f "$conf_file" ]; then
	echo "export zesk_root=\"$zesk_root\"" >> $conf_file
fi
chmod 644 $conf_file

cd $zesk_root/modules/server
echo Running installer ...
if ! $zesk_root/bin/zesk.sh server-install; then
	echo $zesk_root/bin/zesk.sh server-install \# FAILED
	exit 1
fi

