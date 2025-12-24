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
    [ -n "$argument" ] || throwArgument "$handler" "blank #$__index/$__count ($(decorate each quote -- "${__saved[@]}"))" || return $?
    case "$argument" in
      # _IDENTICAL_ helpHandler 1
      --help) "$handler" 0 && return $? || return $? ;;
      --force)
        forceWrite=true
        ;;
      *)
        # _IDENTICAL_ argumentUnknownHandler 1
        throwArgument "$handler" "unknown #$__index/$__count \"$argument\" ($(decorate each code -- "${__saved[@]}"))" || return $?
        ;;
    esac
    # _IDENTICAL_ argument-esac-shift 1
    shift
  done

  local yml home

  home=$(catchReturn "$handler" buildHome) || return $?

  yml="$home/docker-compose.yml"

  [ -f "$yml" ] || throwEnvironment "$handler" "Requires $(decorate file "$yml")" || return $?

  local envs=(VERSION_URL)

  export "${envs[@]}"
  # WAS ZESK_REPOSITORY_VERSION_PREFIX
  catchReturn "$handler" buildEnvironmentLoad "${envs[@]}" || return $?

  if [ -z "${VERSION_URL-}" ]; then
    local owner
    owner=$(catchReturn "$handler" buildEnvironmentGet GITHUB_REPOSITORY_OWNER) || return $?
    [ -n "$owner" ] || throwEnvironment "$handler" "GITHUB_REPOSITORY_OWNER is blank" || return $?
    local name
    name=$(catchReturn "$handler" buildEnvironmentGet GITHUB_REPOSITORY_NAME) || return $?
    [ -n "$name" ] || throwEnvironment "$handler" "GITHUB_REPOSITORY_NAME is blank" || return $?

    VERSION_URL="https://github.com/$owner/$name/compare/{previous}...{current}"
  fi

  catchEnvironment "$handler" dockerComposeInstall || return $?

  catchEnvironment "$handler" dockerCompose up -d >/dev/null || return $?

  currentVersion=$(catchEnvironment "$handler" hookVersionCurrent) || return $?
  previousVersion=$(catchEnvironment "$handler" gitVersionLast "$currentVersion") || return $?

  #
  # Sync with remote
  #
  statusMessage decorate info "Synchronizing with git remote ..."
  catchEnvironment "$handler" muzzle git pull --tags || return $?
  catchEnvironment "$handler" muzzle git push --tags || return $?

  statusMessage decorate info "Cleaning ..."
  catchEnvironment "$handler" dockerComposeWrapper -f "$yml" exec web bash -c 'cd /var/www/app && ./vendor/bin/php-cs-fixer fix' || return $?

  statusMessage decorate info "Composer update ..."
  catchEnvironment "$handler" dockerComposeWrapper -f "$yml" exec web bash -c 'cd /var/www/app && bin/tools.sh phpComposer . update' || return $?
  catchEnvironment "$handler" dockerComposeWrapper -f "$yml" exec web bash -c 'cd /var/www/app && composer update' || return $?

  local nLines
  #
  # Make sure repository state seems sane
  #
  nLines=$(($(git status --short | wc -l | awk '{ print $1 }') + 0))
  if [ $nLines -gt 0 ]; then
    catchEnvironment "$handler" git status --short || return $?
    if ! confirmYesNo --timeout 30 "Current git status, ok?"; then
      return "$(returnCode exit)"
    fi
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
    muzzle catchEnvironment "$handler" git add "$currentChangeLog" || return $?
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

  catchEnvironment "$handler" mv "$editedLog" "$permanentChangeLog" || return $?
  releaseDateFile="$home/etc/db/release-date"
  catchEnvironment "$handler" date >"$releaseDateFile" || return $?

  catchEnvironment "$handler" git commit -m "Release $currentVersion" "$currentChangeLog" "$permanentChangeLog" "$releaseDateFile" || return $?

  #
  # Push to remote
  #
  catchEnvironment "$handler" git push --tags || return $?
  catchEnvironment "$handler" git push --all || return $?
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
