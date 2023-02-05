#!/usr/bin/env bash
#
# pipeline-setup.sh
#
# Set up Zesk build
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
ERR_ENV=1

# Debug bash - set -x
me=$(basename "$0")
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." || exit $ERR_ENV; pwd)"
# Optional binaries in build image
docker=$(which docker)
envFile="$top/.env"
quietLog="$top/.build/$me.log"

set -eo pipefail

if [ -z "$docker" ]; then
  echo "No docker found in $PATH" 1>&2
  exit $ERR_ENV
fi

"$top/bin/build/apt-utils.sh"
"$top/bin/build/docker-compose.sh"

echo Install vendor
docker run -v "$(pwd):/app" composer:latest i --ignore-platform-req=ext-calendar >> "$quietLog" 2>&1

echo Build container ...
docker-compose build --no-cache --pull >> "$quietLog"

echo Running container ...
docker-compose up -d

figlet Testing
set -x
docker-compose exec php /zesk/bin/test-zesk.sh --coverage

"$top/bin/release-check-version.sh"

echo Stopping container ...
docker-compose down

env > "$envFile"
