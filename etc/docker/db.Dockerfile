#
# database Container running MariaDB
#
# Copyright &copy; 2025 Market Acumen, Inc.
#
FROM mariadb:10

ARG DATABASE_ROOT_PASSWORD
ENV BUILD_CODE=db
ENV USER_HOME=/root

ENV APPLICATION_HOME=/root/app
ENV APPLICATION_PREFIX=""
ENV INITDBPATH=/docker-entrypoint-initdb.d/
ENV DATABASE_NETWORK=%
ENV DSN="mysqli://docker:test@db/phpContainer"
ENV MARIADB_ROOT_PASSWORD=${DATABASE_ROOT_PASSWORD?no-root-password}

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

# ===========================================================================
# -- Middle part --

RUN /usr/local/sbin/install.sh __installEnvironment /tmp/application.conf "$APPLICATION_CONF" "$APPLICATION_HOME" "$APPLICATION_PREFIX" MARIADB_ROOT_PASSWORD DSN
RUN chmod 640 "$APPLICATION_CONF" && chown "root:$APPLICATION_USER" "$APPLICATION_CONF"

# -- Middle part end --
# ===========================================================================

# IDENTICAL phpContainerDockerSuffix 1
RUN /usr/local/sbin/install.sh __installClean

# Copy SQL
RUN mkdir -p "$INITDBPATH"
COPY etc/docker/db-health.sh /db-health.sh
COPY etc/docker/db-connect.sh /db-connect.sh
COPY etc/docker/schema.sql "$INITDBPATH/MAP.schema-original.sql"

COPY etc/docker/bashrc.sh "$USER_HOME/MAP..bashrc"

RUN /usr/local/sbin/install.sh __mapFiles "$INITDBPATH" "$USER_HOME" "/root/" --keep "$INITDBPATH" --keep "$APPLICATION_HOME"
