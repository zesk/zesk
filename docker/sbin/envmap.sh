#!/bin/bash
set -eo pipefail

sed_file=$(mktemp)
for i in $(set | cut -d = -f 1); do
	case $i in
	LD_*)
		continue
		;;
	_)
		continue
		;;
	*)
		;;
	esac
	val=$(echo ${!i} | sed 's/\//\\\//g')
	echo "s/{$i}/$val/g" >> "$sed_file"
done
sed -f "$sed_file"
rm "$sed_file"
