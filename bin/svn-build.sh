#!/bin/bash
#
# Build an application and check a version into Subversion with the fully-built software
#
# Process is:
#
# 1. Ensure subversion status is clean (nothing to add, commit, or update)
# 2. Optionally clean vendor/node_modules
#

ME=$(basename "$0")
OPTION_BUMP=0
OPTION_CLEAN=0

error() {
	local rs
	rs=$1
	shift
	echo $ME: $* 1>&2
	exit $rs
}

find_app_root() {
	local n
	n=0
	while [ -z "$(ls *.application.php 2> /dev/null)" ]; do
		cd ..
		n=$(($n + 1))
		if [ "$(pwd)" = "/" ]; then
			error 200 'Unable to find *.application.php file'
		fi
		if [ $n -gt 10 ]; then
			error 201 'Unable to find *.application.php file after recursing ' $n ' times'
		fi
	done
	echo -n $(pwd)
	return 0
}
if [ -z "$APPLICATION_ROOT" ]; then
	export APPLICATION_ROOT=$(find_app_root)
	echo "Application root is $APPLICATION_ROOT"
fi

find_composer() {
	local c 
	c=$(which composer 2> /dev/null);
	if [ -z "$c" ]; then
		c=$HOME/bin/composer.phar
	fi
	echo -n $c
}

composer=$(find_composer)
php=$(which php)
yarn=$(which yarn)
svn=$(which svn 2> /dev/null);
if [ ! -x "$php" ]; then
	error 10 "PHP binary ($php) is not executable, exiting"
fi
if [ ! -x "$yarn" ]; then
	error 11 echo "Yarn binary ($yarn) is not executable, exiting"
fi
if [ ! -f $composer ]; then
	error 12 "Composer binary ($composer) does not exist, exiting"
fi
if [ ! -x "$svn" ]; then
	error 13 "No svn binary found in $PATH"
fi

while [ $# -ge 1 ]; do
	arg=$1
	shift
	case $arg in
	--clean)
		OPTION_CLEAN=$((1-$OPTION_CLEAN))
		;;
	--bump)
		OPTION_BUMP=$((1-$OPTION_BUMP))
		;;
	*)
		error 14 "Unknown argument $arg"
	esac
done

svnRoot() {
	local url prefix
	
	url=$($svn info $1 | grep 'Relative URL' | cut -d ' ' -f 3);
	prefix=${url%tags*}
	if [ $prefix = $url ]; then
		prefix=${url%trunk*}
		if [ $prefix = $url ]; then
			error 99 "No trunk or tags found in $url"
		else
			echo ${prefix}tags
		fi
	else
		echo ${prefix}tags
	fi
}

subversion_build_sync() {
	$svn status . | egrep '^\?' | awk '{ print $2 }' | xargs $svn add
	$svn status . | egrep '^!' | awk '{ print $2 }' | xargs $svn rm
}

logdir=$APPLICATION_ROOT/log
logfile=$logdir/build.log

if [ ! -d "$logdir" ]; then
	if ! mkdir $logdir; then
		error 5 "Can not create $logdir - permission error"
	fi
fi
echo "Logging detailed build information to $logfile ..."

cd $APPLICATION_ROOT
svn_status=$($svn status)
if [ ! -z "$svn_status" ]; then
	echo "Subversion status is not clean: "
	$svn status
	error 99 "Need all files checked in or ignored ..."
fi
if [ $OPTION_CLEAN = 1 ]; then
	for d in $(find . -name node_modules -type d) vendor cache; do
		echo "Cleaning $d"
		rm -rf $d
	done
fi
svn_root=$(svnRoot)
if [ -z "$svn_root" ]; then
	error 100 "No Subversion root project directory can be determined"
fi
echo "### composer install" >> $logfile
if ! $php $composer -q install >> $logfile; then
	error 101 "Composer failed with exit code $?"
fi

zesk=$APPLICATION_ROOT/vendor/bin/zesk

if [ ! -x "$zesk" ]; then
	error 102 "Zesk binary ($zesk) is not executable, exiting"
fi
if [ -f .build ]; then
	last_version=$(cat .build)
else 
	last_version=
fi
app_version=$($zesk version)
if [ "$OPTION_BUMP" = "1" ]; then
	if [ "$app_version" = "$last_version" ]; then
		echo "Updating application version for build to $($zesk version --patch)"
	fi
else
	if [ "$app_version" = "$last_version" ]; then
		echo "Keeping current application version $app_version"
	else
		echo "Latest application version will be updated to $app_version"
	fi
fi
echo "Running update ..."
echo "### zesk update" >> $logfile
$zesk update --quiet >> $logfile
RC=$?
if [ $RC -ne 0 ]; then
	error 103 "Zesk update failed with exit code $RC"
fi
$zesk cache clear >> $logfile

cd $APPLICATION_ROOT
BUILD_DIRECTORY_LIST=$(find . -type d -name build | grep -v node_modules | grep -v vendor)
for builddir in $BUILD_DIRECTORY_LIST; do
	cd $APPLICATION_ROOT/$builddir/..
	find build -type f | xargs rm
	echo "Build React app at $builddir ..."
	echo "### yarn build" >> $logfile
	if ! $yarn >> $logfile; then
		error 104 "Yarn install failed with exit code $?"
	fi
	if ! $yarn build >> $logfile; then
		error 104 "Yarn build failed with exit code $?"
	fi
	if [ ! -d build ]; then
		error 108 "Build directory was not created by yarn build"
	fi
done
cd $APPLICATION_ROOT

version=$(vendor/bin/zesk version)
echo -n $version > .build

svn_tag_url="$svn_root/v$version"
echo "Creating tag $svn_tag_url"

for builddir in $BUILD_DIRECTORY_LIST; do
	cd $APPLICATION_ROOT/$builddir
	echo "Synchronizing $builddir directory ..."
	subversion_build_sync > $logfile
	$svn add `find . -type f` > /dev/null 2>&1
done

cd $APPLICATION_ROOT
$svn add .build > /dev/null 2>&1
if ! $svn commit -m "replacing build" $BUILD_DIRECTORY_LIST .build etc > $logfile 2>&1; then
	error 107 "Unable to commit build directory changes and etc"
fi

echo "Replacing subversion tag ..."
$svn rm -m "replacing" $svn_tag_url 2> /dev/null > $logfile
$svn cp -m "release" . $svn_tag_url > $logfile
echo "Application built successfully to $svn_tag_url"
exit 0
