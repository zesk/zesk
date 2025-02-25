#!/usr/bin/env bash
#
# release-prepare.sh
#
# Prepare the repository for the next release
#
# Copyright &copy; 2025 Market Acumen, Inc.
#

set -eou pipefail

# shellcheck source=/dev/null
source "${BASH_SOURCE[0]%/*}/build/tools.sh" || exit 99

# Prepare repository for next release"
# Argument: --force - Force generation of release markdown
zeskPrepareRelease() {
  local usage="_${FUNCNAME[0]}"

  local forceWrite=false

  # IDENTICAL argument-case-header 5
  local saved=("$@") nArguments=$#
  while [ $# -gt 0 ]; do
    local argument argumentIndex=$((nArguments - $# + 1))
    argument="$(usageArgumentString "$usage" "argument #$argumentIndex (Arguments: $(_command "${usage#_}" "${saved[@]}"))" "$1")" || return $?
    case "$argument" in
      # IDENTICAL --help 4
      --help)
        "$usage" 0
        return $?
        ;;
      --force)
        forceWrite=true
        ;;
      *)
        # IDENTICAL argumentUnknown 1
        __throwArgument "$usage" "unknown argument #$argumentIndex: $argument (Arguments: $(_command "${saved[@]}"))" || return $?
        ;;
    esac
    # IDENTICAL argument-esac-shift 1
    shift || __throwArgument "$usage" "missing argument #$argumentIndex: $argument (Arguments: $(_command "${usage#_}" "${saved[@]}"))" || return $?
  done

  local yml home

  home=$(__catchEnvironment "$usage" buildHome) || return $?
  yml="$home/docker-compose.yml"

  [ -f "$yml" ] || __throwEnvironment "$usage" "Requires $(decorate file "$yml")" || return $?

  local envs=(VERSION_URL)

  export "${envs[@]}"
  # WAS ZESK_REPOSITORY_VERSION_PREFIX
  __catchEnvironment "$usage" buildEnvironmentLoad "${envs[@]}" || return $?

  if [ -z "${VERSION_URL-}" ]; then
    local owner
    owner=$(__catchEnvironment "$usage" buildEnvironmentGet GITHUB_REPOSITORY_OWNER) || return $?
    [ -n "$owner" ] || __throwEnvironment "$usage" "GITHUB_REPOSITORY_OWNER is blank" || return $?
    local name
    name=$(__catchEnvironment "$usage" buildEnvironmentGet GITHUB_REPOSITORY_NAME) || return $?
    [ -n "$name" ] || __throwEnvironment "$usage" "GITHUB_REPOSITORY_NAME is blank" || return $?

    VERSION_URL="https://github.com/$owner/$name/compare/{previous}...{current}"
  fi

  __catchEnvironment "$usage" dockerComposeInstall || return $?

  __catchEnvironment "$usage" docker-compose -f "$yml" up -d >/dev/null || return $?

  currentVersion=$(__catchEnvironment "$usage" hookVersionCurrent) || return $?
  previousVersion=$(__catchEnvironment "$usage" gitVersionLast "$currentVersion") || return $?

  #
  # Sync with remote
  #
  statusMessage decorate info "Synchronizing with git remote ..."
  __catchEnvironment "$usage" muzzle git pull --tags || return $?
  __catchEnvironment "$usage" muzzle git push --tags || return $?

  statusMessage decorate info "Cleaning ..."
  __catchEnvironment "$usage" docker-compose -f "$yml" exec php bash -c 'cd /zesk && ./bin/cs-zesk.sh' || return $?

  local nLines
  #
  # Make sure repository state seems sane
  #
  nLines=$(($(git status --short | wc -l | awk '{ print $1 }') + 0))
  if [ $nLines -gt 0 ]; then
    __catchEnvironment "$usage" git status --short || return $?
    pause "Current git status, ok?"
  else
    statusMessage decorate info "Git status is clean."
  fi

  local releaseDir

  releaseDir="$home/docs/release"

  __catchEnvironment "$usage" requireDirectory "$releaseDir" || return $?

  currentChangeLog="$releaseDir/$currentVersion.md"
  permanentChangeLog="$home/CHANGELOG.md"

  if [ ! -f "$currentChangeLog" ] || "$forceWrite"; then
    #
    # Edit release notes
    #
    {
      echo
      echo "## [$currentVersion][]"
      echo
      git log --pretty=format:'- %s' "^$previousVersion^{}" HEAD | sort -u
      echo
      echo '<!-- Generated automatically by release-prepare.sh, edit me please -->'
    } >"$currentChangeLog"
    muzzle __catchEnvironment "$usage" git add "$currentChangeLog" || return $?
    statusMessage decorate info "Added $(decorate file "$currentChangeLog") to repository - please edit"
  fi

  local moreLines editedLog

  moreLines=100000
  editedLog="$home/CHANGELOG.md.$$.edited"

  # CHANGELOG.md
  #
  # File structure with tokens in order:
  # RELEASE-HERE
  # currentVersion <- replaced up to next token
  # previousVersion
  # LINK-HERE
  # ...currentVersion  <- line replaced
  # rest of file

  {
    grep -B $moreLines RELEASE-HERE "$permanentChangeLog"
    grep -v "$previousVersion" "$currentChangeLog"
    echo
    grep -B $moreLines LINK-HERE "$permanentChangeLog" | grep -A $moreLines "$previousVersion"
    echo "[$currentVersion]: $ZESK_REPOSITORY_VERSION_PREFIX$previousVersion...$currentVersion"
    grep -A $moreLines LINK-HERE "$permanentChangeLog" | grep -v "$currentVersion" | grep -v LINK-HERE
  } >"$editedLog"

  __catchEnvironment "$usage" mv "$editedLog" "$permanentChangeLog" || return $?
  releaseDateFile="$home/etc/db/release-date"
  __catchEnvironment "$usage" date >"$releaseDateFile" || return $?

  __catchEnvironment "$usage" git commit -m "Release $currentVersion" "$currentChangeLog" "$permanentChangeLog" "$releaseDateFile" || return $?

  #
  # Push to remote
  #
  __catchEnvironment "$usage" git push --tags || return $?
  __catchEnvironment "$usage" git push --all || return $?
  #
  # Good
  #
  echo "##################################"
  echo "zesk $currentVersion prepared"
  echo "##################################"

}
_zeskPrepareRelease() {
  # IDENTICAL usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

zeskPrepareRelease "$@"
