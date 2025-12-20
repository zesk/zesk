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

  startSetup=$(catchReturn "$handler" timingStart) || return $?

  #
  # --clean - Do a clean build
  # --develop - Development build, tag with a d-tag upon success
  #

  # _IDENTICAL_ argumentNonBlankLoopHandler 6
  local __saved=("$@") __count=$#
  while [ $# -gt 0 ]; do
    local argument="$1" __index=$((__count - $# + 1))
    # __IDENTICAL__ __checkBlankArgumentHandler 1
    [ -n "$argument" ] || throwArgument "$handler" "blank #$__index/$__count ($(decorate each quote -- "${__saved[@]}"))" || return $?
    case "$argument" in
      # _IDENTICAL_ helpHandler 1
      --help) "$handler" 0 && return $? || return $? ;;
      --clean)
        cleanFlag=true
        statusMessage decorate info "Clean install ..."
        shift
        ;;
      --staging)
        versionArgs+=("--suffix d")
        export BUILD_VERSION_SUFFIX="d"
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
  home=$(catchReturn "$handler" buildHome) || return $?

  #
  # Check shell files
  #
  find "$home" -type f -name '*.sh' ! -path '*/bin/build/*' ! -path '*/vendor/*' ! -path '*/DEPRECATED/*' | catchEnvironment "$handler" bashLintFiles || return $?

  #==========================================================================================
  #
  # Validate our needed environment
  #

  local requireEnvironment=(
    DEPLOYMENT
    GITHUB_REPOSITORY_OWNER GITHUB_REPOSITORY_NAME
    DATABASE_PORT DATABASE_HOST CONTAINER_DATABASE_HOST
  )

  quietLog=$(catchReturn "$handler" buildQuietLog "${FUNCNAME[0]}") || return $?

  export "${requireEnvironment[@]}"

  catchReturn "$handler" buildEnvironmentLoad "${requireEnvironment[@]}" || return $?

  #==========================================================================================
  #
  # Validate our default environment
  #

  export DATABASE_ROOT_PASSWORD DATABASE_HOST PHP_IDE_CONFIG XDEBUG_CLIENT_HOST DATABASE_PORT
  if [ -z "${DATABASE_ROOT_PASSWORD-}" ]; then
    local variable value
    for variable in MARIADB_ROOT_PASSWORD MYSQL_ROOT_PASSWORD; do
      value="${!variable-}"
      [ -z "$value" ] || break
      value=$(bitbucketGetVariable "$variable" 2>/dev/null) || :
      [ -z "$value" ] || break
    done
    [ -n "$value" ] || throwEnvironment "$handler" "No DATABASE_ROOT_PASSWORD defined in environment or bitbucket-pipelines.yml" || return $?
    DATABASE_ROOT_PASSWORD="$value"

    # Evaluate variables found in bitbucket
    if [ "${DATABASE_ROOT_PASSWORD:0:1}" = '$' ]; then
      local varName=${DATABASE_ROOT_PASSWORD:1}
      DATABASE_ROOT_PASSWORD="${!varName-}"
      if [ -z "$DATABASE_ROOT_PASSWORD" ]; then
        throwEnvironment "$handler" "DATABASE_ROOT_PASSWORD value of $varName does not evaluate to any value, stopping" || return $?
      fi
    fi
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
  whichExists php || catchEnvironment "$handler" phpInstall || return $?
  whichExists mysql || catchEnvironment "$handler" mariadbInstall || return $?
  home=$(catchReturn "$handler" buildHome) || return $?

  local dd=()
  # If docker exists, use it for composer
  ! whichExists docker || dd+=(--docker)
  # Set up dependencies so hooks work (environmentFileApplicationMake calls hooks)
  start=$(catchReturn "$handler" timingStart) || return $?
  statusMessage decorate info "Composer ... "
  catchEnvironment "$handler" phpComposer "${dd[@]+"${dd[@]}"}" "$home" || return $?
  statusMessage timingReport "$start" "composer completed in"

  local dotEnv="$home/.env" clean=()

  #
  # Connect to the database and set up test schema
  #
  start=$(catchReturn "$handler" timingStart) || returnClean $? "${clean[@]}" || return $?

  DATABASE_PORT="${DATABASE_PORT-3306}"
  local rootDSN=("mysqli://root:$DATABASE_ROOT_PASSWORD@$DATABASE_HOST:$DATABASE_PORT/mysql")

  #==========================================================================================
  #
  # Load the test schema into our database
  #
  statusMessage decorate info "Loading schema ... "
  start=$(catchReturn "$handler" timingStart) || return $?

  # Writing .env file
  statusMessage decorate info "Generating environment artifact ... "
  clean+=("$dotEnv")
  catchReturn "$handler" environmentFileApplicationMake -- GITHUB_ACCESS_TOKEN | catchEnvironment "$handler" muzzle tee "$dotEnv" || returnClean $? "${clean[@]}" || return $?
  environmentFileShow || :

  local randomPassword
  randomPassword="$(randomString)"
  randomPassword=${randomPassword:0:12}
  export DSN="mysqli://testUser:$randomPassword@$DATABASE_HOST/testDatabase"
  {
    catchReturn "$handler" environmentValueWrite DSN "$DSN" || return $?
    catchReturn "$handler" urlParse --prefix "DSN_" --uppercase "$DSN" >>"$dotEnv" || return $?
    catchReturn "$handler" environmentValueWrite "DATABASE_NETWORK" "%" || return $?
    for variable in "${requireEnvironment[@]}"; do
      catchReturn "$handler" environmentValueWrite "$variable" "${!variable}" || return $?
    done
  } >>"$dotEnv" || returnClean $? "${clean[@]}" || return $?

  dumpPipe ".env:" <"$dotEnv"
  environmentFileLoad "$dotEnv" --execute mapEnvironment <"$home/etc/docker/schema.sql" | dumpPipe "Schema:" || :

  catchReturn "$handler" environmentFileLoad "$dotEnv" --execute mapEnvironment <./etc/docker/schema.sql | catchEnvironment "$handler" mariadbConnect "${rootDSN[@]}" >>"$quietLog" || buildFailed "$quietLog" || returnClean $? "${clean[@]}" || return $?
  statusMessage timingReport "$start" "Schema load completed in"

  #==========================================================================================
  #
  # Install vendor directory
  #
  if $cleanFlag; then
    if [ -d "./vendor" ]; then
      statusMessage decorate warning "Deleting ./vendor ..."
      catchEnvironment "$handler" rm -rf "./vendor" || returnClean $? "${clean[@]}" || return $?
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
  catchEnvironment "$handler" muzzle pushd "$home/etc/docker" || returnClean $? "${clean[@]}" || returnUndo $? "${undo[@]+"${undo[@]}"}" || return $?
  undo+=(muzzle popd --)
  DOCKER_BUILDKIT=1 catchEnvironment "$handler" docker build -f web.Dockerfile --tag zesk:latest --build-arg BUILDKIT_INLINE_CACHE=1 "${cleanArgs[@]}" ../.. || returnClean $? "${clean[@]}" || returnUndo $? "${undo[@]+"${undo[@]}"}" || return $?
  returnUndo 0 "${undo[@]+"${undo[@]}"}" || return $?

  #==========================================================================================
  #  ▀▛▘     ▐
  #   ▌▞▀▖▞▀▘▜▀
  #   ▌▛▀ ▝▀▖▐ ▖
  #   ▘▝▀▘▀▀  ▀
  start=$(catchReturn "$handler" timingStart) || return $?
  clearLine
  statusMessage decorate info Testing ...
  for d in "test-results" ".zesk-coverage" "test-coverage" ".phpunit-cache"; do
    [ -d "$d" ] || catchEnvironment "$handler" mkdir -p "$d" || returnClean $? "${clean[@]}" || return $?
  done
  local ee=()
  ee+=(--env "DATABASE_ROOT_PASSWORD=$DATABASE_ROOT_PASSWORD")
  ee+=(--env "DATABASE_HOST=$CONTAINER_DATABASE_HOST")

  catchEnvironment "$handler" executeEcho docker run -v "$(pwd)/:/zesk" zesk:latest /zesk/bin/test.sh "${ee[@]}" "$@" || returnClean $? "${clean[@]}" || return $?
  statusMessage timingReport "$start" "test.sh completed in"

  catchEnvironment "$handler" rm -f "${clean[@]}" || return $?

  statusMessage decorate info "Tagging ..."
  catchEnvironment "$handler" gitTagVersion "${versionArgs[@]}" || return $?
  statusMessage --last timingReport "$startSetup" "Setup complete."
}
_zeskPipelineSetup() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
