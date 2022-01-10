#!/bin/bash

top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd || exit)"

export DOCKER_BUILDKIT=0
export COMPOSE_DOCKER_CLI_BUILD=0

cd "$top"
docker compose build
docker compose down
docker volume rm zesk_database_data 2> /dev/null
docker compose up -d
docker compose exec php /zesk/bin/test-zesk.sh "$@"
