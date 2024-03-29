FROM php:8.1-cli
ENV PHP_INI_DIR=/usr/local/etc/php
ENV BUILD_CODE=zesk
ENV ETC_CONF=/usr/local/etc
ARG DATABASE_HOST=mariadb

RUN $(mkdir -p /root/sbin 2> /dev/null)
COPY docker/sbin/ /root/sbin/
RUN /root/sbin/docker-apt-base.sh
RUN /root/sbin/docker-php.sh
RUN /root/sbin/docker-apt-clean.sh

COPY docker/etc/env.sh /etc/env.sh
RUN echo "DATABASE_HOST=$DATABASE_HOST" >> /etc/env.sh
COPY docker/etc/test.conf /etc/test.conf

COPY docker/php/php.ini /tmp/php.ini
RUN set -a && . /etc/env.sh && /root/sbin/envmap.sh < /tmp/php.ini > /usr/local/etc/php/php.ini
RUN rm /tmp/php.ini

COPY docker/php/xdebug.ini /tmp/xdebug.ini
RUN set -a && . /etc/env.sh && /root/sbin/envmap.sh < /tmp/xdebug.ini > /usr/local/etc/php/conf.d/xdebug.ini
RUN rm /tmp/xdebug.ini

RUN /root/sbin/docker-php-xdebug.sh

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY docker/bin/composer-installer.php /usr/local/bin/
RUN /root/sbin/install-composer.sh
RUN rm /usr/local/bin/composer-installer.php

COPY docker/bin/*.sh /usr/local/bin/
COPY docker/bin/*.php /usr/local/bin/
COPY docker/bin/bash_profile /var/www/.bashrc

RUN mkdir -v -m 0700 /var/www/.zesk /var/www/.ssh /var/www/log/
RUN chown www-data:www-data /var/www/.ssh /var/www/.zesk /var/www/.bashrc /var/www/log/

ADD docker/bin/bash_profile /root/.bashrc
RUN mkdir /root/.zesk/

RUN echo -n zesk > /etc/docker-role

RUN chsh -s /bin/bash www-data

USER www-data
ADD ./ /zesk
WORKDIR /zesk

CMD ["tail", "-f", "/dev/null"]
