#!/usr/bin/env bash
#
# Install components of our operating system
#
# Copyright &copy; 2025, Market Acumen, Inc.
#

# Run apt commands non-interactively
__aptWrapper() {
  DEBIAN_FRONTEND=non-interactive apt-get "$@"
}

# Install our base packages required for basic operations
__installBase() {
  # Debian package names
  packageInstall procps ssh bash net-tools bc zip unzip jq
}

# Install development packages for testing or development work
__installDevelopment() {
  # Debian package names
  packageInstall vim manpages git curl strace dnsutils shellcheck
}

# Incomplete but add extensions as needed
__phpExtensionDependency() {
  case "$1" in
    curl)
      if php -i | grep -q "with-curl"; then
        return 1
      fi
      printf -- "%s\n" "libcurl4"
      ;;
    gd)
      printf -- "%s\n" "zlib1g-dev" "libpng-dev" "libjpeg-dev"
      ;;
    # Built-in
    json | readline | ftp)
      return 1
      ;;
    intl)
      printf -- "%s\n" "libicu-dev"
      ;;
    zip)
      printf -- "%s\n" "libzip-dev"
      ;;
    mysqli)
      printf -- "%s\n" "mariadb-client"
      ;;
  esac
}

# List of all PHP valid extensions as of April 2025 (Debian)
__phpExtensionsList() {
  printf "%s\n" bcmath bz2 calendar ctype curl dba dl_test dom enchant exif \
    ffi fileinfo filter ftp gd gettext gmp hash iconv imap intl json ldap mbstring mysqli \
    oci8 odbc opcache pcntl pdo pdo_dblib pdo_firebird pdo_mysql pdo_oci pdo_odbc pdo_pgsql pdo_sqlite pgsql phar posix pspell \
    readline reflection session shmop simplexml snmp soap sockets sodium spl standard sysvmsg sysvsem sysvshm tidy tokenizer \
    xml xmlreader xmlwriter xsl zend_test zip
}

# Does an extension exist?
__phpExtensionExists() {
  grep -q -e "^$(quoteGrepPattern "${1}")\$" < <(__phpExtensionsList)
}

#
# Install PHP and any dependencies
#
# Argument: composerJson - File. Optional. One or more `composer.json` files to determine additional depencencies.
#
__installPHP() {
  local handler="returnMessage"

  packageInstall wget unzip zip awscli
  statusMessage decorate info Installing php extensions ...

  export PHP_INI_DIR=/usr/local/etc/php
  docker-php-ext-install mysqli pcntl calendar

  if isFunction phpComposerInstall; then
    phpComposerInstall
  elif ! whichExists composer; then
    local target="/usr/local/bin/composer"
    local tempBinary="$target.$$"
    catchReturn "$handler" urlFetch "https://getcomposer.org/composer.phar" "$tempBinary" || returnClean $? "$tempBinary" || return $?
    catchEnvironment "$handler" mv -f "$tempBinary" "$target" || returnClean $? "$tempBinary" || return $?
    catchEnvironment "$handler" chmod +x "$target" || returnClean $? "$tempBinary" || return $?
  fi

  while [ $# -gt 0 ]; do
    local json="$1"

    [ -f "$json" ] || throwArgument "$handler" "$json is not a file" || return $?

    statusMessage decorate info "Scanning $(decorate file "$json") for extensions"
    local phpExtensions=()
    IFS=$'\n' read -d "" -r -a phpExtensions < <(jq -r '.require, .["require-dev"] | to_entries[] | select(.key | startswith("ext-")) | .key' <"$json" | sort -u | cut -c 5-) || :
    statusMessage decorate info "Found extensions $(decorate each code "${phpExtensions[@]}")"
    for extension in "${phpExtensions[@]+"${phpExtensions[@]}"}"; do
      if __phpExtensionExists "$extension"; then
        if ! muzzle __phpExtensionDependency "$extension"; then
          decorate info "Skipping extension $extension"
          continue
        fi
        local dependencies=()
        IFS=$'\n' read -d "" -r -a dependencies < <(__phpExtensionDependency "$extension") || :
        statusMessage decorate info "Extension dependencies: $(decorate value "${#dependencies[@]} $(plural ${#dependencies[@]} library libraries)") $(decorate each code "${dependencies[@]}")"
        [ "${#dependencies[@]}" -eq 0 ] || catchReturn "$handler" packageInstall "${dependencies[@]}" || return $?
        catchEnvironment "$handler" docker-php-ext-install "$extension" || return $?
      else
        decorate info "Extension $(decorate code "$extension") is not installable, skipping."
      fi
    done
    shift
  done

}

# Install xdebug
__installPHPXdebug() {
  local iniFile

  # shellcheck source=/dev/null
  if [ "$(source /etc/application.conf && [ "$DEBUGGING" = "true" ] && printf -- "1")" != "1" ]; then
    decorate warning "XDebug not installed"
    return 0
  fi
  iniFile=$(phpIniFile)
  if [ ! -f "$iniFile" ]; then
    printf -- "%s\n" "$iniFile file not found" 1>&2
    return 1
  fi
  # packageInstall php-dev
  decorate info "Setting php ini path to $iniFile"
  pear config-set php_ini "$iniFile"

  decorate info "Installing xdebug ..."
  pecl install xdebug >/dev/null

  date >/etc/xdebug-enabled
}

# Tool to find any file name `MAP.*` and map it use it environment files to a new file name.
# Files are renamed unless they exist in the `--keep` directories in which case the `MAP.` file is kept.
#
# Provides an easy way to add files to a file system and then convert them into local versions using environment variables.
#
# Argument: directory - Directory. Required.
# Argument: --keep directory - Flag. Do not delete any files in this path.
__mapFiles() {
  local handler="returnMessage"
  local directories=() directory="" deleteArgs=()

  # _IDENTICAL_ argumentNonBlankLoopHandler 6
  local __saved=("$@") __count=$#
  while [ $# -gt 0 ]; do
    local argument="$1" __index=$((__count - $# + 1))
    # __IDENTICAL__ __checkBlankArgumentHandler 1
    [ -n "$argument" ] || throwArgument "$handler" "blank #$__index/$__count ($(decorate each quote -- "${__saved[@]}"))" || return $?
    case "$argument" in
      # _IDENTICAL_ helpHandler 1
      --help) "$handler" 0 && return $? || return $? ;;
      --keep)
        shift
        local keep
        keep=$(usageArgumentDirectory "$handler" "directory" "${1-}") || return $?
        deleteArgs+=(! -path "${keep%/}")
        ;;
      *)
        directory=$(usageArgumentDirectory "$handler" "directory" "${1-}") || return $?
        directories+=("$directory")
        ;;
    esac
    # _IDENTICAL_ argument-esac-shift 1
    shift
  done

  [ "${#directories[@]}" -gt 0 ] || throwArgument "$handler" "No directory supplied" || return $?

  local fileCount=0 start
  catchReturn "$handler" environmentFileLoad "/etc/application.conf" || return $?
  start=$(catchEnvironment "$handler" timingStart) || return $?
  for directory in "${directories[@]}"; do
    local fileName
    while read -r fileName; do
      newFileName=$(basename "$fileName")
      newFileName="${newFileName#MAP.}"
      statusMessage decorate info "Mapping $(decorate subtle "$fileName") -> $(decorate green "$newFileName")"
      newFileName="$(dirname "$fileName")/$newFileName"
      catchReturn "$handler" mapEnvironment <"$fileName" >"${newFileName}" || return $?
      fileCount=$((fileCount + 1))
    done < <(find "$directory" -type f -name 'MAP.*')
    find "$directory" -type f -name 'MAP.*' "${deleteArgs[@]+"${deleteArgs[@]}"}" -exec rm "{}" \; || :
  done
  statusMessage --last timingReport "$start" "Mapped $fileCount $(plural "$fileCount" file files) in"
}

#
# Clean installation when we are done
#
__installClean() {
  __aptWrapper -y autoclean
  __aptWrapper -y autoremove
}

#
# Generate a list of production values useful for configuring files and settings based on simple boolean logic
#
__productionValues() {
  local value="${1-}" trueValue falseValue onValue offValue

  if parseBoolean "$value"; then
    trueValue=true
    falseValue=false
    onValue=On
    offValue=Off
  else
    trueValue=false
    falseValue=true
    onValue=Off
    offValue=On
  fi
  local prefix
  prefix="PRODUCTION"
  printf "%s\n" "${prefix}_TRUE=$trueValue" "${prefix}_FALSE=$falseValue" "${prefix}_ON=$onValue" "${prefix}_OFF=$offValue"
  prefix="DEVELOPMENT"
  printf "%s\n" "${prefix}_TRUE=$falseValue" "${prefix}_FALSE=$trueValue" "${prefix}_ON=$offValue" "${prefix}_OFF=$onValue"
}

# Given a database scheme, print the default port used for that database
# Argument: databaseSchema - String. Required.
__portFromScheme() {
  case "${1-}" in
    mysql*) printf "%d\n" 3306 ;;
    postgres*) printf "%d\n" 5432 ;;
    *)
      throwArgument "$handler" "Unknown database scheme: \"$1\"" || return $?
      ;;
  esac
}

# Convert a data source URL into component environment variables
# Argument: handler - Function. Required. Error handler.
# Argument: target - Function. Required. Error handler.
# Argument: variables - String. Required. One or more environment variables which represent a data source URL which should be expanded
__dsnExpansions() {
  local handler="${1-"returnMessage"}" target="${2-}"

  shift 2 >/dev/null || throwArgument "$handler" "Missing handler and target" || return $?

  [ $# -gt 0 ] || throwArgument "$handler" "Missing at least one data source environment variable name ..." || return $?

  while [ $# -gt 0 ]; do
    local variable

    variable=$(usageArgumentEnvironmentVariable "$handler" "variable" "$1") || return $?

    statusMessage decorate info "Processing $variable ..."

    export "${variable?}"

    local url
    url=$(environmentValueRead "$target" "$variable") || url=""
    if [ -n "$url" ]; then
      if ! urlValid "$url"; then
        catchReturn "$handler" environmentValueWrite "${variable}_ERROR" "not-urlValid: $url" || return $?
        statusMessage decorate info "$variable not a valid URL ..."
      else
        local host="" name="" port="" user="" password="" scheme="" error="" portDefault=""
        catchReturn "$handler" urlParse --uppercase --prefix "${variable}_" "$url" >>"$target" || return $?
        eval "$(urlParse "$url")"
        : "$error" "$portDefault"
        [ -n "$port" ] || port=$(__portFromScheme "$scheme") || return $?
        statusMessage --last printf -- "%s\n" "$(decorate pair "Database:" "$name ($scheme)")"
        printf -- "%s\n" "$(decorate pair "Host:" "$host:$port")" \
          "$(decorate pair "User:" "$user")" \
          "$(decorate pair "Password:" "${#password} chars")"
      fi
    fi
    shift
  done
}

# Fetch and output application values with an optional prefix
__applicationValues() {
  local handler="$1" application="$2" prefix="$3" variable && shift 3 || _argument "${FUNCNAME[0]}" || return $?

  catchEnvironment "$handler" muzzle pushd "$application" || return $?

  # Set the context - ensure tools is loaded locally
  # shellcheck source=/dev/null
  catchEnvironment "$handler" source "$application/bin/build/tools.sh" || return $?

  catchReturn "$handler" buildEnvironmentLoad APPLICATION_NAME || return $?
  catchReturn "$handler" buildEnvironmentLoad APPLICATION_CODE || return $?
  catchReturn "$handler" environmentApplicationLoad APPLICATION_NAME APPLICATION_CODE || return $?

  catchEnvironment "$handler" muzzle popd || return $?

  catchEnvironment "$handler" hookRunOptional --application "$application" application-environment | decorate wrap "$prefix" "" || return $?

  for variable in APPLICATION_NAME APPLICATION_CODE; do
    local value="${!variable-}"
    [ -z "$value" ] || catchReturn "$handler" environmentValueWrite "$prefix$variable" "${!variable-}" || return $?
  done
}

#
# Install the environment file
#
# Argument: source - File. Source to load to generate application environment.
# Argument: target - FileDirectory. Target file to place final application environment.
# Argument: applicationHome - Directory|Empty. Optional. Application home directory to generate application values.
# Argument: applicationPrefix - String. Optional. Prefix application variables with this.
# Argument: variables - EnvironmentName. Optional. Require these to be defined in the build environment and then written to the file.
__installEnvironment() {
  local handler="returnMessage"
  local source target finalTarget application=""

  source=$(usageArgumentFile "$handler" "source" "${1-}") && shift || return $?

  finalTarget=$(usageArgumentFileDirectory "$handler" "target" "${1-}") && shift || return $?

  application="${1-}" && shift
  [ -z "$application" ] || application=$(usageArgumentDirectory "$handler" "application" "$application") || return $?
  prefix="${1-}" && shift

  catchReturn "$handler" environmentFileLoad "$source" || return $?

  target="$finalTarget.$$"

  catchEnvironment "$handler" cp -f "$source" "$target" || returnClean $? "$target" || return $?
  while [ $# -gt 0 ]; do
    local name="$1"
    export "${name?}"
    local value="${!1-}"
    [ -n "$value" ] || throwEnvironment "$handler" "Required environment variable $(decorate code "$name") is blank" || returnUndo $? dumpPipe < <(declare -px) || returnClean $? "$target" || return $?
    if ! environmentValueRead "$source" "$name"; then
      catchReturn "$handler" environmentValueWrite "$name" "$value" >>"$target" || returnClean $? "$target" || return $?
    fi
    shift
  done
  production=$(catchReturn "$handler" environmentValueRead "$target" "PRODUCTION" "unset") || returnClean $? "$target" || return $?

  __dsnExpansions "$handler" "$target" DSN || returnClean $? "$target" || return $?
  catchReturn "$handler" __productionValues "$production" >>"$target" || returnClean $? "$target" || return $?
  if [ -d "$application" ]; then
    __applicationValues "$handler" "$application" "$prefix" >>"$target" || returnClean $? "$target" || return $?
  fi
  catchEnvironment "$handler" sort -u "$target" >"$finalTarget" || returnClean $? "$target" || return $?
  catchEnvironment "$handler" rm -f "$target" || return $?
  # Sanity check I guess with Docker layers:
  if [ -f "$finalTarget" ]; then
    catchEnvironment "$handler" statusMessage --last decorate success "$finalTarget exists" || return $?

    return 0
  fi
  throwEnvironment "$handler" statusMessage --last decorate error "$finalTarget does NOT exist" 1>&2 || return $?
}

# IDENTICAL returnMessage 39

# Return passed in integer return code and output message to `stderr` (non-zero) or `stdout` (zero)
# Argument: exitCode - Required. UnsignedInteger. Exit code to return. Default is 1.
# Argument: message ... - Optional. String. Message to output
# Return Code: exitCode
# Requires: isUnsignedInteger printf returnMessage
returnMessage() {
  local handler="_${FUNCNAME[0]}"
  local to=1 icon="✅" code="${1:-1}" && shift 2>/dev/null
  if [ "$code" = "--help" ]; then "$handler" 0 && return; fi
  isUnsignedInteger "$code" || returnMessage 2 "${FUNCNAME[1]-none}:${BASH_LINENO[1]-} -> ${handler#_} non-integer \"$code\"" "$@" || return $?
  if [ "$code" -gt 0 ]; then icon="❌ [$code]" && to=2; fi
  printf -- "%s %s\n" "$icon" "${*-§}" 1>&"$to"
  return "$code"
}
_returnMessage() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

# Test if an argument is an unsigned integer
# Source: https://stackoverflow.com/questions/806906/how-do-i-test-if-a-variable-is-a-number-in-bash
# Credits: F. Hauri - Give Up GitHub (isnum_Case)
# Original: is_uint
# Argument: value - EmptyString. Value to test if it is an unsigned integer.
# Usage: {fn} argument ...
# Return Code: 0 - if it is an unsigned integer
# Return Code: 1 - if it is not an unsigned integer
# Requires: returnMessage
isUnsignedInteger() {
  [ $# -eq 1 ] || returnMessage 2 "Single argument only: $*" || return $?
  case "${1#+}" in --help) usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]}" 0 ;; '' | *[!0-9]*) return 1 ;; esac
}
_isUnsignedInteger() {
  # __IDENTICAL__ usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

# <-- END of IDENTICAL returnMessage

# ALTERNATE __source
# Load a source file and run a command
# Argument: source - Required. File. Path to source relative to application root..
# Argument: relativeHome - Required. Directory. Path to application root.
# Argument: command ... - Optional. Callable. A command to run and optional arguments.
# Requires: returnMessage
# Security: source
__source() {
  local me="${BASH_SOURCE[0]}" e=253
  local here="${me%/*}"
  local source="$here/${2:-".."}/${1-}" && shift 2 || returnMessage $e "missing source" || return $?
  [ -d "${source%/*}" ] || returnMessage $e "${source%/*} is not a directory" || return $?
  [ -f "$source" ] && [ -x "$source" ] || returnMessage $e "$source not an executable file" "$@" || return $?
  local a=("$@") && set --
  # shellcheck source=/dev/null
  source "$source" || returnMessage $e source "$source" "$@" || return $?
  [ ${#a[@]} -gt 0 ] || return 0
  "${a[@]}" || return $?
}

# IDENTICAL __tools 8

# Load build tools and run command
# Argument: relativeHome - Required. Directory. Path to application root.
# Argument: command ... - Optional. Callable. A command to run and optional arguments.
# Requires: __source
__tools() {
  __source bin/build/tools.sh "$@"
}

# Main entry point
__buildRequirements() {
  local URL_FETCHER
  # https://daniel.haxx.se/docs/curl-vs-wget.html
  # GPL: Wget is GPL v3. curl is MIT licensed.
  URL_FETCHER="curl"

  if ! which jq >/dev/null; then
    if ! tempFile="$(mktemp)"; then
      printf -- "%s\n" "mktemp failed?" 1>&2
      return 1
    fi
    if ! __aptWrapper update >"$tempFile"; then
      printf -- "%s\n" "apt-get update failed?" 1>&2
      cat "$tempFile"
      rm -rf "$tempFile"
      return 1
    fi
    rm -rf "$tempFile"
    if ! __aptWrapper install -y apt-utils toilet toilet-fonts jq pcre2-utils "$URL_FETCHER" >>"$tempFile"; then
      printf -- "%s\n" "apt-get install failed?" 1>&2
      cat "$tempFile"
      rm -rf "$tempFile"
      return 1
    fi
  fi
  [ $# -eq 0 ] || __tools .. "$@"
}

__buildRequirements "$@"
