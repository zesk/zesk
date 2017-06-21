#!/usr/bin/env sh
# Use sh to avoid having to install bash on virgin systems
export PATH=$PATH:/usr/bin:/bin:/usr/local/bin

me="$0"
here=$(dirname "$0")
php=`which php`
if [ -z "$php" ]; then
	echo "PHP is not found in PATH: " . $PATH 1>&2
	exit 1
fi
start=`pwd`
app_root=
while [ -z "$app_root" ]; do
	current=`pwd`
	if ls *.application.php 1> /dev/null 2>&1; then
		app_root=`pwd`
	else
		cd ..
		next=`pwd`
		if [ "$next" = "$current" ]; then
			break
		fi
	fi
done
cd $start
if [ -z "$app_root" ]; then
	cd $here
	cd ..
	app_root=`pwd`
	cd $start
elif [ ! -d "$app_root/vendor/bin/" ]; then
	echo "No vendor directory exists, run: composer require zesk/zesk && composer update" 1>&2
	exit 1002
fi
for binary in "$app_root/bin/zesk-command.php" "$app_root/vendor/bin/zesk-command.php"; do
	if [ -x "$binary" ]; then
		exec "$php" "$binary" "$@"
	fi
done
echo "No zesk command file, run: composer require zesk/zesk && composer update" 1>&2
exit 1003
