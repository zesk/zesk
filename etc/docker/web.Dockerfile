#
# web Container running Apache and PHP
#
# Copyright &copy; 2025 Market Acumen, Inc.
#
FROM php:8.1-apache

ENV BUILD_CODE=web
ENV USER_HOME=/var/www

ENV APPLICATION_HOME=/var/www/app
ENV LOG_HOME=/var/www/log
ENV APPLICATION_USER=www-data
ENV APPLICATION_PREFIX=""
ENV WEB_ROOT=$APPLICATION_HOME/public

# IDENTICAL phpContainerDockerPrefix 15
ENV APPLICATION_CONF=/etc/application.conf

ADD . "$APPLICATION_HOME"

RUN printf -- "%s\n" "$BUILD_CODE" > /etc/docker-role
COPY etc/docker/install.sh /usr/local/sbin/install.sh
COPY etc/docker/application.sh /usr/local/bin/application.sh

COPY bin/build/ /usr/local/bin/build/

RUN /usr/local/sbin/install.sh
RUN /usr/local/sbin/install.sh __installBase
RUN /usr/local/sbin/install.sh __installDevelopment
COPY .env /tmp/application.conf
# -- phpContainerDockerPrefix

RUN /usr/local/sbin/install.sh __installEnvironment /tmp/application.conf "$APPLICATION_CONF" "$APPLICATION_HOME" "$APPLICATION_PREFIX" PHP_IDE_CONFIG XDEBUG_CLIENT_HOST APPLICATION_USER LOG_HOME
RUN chmod 640 "$APPLICATION_CONF" && chown "root:$APPLICATION_USER" "$APPLICATION_CONF"

ADD . "$APPLICATION_HOME"

# ===========================================================================
# -- Middle part --

RUN mkdir -p "$LOG_HOME" && chmod 770 "$LOG_HOME" && chown "root:$APPLICATION_USER" "$LOG_HOME"

# PHP
COPY etc/docker/php.ini /usr/local/etc/php/MAP.php.ini
# XDebug
COPY etc/docker/xdebug.ini /usr/local/etc/php/conf.d/MAP.xdebug.ini

RUN /usr/local/sbin/install.sh __mapFiles /usr/local/etc/php

COPY composer.json /tmp/composer.json
RUN /usr/local/sbin/install.sh __installPHP /tmp/composer.json

RUN /usr/local/sbin/install.sh __installPHPXdebug && date > /etc/xdebug-enabled
COPY etc/docker/xdebug.ini /usr/local/etc/php/conf.d/MAP.xdebug.ini

# -- Middle part end --
# ===========================================================================

# IDENTICAL phpContainerDockerSuffix 1
RUN /usr/local/sbin/install.sh __installClean

# Apache
COPY etc/docker/web.conf /etc/apache2/sites-available/MAP.web.conf
COPY etc/docker/bashrc.sh "$USER_HOME/MAP..bashrc"
COPY etc/docker/bashrc.sh "/root/MAP..bashrc"

RUN /usr/local/sbin/install.sh __mapFiles /etc/apache2/ "$USER_HOME" "/root/" --keep "$APPLICATION_HOME"

RUN /usr/sbin/a2enmod rewrite alias
# RUN printf "%s\n" "*" | a2disconf >/dev/null || :
RUN printf "%s\n" "*" | a2dissite >/dev/null || :
RUN /usr/sbin/a2ensite web
RUN rm -rf "/var/www/html"

RUN chown www-data "$USER_HOME/.bashrc" "$USER_HOME"

USER "$APPLICATION_USER"
WORKDIR "$APPLICATION_HOME"
