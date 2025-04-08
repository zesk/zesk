#
# database Container running MariaDB
#
# Copyright &copy; 2025 Market Acumen, Inc.
#
FROM mariadb:10

ENV BUILD_CODE=db
ENV USER_HOME=/root

ENV APPLICATION_HOME=/root/app
ENV INITDBPATH=/docker-entrypoint-initdb.d/
ENV MARIADB_ROOT_PASSWORD=hard-to-guess
ENV DATABASE_NETWORK=%
ENV DSN="mysqli://docker:test@db/phpContainer"

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
RUN touch /tmp/application.conf
# -- phpContainerDockerPrefix

RUN /usr/local/sbin/install.sh __installEnvironment /tmp/application.conf "$APPLICATION_CONF" "$APPLICATION_HOME" MARIADB_ROOT_PASSWORD DSN

RUN chmod 600 "$APPLICATION_CONF"
RUN chown root:root "$APPLICATION_CONF"

# ===========================================================================
# -- Middle part --

#
# Nada
#

# -- Middle part end --
# ===========================================================================

# IDENTICAL phpContainerDockerSuffix 1
RUN /usr/local/sbin/install.sh __installClean

# Copy SQL
RUN mkdir -p "$INITDBPATH"
COPY etc/docker/db-health.sh /db-health.sh
COPY etc/docker/db-connect.sh /db-connect.sh
COPY etc/docker/schema.sql "$INITDBPATH/MAP.schema-original.sql"

COPY etc/docker/bashrc.sh "/root/MAP..bashrc"

RUN /usr/local/sbin/install.sh __mapFiles / --keep "$INITDBPATH" --keep "$APPLICATION_HOME"
