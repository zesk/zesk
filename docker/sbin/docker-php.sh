#!/bin/bash
#
# Installing PHP
#
set -e

export DEBIAN_FRONTEND=noninteractive
export PHP_INI_DIR=/usr/local/etc/php

apt-get install -y wget unzip zip awscli default-mysql-client
docker-php-ext-install mysqli pcntl
docker-php-ext-install calendar

# For gd
apt-get install -y zlib1g-dev libpng-dev libfreetype6 libfreetype6-dev libjpeg-dev libgif-dev libpng-dev
docker-php-ext-configure gd --with-freetype --with-jpeg
docker-php-ext-install gd

# docker-php-ext-install readline json opcache
