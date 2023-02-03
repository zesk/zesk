#!/usr/bin/env bash
ERR_ENV=1
ERR_BUILD=1001

# Debug bash
set -x

top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." || exit $ERR_ENV; pwd)"
# Optional
composer=$(which composer)
dc=$(which docker-compose)
docker=$(which docker)

set -eo pipefail

envFile="$top/.env"

if [ -z "$docker" ]; then
  echo "No docker found in $PATH" 1>&2
  exit $ERR_ENV
fi
if [ -z "$dc" ]; then
  pip install docker-compose
fi
#if test "$INSTALL_COMPOSER" || [ -z "$composer" ]; then
#  composer="$top/.bin/composer"
#  if [ ! -x "$composer" ]; then
#    [ -d "$top/.bin/" ] || mkdir -p "$top/.bin/"
#    cd "$top/.bin" || exit "$ERR_ENV"
#    "$php" "$top/docker/bin/composer-installer.php"
#    if [ ! -f composer.phar ]; then
#      echo "Composer installer failed" 1>&2
#      exit $ERR_ENV
#    fi
#    mv "$top/.bin/composer.phar" "$composer"
#    chmod +x "$composer"
#  fi
#fi
cd "$top" || exit "$ERR_ENV"
#if ! "$composer" install -q; then
#  echo "Composer install failed" 1>&2
#  exit "$ERR_BUILD"
#fi
docker run
$dc build --no-cache --pull
env > "$envFile"
