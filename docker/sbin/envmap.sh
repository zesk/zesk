#!/bin/bash

sedfile=$(mktemp)
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
	echo "s/{$i}/$val/g" >> $sedfile
done
sed -f $sedfile
rm $sedfile
