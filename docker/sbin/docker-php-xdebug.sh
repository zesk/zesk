#!/bin/bash
#
# Installing PHP xdebug
#
set -e

ini_path=/usr/local/etc/php/php.ini
if [ ! -f "$ini_path" ]; then
  echo "$ini_path file not found" 1>&2
  exit 1
fi
echo "Setting php ini path to $ini_path"
pear config-set php_ini "$ini_path"

echo "Installing xdebug ..."
pecl install xdebug
