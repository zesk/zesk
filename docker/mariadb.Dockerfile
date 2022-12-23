FROM mariadb:latest

ENV DEBIAN_FRONTEND=noninteractive
ENV INITDBPATH=/docker-entrypoint-initdb.d/
ENV BUILD_CODE=mariadb

RUN $(mkdir -p /root/sbin 2> /dev/null)
COPY docker/sbin/ /root/sbin/
RUN /root/sbin/docker-apt-base.sh
RUN /root/sbin/docker-apt-clean.sh


# Copy SQL
RUN mkdir -p $INITDBPATH
COPY docker/etc/env.sh /tmp/env.sh
COPY docker/mariadb/schema.sql /tmp/schema.sql
RUN set -a && . /tmp/env.sh && /root/sbin/envmap.sh < /tmp/schema.sql > ${INITDBPATH}schema.sql
RUN rm /tmp/schema.sql
RUN rm /tmp/env.sh

RUN echo -n mariadb > /etc/docker-role

COPY docker/bin/*.sh /usr/local/bin/
COPY docker/mariadb/db-health.sh /db-health.sh
ADD docker/mariadb/bash_profile /root/.bashrc
