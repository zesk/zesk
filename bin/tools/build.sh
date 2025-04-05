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

  local startSetup quietLog clean versionArgs
  local requireEnvironment=(
    DEPLOYMENT
    GITHUB_REPOSITORY_OWNER GITHUB_REPOSITORY_NAME
    DATABASE_PORT DATABASE_HOST CONTAINER_DATABASE_HOST
  )
  # Will be exported to the environment file
  local buildEnvironment=("${requireEnvironment[@]}")

  startSetup=$(__catchEnvironment "$usage" timingStart) || return $?

  quietLog=$(__catchEnvironment "$usage" buildQuietLog "${FUNCNAME[0]}") || return $?

  export "${requireEnvironment[@]}" "${buildEnvironment[@]}"

  __catchEnvironment "$usage" buildEnvironmentLoad "${requireEnvironment[@]}" || return $?

  #
  # --clean - Do a clean build
  # --develop - Development build, tag with a d-tag upon success
  #
  clean=
  versionArgs=()
  # _IDENTICAL_ argument-case-header 5
  local __saved=("$@") __count=$#
  while [ $# -gt 0 ]; do
    local argument="$1" __index=$((__count - $# + 1))
    [ -n "$argument" ] || __throwArgument "$usage" "blank #$__index/$__count ($(decorate each quote "${__saved[@]}"))" || return $?
    case "$argument" in
      # _IDENTICAL_ --help 4
      --help)
        "$usage" 0
        return $?
        ;;
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
    # _IDENTICAL_ argument-esac-shift 1
    shift
  done

  #
  # Connect to the database and set up test schema
  #
  export DATABASE_ROOT_PASSWORD DATABASE_HOST
  if [ -z "${DATABASE_ROOT_PASSWORD-}" ]; then
    local variable
    for variable in DATABASE_ROOT_PASSWORD MARIADB_ROOT_PASSWORD MYSQL_ROOT_PASSWORD; do
      DATABASE_ROOT_PASSWORD=$(getFromPipelineYML "$variable" 2>/dev/null) || :
      [ -z "$DATABASE_ROOT_PASSWORD" ] || break
    done
    [ -n "$DATABASE_ROOT_PASSWORD" ] || __throwEnvironment "$usage" "No DATABASE_ROOT_PASSWORD defined in environment or bitbucket-pipelines.yml" || return $?
  fi
  [ -n "${DATABASE_HOST-}" ] || DATABASE_HOST="localhost"

  usageRequireEnvironment "$usage" "${requireEnvironment[@]}" || return $?

  statusMessage decorate info "Started on $(date)"

  #==========================================================================================
  #
  # Install
  #
  local home
  __catchEnvironment "$usage" packageInstall || return $?
  __catchEnvironment "$usage" mariadbInstall || return $?
  home=$(__catchEnvironment "$usage" buildHome) || return $?

  find "$home" -type f -name '*.sh' ! -path '*/vendor/*' ! -path '*/DEPRECATED/*' | __catchEnvironment "$usage" bashLintFiles || return $?

  # Set up dependencies so hooks work (environmentFileApplicationMake calls hooks)
  start=$(__catchEnvironment "$usage" timingStart) || return $?
  statusMessage decorate info "Composer ... "
  __catchEnvironment "$usage" phpComposer "$home" || return $?
  statusMessage timingReport "$start" "composer completed in"

  statusMessage decorate info "Generating environment artifact ... " || :
  __catchEnvironment "$usage" environmentFileApplicationMake "${buildEnvironment[@]}" -- GITHUB_ACCESS_TOKEN | __catchEnvironment "$usage" muzzle tee "$home/.env" || return $?
  environmentFileShow || :

  start=$(__catchEnvironment "$usage" timingStart) || return $?
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
  statusMessage timingReport "$start" "Database setup completed in"

  #==========================================================================================
  #
  # Load the test schema into our database
  #
  statusMessage decorate info "Loading schema ... "
  start=$(__catchEnvironment "$usage" timingStart) || return $?
  __catchEnvironment "$usage" mariadb "${databaseArguments[@]}" <./docker/mariadb/schema.sql >>"$quietLog" || buildFailed "$quietLog" || return $?
  statusMessage timingReport "$start" "Schema load completed in"

  #==========================================================================================
  #
  # Install vendor directory
  #
  if test $clean; then
    if [ -d "./vendor" ]; then
      statusMessage decorate warning "Deleting ./vendor ..."
      __catchEnvironment "$usage" rm -rf "./vendor" || return $?
    fi
  fi
  #==========================================================================================
  #
  # Build the Zesk docker image
  #
  cleanArgs=()
  if test "$clean"; then
    cleanArgs=("--no-cache" "--pull")
  fi
  __catchEnvironment "$usage" zeskContainerLatest "$CONTAINER_DATABASE_HOST" "${cleanArgs[@]}" || return $?

  #==========================================================================================
  #  ▀▛▘     ▐
  #   ▌▞▀▖▞▀▘▜▀
  #   ▌▛▀ ▝▀▖▐ ▖
  #   ▘▝▀▘▀▀  ▀
  start=$(__catchEnvironment "$usage" timingStart) || return $?
  clearLine
  statusMessage decorate info Testing ...
  for d in "test-results" ".zesk-coverage" "test-coverage" ".phpunit-cache"; do
    [ -d "$d" ] || mkdir -p "$d"
  done
  __catchEnvironment "$usage" docker run -v "$(pwd)/:/zesk" zesk:latest /zesk/bin/test-zesk.sh "$@" || return $?
  statusMessage timingReport "$start" "zesk-test.sh completed in"

  statusMessage decorate info "Tagging ..."
  __catchEnvironment "$usage" gitTagVersion "${versionArgs[@]}" || return $?
  statusMessage timingReport "$startSetup" "Setup complete."
  printf -- "\n"
}
_zeskPipelineSetup() {
  # _IDENTICAL_ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
