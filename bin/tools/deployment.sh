#!/usr/bin/env bash
#
# deployment.sh
#
# Zesk PHP deployment tools
#
# Copyright &copy; 2025 Market Acumen, Inc.

#
# Prepare for a release by running all of our cleaning and build steps
#
zeskPrepareRelease() {
  local handler="_${FUNCNAME[0]}"

  local forceWrite=false

  # _IDENTICAL_ argumentNonBlankLoopHandler 6
  local __saved=("$@") __count=$#
  while [ $# -gt 0 ]; do
    local argument="$1" __index=$((__count - $# + 1))
    # __IDENTICAL__ __checkBlankArgumentHandler 1
    [ -n "$argument" ] || __throwArgument "$handler" "blank #$__index/$__count ($(decorate each quote "${__saved[@]}"))" || return $?
    case "$argument" in
    # _IDENTICAL_ helpHandler 1
    --help) "$handler" 0 && return $? || return $? ;;
    --force)
      forceWrite=true
      ;;
    *)
      # _IDENTICAL_ argumentUnknownHandler 1
      __throwArgument "$handler" "unknown #$__index/$__count \"$argument\" ($(decorate each code "${__saved[@]}"))" || return $?
      ;;
    esac
    # _IDENTICAL_ argument-esac-shift 1
    shift
  done

  local yml home

  home=$(__catch "$handler" buildHome) || return $?
  yml="$home/docker-compose.yml"

  [ -f "$yml" ] || __throwEnvironment "$handler" "Requires $(decorate file "$yml")" || return $?

  local envs=(VERSION_URL)

  export "${envs[@]}"
  # WAS ZESK_REPOSITORY_VERSION_PREFIX
  __catch "$handler" buildEnvironmentLoad "${envs[@]}" || return $?

  if [ -z "${VERSION_URL-}" ]; then
    local owner
    owner=$(__catch "$handler" buildEnvironmentGet GITHUB_REPOSITORY_OWNER) || return $?
    [ -n "$owner" ] || __throwEnvironment "$handler" "GITHUB_REPOSITORY_OWNER is blank" || return $?
    local name
    name=$(__catch "$handler" buildEnvironmentGet GITHUB_REPOSITORY_NAME) || return $?
    [ -n "$name" ] || __throwEnvironment "$handler" "GITHUB_REPOSITORY_NAME is blank" || return $?

    VERSION_URL="https://github.com/$owner/$name/compare/{previous}...{current}"
  fi

  __catchEnvironment "$handler" dockerComposeInstall || return $?

  __catchEnvironment "$handler" dockerCompose up -d >/dev/null || return $?

  currentVersion=$(__catchEnvironment "$handler" hookVersionCurrent) || return $?
  previousVersion=$(__catchEnvironment "$handler" gitVersionLast "$currentVersion") || return $?

  #
  # Sync with remote
  #
  statusMessage decorate info "Synchronizing with git remote ..."
  __catchEnvironment "$handler" muzzle git pull --tags || return $?
  __catchEnvironment "$handler" muzzle git push --tags || return $?

  statusMessage decorate info "Cleaning ..."
  __catchEnvironment "$handler" docker-compose -f "$yml" exec php bash -c 'cd /zesk && ./bin/cs-zesk.sh' || return $?

  local nLines
  #
  # Make sure repository state seems sane
  #
  nLines=$(($(git status --short | wc -l | awk '{ print $1 }') + 0))
  if [ $nLines -gt 0 ]; then
    __catchEnvironment "$handler" git status --short || return $?
    pause "Current git status, ok?"
  else
    statusMessage decorate info "Git status is clean."
  fi

  currentChangeLog="$(releaseNotes)"
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
    muzzle __catchEnvironment "$handler" git add "$currentChangeLog" || return $?
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

  __catchEnvironment "$handler" mv "$editedLog" "$permanentChangeLog" || return $?
  releaseDateFile="$home/etc/db/release-date"
  __catchEnvironment "$handler" date >"$releaseDateFile" || return $?

  __catchEnvironment "$handler" git commit -m "Release $currentVersion" "$currentChangeLog" "$permanentChangeLog" "$releaseDateFile" || return $?

  #
  # Push to remote
  #
  __catchEnvironment "$handler" git push --tags || return $?
  __catchEnvironment "$handler" git push --all || return $?
  #
  # Good
  #
  echo "##################################"
  echo "zesk $currentVersion prepared"
  echo "##################################"

}
_zeskPrepareRelease() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}
