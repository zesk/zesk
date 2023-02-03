#!/usr/bin/env bash
ERR_ENV=1

top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." || exit $ERR_ENV; pwd)"
set -eo pipefail

envFile="$top/.env"
if [ ! -f "$envFile" ]; then
  echo "Missing $envFile" 1>&2
  exit $ERR_ENV
fi
docker=$(which docker)
if [ -z "$php" ]; then
  echo "No docker found in $PATH" 1>&2
  exit $ERR_ENV
fi

# shellcheck source=./.env
source "$envFile"
"$docker" compose exec php /zesk/bin/test-zesk.sh --coverage
