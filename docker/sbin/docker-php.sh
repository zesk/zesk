#!/bin/bash
#
# Installing PHP
#
set -e

export DEBIAN_FRONTEND=noninteractive
export PHP_INI_DIR=/usr/local/etc/php

apt-get install -y wget unzip zip awscli default-mysql-client
docker-php-ext-install mysqli pcntl calendar
docker-php-ext-enable mysqli pcntl calendar

# For gd
apt-get install -y zlib1g-dev libpng-dev libfreetype6 libfreetype6-dev libjpeg-dev libgif-dev libpng-dev
docker-php-ext-configure gd --with-freetype --with-jpeg
docker-php-ext-install gd
docker-php-ext-enable gd

# for intl
apt-get install -y libicu-dev
docker-php-ext-install intl
docker-php-ext-enable intl

# docker-php-ext-install readline json opcache
