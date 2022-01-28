#!/bin/bash
#
# Installing PHP xdebug
#
set -e

inipath=/usr/local/etc/php/php.ini
if [ ! -f "$inipath" ]; then
echo "$inipath file not found" 1>&2
exit 1
fi
echo "Setting php ini path to $inipath"
pear config-set php_ini "$inipath"

if ! test "$DEVELOPMENT"; then
  exit 0
fi
echo "Installing xdebug ..."
pecl install xdebug
