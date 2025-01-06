#!/usr/bin/env bash
#
# pipeline-setup.sh
#
# Set up Zesk build
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

# fn: {base}
# Usage: {fn}
# Build and test Zesk, and tag release
# Argument: --clean - Optional. Do a clean build.
# Argument: --develop - Optional. Development build.
#
zeskPipelineSetup() {
  local usage="_${FUNCNAME[0]}"

  set -eou pipefail

  # shellcheck source=/dev/null
  source "${BASH_SOURCE[0]%/*}/tools.sh" || return 99

  local startSetup quietLog clean versionArgs
  local requireEnvironment=(
    DEPLOYMENT
    GITHUB_REPOSITORY_OWNER GITHUB_REPOSITORY_NAME
    DATABASE_PORT DATABASE_HOST DATABASE_ROOT_PASSWORD CONTAINER_DATABASE_HOST
  )
  # Will be exported to the environment file
  local buildEnvironment=("${requireEnvironment[@]}")

  startSetup=$(__usageEnvironment "$usage" beginTiming) || return $?

  quietLog=$(__usageEnvironment "$usage" buildQuietLog "${FUNCNAME[0]}") || return $?

  export "${requireEnvironment[@]}"

  usageRequireEnvironment "$usage" "${requireEnvironment[@]}" || return $?

  usageRequireBinary "$usage" docker || return $?

  #
  # --clean - Do a clean build
  # --develop - Development build, tag with a d-tag upon success
  #
  clean=
  versionArgs=()
  while [ $# -gt 0 ]; do
    case $1 in
      --clean)
        clean=1
        statusMessage decorate info "Clean install ..."
        shift
        ;;
      --staging)
        versionArgs+=("--staging")
        statusMessage decorate info "Staging ..."
        shift
        ;;
      *)
        break
        ;;
    esac
  done

  statusMessage decorate info "Started on $(date)"

  #==========================================================================================
  #
  # Install
  #
  local home
  __usageEnvironment "$usage" packageInstall || return $?
  __usageEnvironment "$usage" mariadbInstall || return $?
  home=$(__usageEnvironment "$usage" buildHome) || return $?

  find "$home" -type f -name '*.sh' ! -path '*/vendor/*' ! -path '*/DEPRECATED/*' | __usageEnvironment "$usage" bashLintFiles || return $?

  statusMessage decorate info "Generating environment artifact ... " || :

  __usageEnvironment "$usage" environmentFileApplicationMake "${buildEnvironment[@]}" -- GITHUB_ACCESS_TOKEN | __usageEnvironment "$usage" muzzle tee "$home/.env" || return $?
  environmentFileShow || :

  #==========================================================================================
  #
  # Test our build assumptions
  #
  start=$(__usageEnvironment "$usage" beginTiming) || return $?
  statusMessage decorate info "Build bin smoke tests ... "
  __usageEnvironment "$usage" ./bin/test-bin.sh || return $?
  statusMessage reportTiming "$start" "Smoke tests completed in"

  #
  # Connect to the database and set up test schema
  #
  start=$(__usageEnvironment "$usage" beginTiming) || return $?
  databaseArguments=("-u" "root" "-p$DATABASE_ROOT_PASSWORD" "-h" "$DATABASE_HOST" "--port" "$DATABASE_PORT")
  statusMessage decorate info "Setting up database ... "
  {
    figlet "Database"
    echoBar
    printf "%s" "COMMAND: mariadb"
    echo "${databaseArguments[@]}"
    echo "TODO TRY COMMAND:"
    echo docker run -t zesk:latest mariadb "${databaseArguments[@]}"
  } >>"$quietLog"
  statusMessage reportTiming "$start" "Database setup completed in"

  #==========================================================================================
  #
  # Load the test schema into our database
  #
  statusMessage decorate info "Loading schema ... "
  start=$(__usageEnvironment "$usage" beginTiming) || return $?
  __usageEnvironment "$usage" mariadb "${databaseArguments[@]}" <./docker/mariadb/schema.sql >>"$quietLog" || buildFailed "$quietLog" || return $?
  statusMessage reportTiming "$start" "Schema load completed in"

  #==========================================================================================
  #
  # Install vendor directory
  #
  if test $clean; then
    if [ -d "./vendor" ]; then
      statusMessage decorate warning "Deleting ./vendor ..."
      __usageEnvironment "$usage" rm -rf "./vendor" || return $?
    fi
  fi
  start=$(__usageEnvironment "$usage" beginTiming) || return $?
  statusMessage decorate info "Composer ... "
  __usageEnvironment "$usage" phpComposer "$home" || return $?
  statusMessage reportTiming "$start" "composer completed in"

  #==========================================================================================
  #
  # Build the Zesk docker image
  #
  cleanArgs=()
  if test "$clean"; then
    cleanArgs=("--no-cache" "--pull")
  fi
  __usageEnvironment "$usage" zeskContainerLatest "$CONTAINER_DATABASE_HOST" "${cleanArgs[@]}" || return $?

  #==========================================================================================
  #  ▀▛▘     ▐
  #   ▌▞▀▖▞▀▘▜▀
  #   ▌▛▀ ▝▀▖▐ ▖
  #   ▘▝▀▘▀▀  ▀
  start=$(__usageEnvironment "$usage" beginTiming) || return $?
  clearLine
  statusMessage decorate info Testing ...
  for d in "test-results" ".zesk-coverage" "test-coverage" ".phpunit-cache"; do
    [ -d "$d" ] || mkdir -p "$d"
  done
  __usageEnvironment "$usage" docker run -v "$(pwd)/:/zesk" zesk:latest /zesk/bin/test-zesk.sh "$@" || return $?
  statusMessage reportTiming "$start" "zesk-test.sh completed in"

  statusMessage decorate info "Tagging ..."
  __usageEnvironment "$usage" gitTagVersion "${versionArgs[@]}" || return $?
  statusMessage reportTiming "$startSetup" "Setup complete."
  printf -- "\n"
}
_zeskPipelineSetup() {
  # IDENTICAL usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

zeskPipelineSetup "$@"
