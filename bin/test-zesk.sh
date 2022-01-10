#!/bin/bash
#
# Run Zesk test interactively while debugging and fixing things
# Run usually inside a container
#
set -e

top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd || exit)"
PATH=$top/vendor/bin:$PATH

source /usr/local/bin/zesk-bash.sh
if [ ! -d $top/vendor ]; then
	composer_install
	cd $top
	composer install
fi
opts=
#opts="$opts --debug"
opts="$opts --verbose"
#opts="$opts --sandbox"
opts="$opts --interactive"
#opts="$opts --debug-command"
[ -d "$HOME/.zesk" ] || mkdir -p "$HOME/.zesk"
$top/bin/zesk.sh --config /etc/test.conf test $opts $*
