FROM mariadb:latest

ENV DEBIAN_FRONTEND=noninteractive
ENV INITDBPATH=/docker-entrypoint-initdb.d/
ENV BUILD_CODE=mariadb

RUN $(mkdir -p /root/sbin 2> /dev/null)
COPY docker/sbin/ /root/sbin/
COPY bin/build/map.sh /usr/local/bin/map.sh

RUN /root/sbin/docker-apt-base.sh
RUN /root/sbin/docker-apt-clean.sh


# Copy SQL
RUN mkdir -p $INITDBPATH
COPY docker/etc/application.conf /tmp/application.conf
COPY docker/mariadb/schema.sql /tmp/schema.sql
RUN set -a && . /tmp/application.conf && /usr/local/bin/map.sh < /tmp/schema.sql > ${INITDBPATH}schema.sql
RUN rm /tmp/schema.sql
RUN rm /tmp/application.conf

RUN echo -n mariadb > /etc/docker-role

COPY docker/bin/*.sh /usr/local/bin/
COPY docker/mariadb/db-health.sh /db-health.sh
ADD docker/mariadb/bash_profile.sh /root/.bashrc
