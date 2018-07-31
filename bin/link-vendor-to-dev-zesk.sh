#!/bin/bash
ME=$(basename "$0")
if [ -z "$ZESK" ]; then
	echo "$ME: Need to define and export ZESK in your shell environment. Add:" 1>&2
	echo 1>&2
	echo "    export ZESK=/path/to/dev/zesk" 1>&2
	echo 1>&2
	exit 2
fi
if [ ! -d "$ZESK" ]; then
	echo "$ME: ZESK is not a directory: $ZESK" 1>&2
	exit 3
fi
f=$ZESK/zesk.application.php
if [ ! -f "$f" ]; then
	echo "$ME: ZESK does not appear to be correctly set up: $f missing" 1>&2
	exit 4
fi
vendor=./vendor/zesk/zesk
if [ -L $vendor ]; then
	echo "$ME: Already linked: $(readlink $vendor)"
	exit 0
elif [ -d $vendor ]; then
	rm -rf $vendor
	ln -s $ZESK $vendor
	echo "$ME: Linked to active zesk dev: $ZESK"
	exit 0
else
	echo "$ME: Can not find $vendor" 1>&2
	exit 1
fi
