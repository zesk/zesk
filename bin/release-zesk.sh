#!/bin/bash

#
# Assumptions
#
export ZESK_ROOT="$(cd $(dirname "$BASH_SOURCE")/..; pwd)"
GIT=`which git`
ZESK=$ZESK_ROOT/bin/zesk.sh

# 
# Functions
#
ltrim()
{
	echo $* | sed 's/^ *//'
}
rtrim()
{
	echo $* | sed 's/ *$//'
}
trim()
{
	ltrim `rtrim $*`
}
pause() {
	echo -n "$* (Ctrl-C to stop, Enter to continue) "; 
	read
}

yes_continue() {
	echo -n "$* (Y/n) "; 
	read yes
	case $yes in
	y|Y|yes|Yes|YES)
		return 0
		;;
	*)
		return 1
		;;
	esac
}
#
# Check assumptions
#
if [ ! -d ./.git ]; then
	echo "No .git directory at $ZESK_ROOT, stopping" 1>&2
	exit 1
fi
if [ ! -x "$GIT" ]; then
	echo "git is not installed in $PATH" 1>& 2
	exit 2
fi

if [ ! -x "$ZESK" ]; then
	echo "zesk.sh is not executable?" 1>& 2
	exit 2
fi

#
# Sync with remote
#
echo "Synchronizing with remote ..."
$GIT pull --tags > /dev/null 2>&1
$GIT push --tags > /dev/null 2>&1

#
# Make sure repository state seems sane
#
nlines=$(trim $(git status --short | wc -l | awk '{ print $1 }'))
if [ $nlines -gt 0 ]; then
	git status --short 
	pause "Current git status, ok?"
fi

#
# Determine last and next versions
#
cd $ZESK_ROOT
ZESK_LAST_VERSION=`git tag | sort -t. -k 1.2,1n -k 2,2n -k 3,3n -k 4,4n | tail -1`	
ZESK_CURRENT_VERSION=$(trim $($ZESK version))
ZESK_CURRENT_VERSION="v$ZESK_CURRENT_VERSION"
VERSION_EXISTS=`git tag | grep "$ZESK_CURRENT_VERSION"`
if [ ! -z "$VERSION_EXISTS" ]; then
	echo "Version $ZESK_CURRENT_VERSION already exists, already tagged."
	exit 16
fi
if [ $ZESK_LAST_VERSION = $ZESK_CURRENT_VERSION ]; then
	echo "Version $ZESK_CURRENT_VERSION up to date, nothing to do."
	exit 17
fi
echo "Zesk previous version is: $ZESK_LAST_VERSION"
echo " Zesk release version is: $ZESK_CURRENT_VERSION"
pause "Versions look OK?"

#
# Edit release notes
#
current_log=$ZESK_ROOT/docs/current.md
permanent_log=$ZESK_ROOT/docs/versions.md
echo '## Release {version}' > $current_log
echo >> $current_log
$GIT log --pretty=format:'- %s' ^$ZESK_LAST_VERSION^{} HEAD | sort -u >> $current_log
echo >> $current_log
echo >> $current_log
echo '<!-- Generated automatically by release-zesk.sh, beware editing! -->' >> $current_log

while true; do
	if [ ! -z "$EDITOR" ]; then
		echo "Opening editor for $current_log"
		$EDITOR $current_log
	fi
	cat $current_log
	if yes_continue "Generate release with release notes shown above?"; then
		break
	fi
done

#
# Update release notes for all versions and current version
#
cat $current_log $permanent_log | grep -v 'by release-zesk.sh' | sed -e "s/{version}/$ZESK_CURRENT_VERSION/g" > $ZESK_ROOT/$$.temp
mv $ZESK_ROOT/$$.temp $permanent_log
$GIT commit -m "Release $ZESK_CURRENT_VERSION" $current_log $permanent_log

#
# Push to remote
# 
$GIT push --tags

#
# Tag automatically
#
$ZESK github --tag --description-file $current_log

#
# Pull from remote
#
git pull
echo "Release $ZESK_CURRENT_VERSION completed"
