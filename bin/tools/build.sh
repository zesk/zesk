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
  local handler="_${FUNCNAME[0]}"

  local startSetup quietLog cleanFlag=false versionArgs=()

  startSetup=$(__catch "$handler" timingStart) || return $?

  #
  # --clean - Do a clean build
  # --develop - Development build, tag with a d-tag upon success
  #

  # _IDENTICAL_ argumentNonBlankLoopHandler 6
  local __saved=("$@") __count=$#
  while [ $# -gt 0 ]; do
    local argument="$1" __index=$((__count - $# + 1))
    # __IDENTICAL__ __checkBlankArgumentHandler 1
    [ -n "$argument" ] || __throwArgument "$handler" "blank #$__index/$__count ($(decorate each quote "${__saved[@]}"))" || return $?
    case "$argument" in
    # _IDENTICAL_ helpHandler 1
    --help) "$handler" 0 && return $? || return $? ;;
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

  local home
  home=$(__catch "$handler" buildHome) || return $?

  #
  # Check shell files
  #
  find "$home" -type f -name '*.sh' ! -path '*/bin/build/*' ! -path '*/vendor/*' ! -path '*/DEPRECATED/*' | __catchEnvironment "$handler" bashLintFiles || return $?

  #==========================================================================================
  #
  # Validate our needed environment
  #

  local requireEnvironment=(
    DEPLOYMENT
    GITHUB_REPOSITORY_OWNER GITHUB_REPOSITORY_NAME
    DATABASE_PORT DATABASE_HOST CONTAINER_DATABASE_HOST
  )

  quietLog=$(__catch "$handler" buildQuietLog "${FUNCNAME[0]}") || return $?

  export "${requireEnvironment[@]}"

  __catch "$handler" buildEnvironmentLoad "${requireEnvironment[@]}" || return $?

  #==========================================================================================
  #
  # Validate our default environment
  #

  export DATABASE_ROOT_PASSWORD DATABASE_HOST PHP_IDE_CONFIG XDEBUG_CLIENT_HOST
  if [ -z "${DATABASE_ROOT_PASSWORD-}" ]; then
    local variable
    for variable in DATABASE_ROOT_PASSWORD MARIADB_ROOT_PASSWORD MYSQL_ROOT_PASSWORD; do
      DATABASE_ROOT_PASSWORD=$(bitbucketGetVariable "$variable" 2>/dev/null) || :
      [ -z "$DATABASE_ROOT_PASSWORD" ] || break
    done
    [ -n "$DATABASE_ROOT_PASSWORD" ] || __throwEnvironment "$handler" "No DATABASE_ROOT_PASSWORD defined in environment or bitbucket-pipelines.yml" || return $?
  fi

  [ -n "${DATABASE_HOST-}" ] || DATABASE_HOST="localhost"
  requireEnvironment+=(DATABASE_HOST)

  [ -n "${PHP_IDE_CONFIG-}" ] || PHP_IDE_CONFIG="serverName=zesk"
  requireEnvironment+=(PHP_IDE_CONFIG)

  [ -n "${XDEBUG_CLIENT_HOST-}" ] || XDEBUG_CLIENT_HOST="$CONTAINER_DATABASE_HOST"
  requireEnvironment+=(XDEBUG_CLIENT_HOST)

  [ -n "${DATABASE_NETWORK-}" ] || DATABASE_NETWORK="%"
  requireEnvironment+=(DATABASE_NETWORK)

  usageRequireEnvironment "$handler" "${requireEnvironment[@]}" || return $?

  statusMessage decorate info "Started on $(date)"

  #==========================================================================================
  #
  # Install
  #
  local home
  whichExists php || __catchEnvironment "$handler" phpInstall || return $?
  whichExists mysql || __catchEnvironment "$handler" mariadbInstall || return $?
  home=$(__catch "$handler" buildHome) || return $?

  # Set up dependencies so hooks work (environmentFileApplicationMake calls hooks)
  start=$(__catch "$handler" timingStart) || return $?
  statusMessage decorate info "Composer ... "
  __catchEnvironment "$handler" phpComposer "$home" || return $?
  statusMessage timingReport "$start" "composer completed in"

  local dotEnv="$home/.env" clean=()

  #
  # Connect to the database and set up test schema
  #
  start=$(__catch "$handler" timingStart) || returnClean $? "${clean[@]}" || return $?
  local rootDSN=("mysqli://root:$DATABASE_ROOT_PASSWORD@$DATABASE_HOST:$DATABASE_PORT/mysql")

  #==========================================================================================
  #
  # Load the test schema into our database
  #
  statusMessage decorate info "Loading schema ... "
  start=$(__catch "$handler" timingStart) || return $?

  # Writing .env file
  statusMessage decorate info "Generating environment artifact ... "
  clean+=("$dotEnv")
  __catch "$handler" environmentFileApplicationMake "${requireEnvironment[@]}" -- GITHUB_ACCESS_TOKEN | __catchEnvironment "$handler" muzzle tee "$dotEnv" || returnClean $? "${clean[@]}" || return $?
  environmentFileShow || :

  local randomPassword
  randomPassword="$(randomString)"
  randomPassword=${randomPassword:0:12}
  export DSN="mysqli://testUser:$randomPassword@$DATABASE_HOST/testDatabase"
  {
    __catch "$handler" environmentValueWrite DSN "$DSN" || return $?
    __catch "$handler" urlParse --prefix "DSN_" --uppercase "$DSN" >>"$dotEnv" || return $?
    __catch "$handler" environmentValueWrite DATABASE_HOST "$CONTAINER_DATABASE_HOST" || return $?
    __catch "$handler" environmentValueWrite "DATABASE_NETWORK" "%" || return $?
    for variable in "${requireEnvironment[@]}"; do
      __catch "$handler" environmentValueWrite "$variable" "${!variable}" || return $?
    done
  } >>"$dotEnv" || returnClean $? "${clean[@]}" || return $?

  dumpPipe ".env:" <"$dotEnv"
  environmentFileLoad "$dotEnv" --execute mapEnvironment <./etc/docker/schema.sql | dumpPipe "Schema:" || :

  __catch "$handler" environmentFileLoad "$dotEnv" --execute mapEnvironment <./etc/docker/schema.sql | __catchEnvironment "$handler" mariadbConnect "${rootDSN[@]}" >>"$quietLog" || buildFailed "$quietLog" || returnClean $? "${clean[@]}" || return $?
  statusMessage timingReport "$start" "Schema load completed in"

  #==========================================================================================
  #
  # Install vendor directory
  #
  if $cleanFlag; then
    if [ -d "./vendor" ]; then
      statusMessage decorate warning "Deleting ./vendor ..."
      __catchEnvironment "$handler" rm -rf "./vendor" || returnClean $? "${clean[@]}" || return $?
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
  __catchEnvironment "$handler" muzzle pushd "$home/etc/docker" || returnClean $? "${clean[@]}" || returnUndo $? "${undo[@]+"${undo[@]}"}" || return $?
  undo+=(muzzle popd --)
  __catchEnvironment "$handler" docker build -f web.Dockerfile --tag zesk:latest "${cleanArgs[@]}" ../.. || returnClean $? "${clean[@]}" || returnUndo $? "${undo[@]+"${undo[@]}"}" || return $?
  returnUndo 0 "${undo[@]+"${undo[@]}"}" || return $?

  #==========================================================================================
  #  ▀▛▘     ▐
  #   ▌▞▀▖▞▀▘▜▀
  #   ▌▛▀ ▝▀▖▐ ▖
  #   ▘▝▀▘▀▀  ▀
  start=$(__catch "$handler" timingStart) || return $?
  clearLine
  statusMessage decorate info Testing ...
  for d in "test-results" ".zesk-coverage" "test-coverage" ".phpunit-cache"; do
    [ -d "$d" ] || __catchEnvironment "$handler" mkdir -p "$d" || returnClean $? "${clean[@]}" || return $?
  done
  __catchEnvironment "$handler" docker run -v "$(pwd)/:/zesk" zesk:latest /zesk/bin/test.sh "$@" || returnClean $? "${clean[@]}" || return $?
  statusMessage timingReport "$start" "test.sh completed in"

  __catchEnvironment "$handler" rm -f "${clean[@]}" || return $?

  statusMessage decorate info "Tagging ..."
  __catchEnvironment "$handler" gitTagVersion "${versionArgs[@]}" || return $?
  statusMessage --last timingReport "$startSetup" "Setup complete."
}
_zeskPipelineSetup() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
