#!/bin/sh
use_defaults=false
while [ $# -gt 0 ]; do
	case "$1" in
		-y)
			echo "Yes to defaults."; 
			use_defaults=true;
			shift
		 	;;
		*)
			echo "Unknown argument: $1"
			shift
			;;
	esac
done

pkg_register() {
	while [ $# -gt 0 ]; do
		if pkg_info | grep -v $1; then
			echo "$1 is installed"
		else
			pkg_add -rF $1
		fi
		shift
	done
}
apt_register() {
	aptitude -y install $*
}
export uid=`id -u`
export ostype=`uname`
if [ "$ostype" = "FreeBSD" ]; then
	echo "# FreeBSD Basic packages ...";
	pkg_register bash openssl curl rsync subversion php5 php5-curl php5-pcntl php5-json php5-posix
	#  php5 php5-curl php5-mysql php5-pcntl php5-sysvmsg php5-sysvsem
elif [ "$ostype" = "Linux" ]; then
	echo "# Linux Basic packages ...";
	apt_register bash openssl curl rsync subversion php5 php5-curl php5-json 
#	apt_register php5-pcntl php5-posix
else
	echo "Unhandled operating system: $ostype"
fi

install_dir="/usr/local/zesk/"

while true; do
	echo -n "Install source code where? ($install_dir) ";
	if ! $use_defaults; then
		read d
	else
		echo $install_dir
	fi 
	if [ ! -z "$d" ]; then
		install_dir=$d
	fi
	if [ -d $install_dir ]; then
		break;
	fi
	echo -n "Create $install_dir? (Y/n) ";
	if ! $use_defaults; then
		read yes
	else
		echo "y"
		yes="y"
	fi
	if [ "$yes" = "y" ]; then
		mkdir -p $install_dir
		chmod 755 $install_dir
	fi
	if [ -w $install_dir ]; then
		break;
	else
		echo "$install_dir is not writable" 1>&2
		exit 1
	fi
done

save_dir=`pwd`
cd $install_dir; 
install_dir=`pwd`;
svn co --non-interactive --username anonymous --password zeskquality http://code.marketacumen.com/zesk/trunk/ $install_dir

runner="$install_dir/modules/server/bin/install.sh"

if [ ! -f $runner ]; then
	echo "No installer $runner found after update." 1>&2
	exit 2
fi
if [ ! -x $runner ]; then
	chmod +x $runner
fi

echo -n "Run $runner ? (Ctrl-C to stop) "
if ! $use_defaults; then
	read yes
else 
	echo You betcha.
fi

echo "zesk_root=$install_dir" > /etc/zesk.conf
exec $install_dir/modules/server/bin/install.sh
