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
figlet=$(which figlet)

set -eo pipefail

envFile="$top/.env"
quietLog=$top/output.log

if [ -z "$docker" ]; then
  echo "No docker found in $PATH" 1>&2
  exit $ERR_ENV
fi
if [ -z "$figlet" ]; then
  echo "Updating apt-get ..."
  apt-get update > $quietLog
  echo "Updating apt-get ..."
  apt-get install -y apt-utils figlet > "$quietLog" 2>&1
fi
if [ -z "$dc" ]; then
  echo "Upgrading pip ..."
  pip install --upgrade pip > "$quietLog" 2>&1
  echo "Installing docker-compose ..."
  pip install docker-compose > "$quietLog" 2>&1
  if ! which docker-compose > /dev/null; then
    echo "docker-compose not found after install" 1>&2
    exit $ERR_ENV
  fi
fi
figlet Building vendor
docker run -v "$(pwd):/app" composer:latest i --ignore-platform-req=ext-calendar > "$quietLog"

figlet Building containers
$dc build --no-cache --pull > "$quietLog"

figlet Testing
$dc exec php /zesk/bin/test-zesk.sh --coverage
