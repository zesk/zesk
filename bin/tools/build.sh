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

  local startSetup quietLog cleanFlag=false versionArgs=()

  startSetup=$(__catchEnvironment "$usage" timingStart) || return $?

  #
  # --clean - Do a clean build
  # --develop - Development build, tag with a d-tag upon success
  #

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
      cleanFlag=true
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
  # Check shell files
  #
  find "$home" -type f -name '*.sh' ! -path '*/bin/build/*' ! -path '*/vendor/*' ! -path '*/DEPRECATED/*' | __catchEnvironment "$usage" bashLintFiles || return $?

  #==========================================================================================
  #
  # Validate our needed environment
  #

  local requireEnvironment=(
    DEPLOYMENT
    GITHUB_REPOSITORY_OWNER GITHUB_REPOSITORY_NAME
    DATABASE_PORT DATABASE_HOST CONTAINER_DATABASE_HOST
  )

  quietLog=$(__catchEnvironment "$usage" buildQuietLog "${FUNCNAME[0]}") || return $?

  export "${requireEnvironment[@]}"

  __catchEnvironment "$usage" buildEnvironmentLoad "${requireEnvironment[@]}" || return $?

  #==========================================================================================
  #
  # Validate our default environment
  #

  export DATABASE_ROOT_PASSWORD DATABASE_HOST PHP_IDE_CONFIG XDEBUG_CLIENT_HOST
  if [ -z "${DATABASE_ROOT_PASSWORD-}" ]; then
    local variable
    for variable in DATABASE_ROOT_PASSWORD MARIADB_ROOT_PASSWORD MYSQL_ROOT_PASSWORD; do
      DATABASE_ROOT_PASSWORD=$(getFromPipelineYML "$variable" 2>/dev/null) || :
      [ -z "$DATABASE_ROOT_PASSWORD" ] || break
    done
    [ -n "$DATABASE_ROOT_PASSWORD" ] || __throwEnvironment "$usage" "No DATABASE_ROOT_PASSWORD defined in environment or bitbucket-pipelines.yml" || return $?
  fi

  [ -n "${DATABASE_HOST-}" ] || DATABASE_HOST="localhost"
  requireEnvironment+=(DATABASE_HOST)

  [ -n "${PHP_IDE_CONFIG-}" ] || PHP_IDE_CONFIG="serverName=zesk"
  requireEnvironment+=(PHP_IDE_CONFIG)

  [ -n "${XDEBUG_CLIENT_HOST-}" ] || XDEBUG_CLIENT_HOST="$CONTAINER_DATABASE_HOST"
  requireEnvironment+=(XDEBUG_CLIENT_HOST)

  [ -n "${DATABASE_NETWORK-}" ] || DATABASE_NETWORK="%"
  requireEnvironment+=(DATABASE_NETWORK)

  usageRequireEnvironment "$usage" "${requireEnvironment[@]}" || return $?

  statusMessage decorate info "Started on $(date)"

  #==========================================================================================
  #
  # Install
  #
  local home
  whichExists php || __catchEnvironment "$usage" phpInstall || return $?
  whichExists mysql || __catchEnvironment "$usage" mariadbInstall || return $?
  home=$(__catchEnvironment "$usage" buildHome) || return $?

  # Set up dependencies so hooks work (environmentFileApplicationMake calls hooks)
  start=$(__catchEnvironment "$usage" timingStart) || return $?
  statusMessage decorate info "Composer ... "
  __catchEnvironment "$usage" phpComposer "$home" || return $?
  statusMessage timingReport "$start" "composer completed in"

  local dotEnv="$home/.env" clean=()

  #
  # Connect to the database and set up test schema
  #
  start=$(__catchEnvironment "$usage" timingStart) || _clean $? "${clean[@]}" || return $?
  local rootDSN=("mysqli://root:$DATABASE_ROOT_PASSWORD@$DATABASE_HOST:$DATABASE_PORT/mysql")

  #==========================================================================================
  #
  # Load the test schema into our database
  #
  statusMessage decorate info "Loading schema ... "
  start=$(__catchEnvironment "$usage" timingStart) || return $?

  # Writing .env file
  statusMessage decorate info "Generating environment artifact ... "
  clean+=("$dotEnv")
  __catchEnvironment "$usage" environmentFileApplicationMake "${requireEnvironment[@]}" -- GITHUB_ACCESS_TOKEN | __catchEnvironment "$usage" muzzle tee "$dotEnv" || _clean $? "${clean[@]}" || return $?
  environmentFileShow || :

  local randomPassword
  randomPassword="$(randomString)"
  randomPassword=${randomPassword:0:12}
  export DSN="mysqli://testUser:$randomPassword@$DATABASE_HOST/testDatabase"
  {
    __catchEnvironment "$usage" environmentValueWrite DSN "$DSN" || return $?
    __catchEnvironment "$usage" urlParse --prefix "DSN_" --uppercase "$DSN" >>"$dotEnv" || return $?
    __catchEnvironment "$usage" environmentValueWrite DATABASE_HOST "$CONTAINER_DATABASE_HOST" || return $?
    __catchEnvironment "$usage" environmentValueWrite "DATABASE_NETWORK" "%" || return $?
    for variable in "${requireEnvironment[@]}"; do
      __catchEnvironment "$usage" environmentValueWrite "$variable" "${!variable}" || return $?
    done
  } >>"$dotEnv" || _clean $? "${clean[@]}" || return $?

  dumpPipe ".env:" <"$dotEnv"
  environmentFileLoad "$dotEnv" --execute mapEnvironment <./etc/docker/schema.sql | dumpPipe "Schema:" || :

  __catchEnvironment "$usage" environmentFileLoad "$dotEnv" --execute mapEnvironment <./etc/docker/schema.sql | __catchEnvironment "$usage" mariadbConnect "${rootDSN[@]}" >>"$quietLog" || buildFailed "$quietLog" || _clean $? "${clean[@]}" || return $?
  statusMessage timingReport "$start" "Schema load completed in"

  #==========================================================================================
  #
  # Install vendor directory
  #
  if $cleanFlag; then
    if [ -d "./vendor" ]; then
      statusMessage decorate warning "Deleting ./vendor ..."
      __catchEnvironment "$usage" rm -rf "./vendor" || _clean $? "${clean[@]}" || return $?
    fi
  fi
  #==========================================================================================
  #
  # Build the Zesk docker image
  #
  cleanArgs=()
  if $cleanFlag; then
    cleanArgs=("--no-cache" "--pull")
  fi
  local undo=()
  __catchEnvironment "$usage" muzzle pushd "$home/etc/docker" || _clean $? "${clean[@]}" || _undo $? "${undo[@]+"${undo[@]}"}" || return $?
  undo+=(muzzle popd --)
  __catchEnvironment "$usage" docker build -f web.Dockerfile --tag zesk:latest "${cleanArgs[@]}" ../.. || _clean $? "${clean[@]}" || _undo $? "${undo[@]+"${undo[@]}"}" || return $?
  _undo 0 "${undo[@]+"${undo[@]}"}" || return $?

  #==========================================================================================
  #  ▀▛▘     ▐
  #   ▌▞▀▖▞▀▘▜▀
  #   ▌▛▀ ▝▀▖▐ ▖
  #   ▘▝▀▘▀▀  ▀
  start=$(__catchEnvironment "$usage" timingStart) || return $?
  clearLine
  statusMessage decorate info Testing ...
  for d in "test-results" ".zesk-coverage" "test-coverage" ".phpunit-cache"; do
    [ -d "$d" ] || __catchEnvironment "$usage" mkdir -p "$d" || _clean $? "${clean[@]}" || return $?
  done
  __catchEnvironment "$usage" docker run -v "$(pwd)/:/zesk" zesk:latest /zesk/bin/test.sh "$@" || _clean $? "${clean[@]}" || return $?
  statusMessage timingReport "$start" "test.sh completed in"

  __catchEnvironment "$usage" rm -f "${clean[@]}" || return $?

  statusMessage decorate info "Tagging ..."
  __catchEnvironment "$usage" gitTagVersion "${versionArgs[@]}" || return $?
  statusMessage --last timingReport "$startSetup" "Setup complete."
}
_zeskPipelineSetup() {
  # _IDENTICAL_ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
