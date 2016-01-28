#!/usr/bin/env sh
# Use sh to avoid having to install bash on virgin systems
export PATH=$PATH:/usr/bin:/bin:/usr/local/bin

me="$0"
php=`which php`
if [ -z "$php" ]; then
	echo "PHP is not found in PATH: " . $PATH 1>&2
	exit 1
fi
if [ -z "$zesk_root" ]; then
	here=`pwd`
	cd `dirname $me`
	cd ..
	zesk_root=`pwd`
	cd $here
fi

exec "$php" "$zesk_root/bin/zesk-command.php" "$@"
