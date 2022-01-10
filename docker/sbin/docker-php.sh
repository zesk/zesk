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
# docker-php-ext-install readline json opcache
