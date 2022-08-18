#!/usr/bin/env bash

ERR_ENV=1

cd /usr/local/bin/
if [ ! -f composer-installer.php ]; then
  echo "No composer-installer.php" 1>&2
  exit $ERR_ENV
fi
php composer-installer.php
if [ ! -f composer.phar ]; then
  echo "composer.phar does not exist?" 1>&2
  exit $ERR_ENV
fi
mv composer.phar composer
chmod 755 composer
echo "Installation complete"
