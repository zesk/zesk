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
  packageInstall procps ssh bash software-properties-common net-tools bc zip unzip jq
}

# Install development packages for testing or development work
__installDevelopment() {
  # Debian package names
  packageInstall vim manpages git curl strace dnsutils
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
    # Built-in
    json | readline)
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
  local usage="_return"

  packageInstall wget unzip zip awscli
  statusMessage decorate info Installing php extensions ...

  export PHP_INI_DIR=/usr/local/etc/php
  docker-php-ext-install mysqli pcntl calendar

  if isFunction phpComposerInstall; then
    phpComposerInstall
  elif ! whichExists composer; then
    local target="/usr/local/bin/composer"
    local tempBinary="$target.$$"
    __catchEnvironment "$usage" urlFetch "https://getcomposer.org/composer.phar" "$tempBinary" || _clean $? "$tempBinary" || return $?
    __catchEnvironment "$usage" mv -f "$tempBinary" "$target" || _clean $? "$tempBinary" || return $?
    __catchEnvironment "$usage" chmod +x "$target" || _clean $? "$tempBinary" || return $?
  fi

  while [ $# -gt 0 ]; do
    local json="$1"

    [ -f "$json" ] || __throwArgument "$usage" "$json is not a file" || return $?

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
        [ "${#dependencies[@]}" -eq 0 ] || __catchEnvironment "$usage" packageInstall "${dependencies[@]}" || return $?
        __catchEnvironment "$usage" docker-php-ext-install "$extension" || return $?
      else
        decorate info "Extension $(decorate code "$extension") is not installable, skipping."
      fi
    done
    shift
  done

}

# Tool to find any file name `MAP.*` and map it use it environment files to a new file name.
# Files are renamed unless they exist in the `--keep` directories in which case the `MAP.` file is kept.
#
# Provides an easy way to add files to a file system and then convert them into local versions using environment variables.
#
# Argument: directory - Directory. Required.
# Argument: --keep directory - Flag. Do not delete any files in this path.
__mapFiles() {
  local usage="_return"
  local directory="" deleteArgs=()

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
      --keep)
        shift
        local keep
        keep=$(usageArgumentDirectory "$usage" "directory" "${1-}") || return $?
        deleteArgs+=(! -path "${keep%/}")
        ;;
      *)
        if [ -z "$directory" ]; then
          directory=$(usageArgumentDirectory "$usage" "directory" "${1-}") || return $?
          # BUG with usageArgumentDirectory
          [ -n "$directory" ] || directory="/"
        else
          # _IDENTICAL_ argumentUnknown 1
          __throwArgument "$usage" "unknown #$__index/$__count \"$argument\" ($(decorate each code "${__saved[@]}"))" || return $?
        fi
        ;;
    esac
    # _IDENTICAL_ argument-esac-shift 1
    shift
  done

  [ -n "$directory" ] || __throwArgument "$usage" "No directory supplied" || return $?

  local fileName fileCount=0 start
  start=$(startTiming)
  __catchEnvironment "$usage" environmentFileLoad "/etc/application.conf" || return $?
  while read -r fileName; do
    newFileName=$(basename "$fileName")
    newFileName="${newFileName#MAP.}"
    statusMessage decorate info "Mapping $(decorate subtle "$fileName") -> $(decorate green "$newFileName")"
    newFileName="$(dirname "$fileName")/$newFileName"
    __catchEnvironment "$usage" mapEnvironment <"$fileName" >"${newFileName}" || return $?
    fileCount=$((fileCount + 1))
  done < <(find "$directory" -type f -name 'MAP.*')
  find "$directory" -type f -name 'MAP.*' "${deleteArgs[@]+"${deleteArgs[@]}"}" -exec rm "{}" \; || :
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
      __throwArgument "$usage" "Unknown database scheme: \"$1\"" || return $?
      ;;
  esac
}

# Convert a data source URL into component environment variables
# Argument: usage - Function. Required. Error handler.
# Argument: target - Function. Required. Error handler.
# Argument: variables - String. Required. One or more environment variables which represent a data source URL which should be expanded
__dsnExpansions() {
  local usage="${1-"_return"}" target="${2-}"

  shift 2 >/dev/null || __throwArgument "$usage" "Missing usage and target" || return $?

  [ $# -gt 0 ] || __throwArgument "$usage" "Missing at least one data source environment variable name ..." || return $?

  while [ $# -gt 0 ]; do
    local variable

    variable=$(usageArgumentEnvironmentVariable "$usage" "variable" "$1") || return $?

    statusMessage decorate info "Processing $variable ..."

    export "${variable?}"

    local url
    url=$(environmentValueRead "$target" "$variable") || url=""
    if [ -n "$url" ]; then
      if ! urlValid "$url"; then
        __catchEnvironment "$usage" environmentValueWrite "${variable}_ERROR" "not-urlValid: $url" || return $?
        statusMessage decorate info "$variable not a valid URL ..."
      else
        local host="" name="" port="" user="" password="" scheme="" suffix
        eval "$(urlParse "$url")"
        [ -n "$port" ] || port=$(__portFromScheme "$scheme") || return $?
        for suffix in scheme host name port user password; do
          __catchEnvironment "$usage" environmentValueWrite "${variable}_$(uppercase "$suffix")" "${!suffix}" >>"$target" || return $?
        done
        statusMessage --last printf -- "%s\n" "$(decorate pair "Database:" "$name ($scheme)")"
        printf -- "%s\n" "$(decorate pair "Host:" "$host:$port")" \
          "$(decorate pair "User:" "$user")" \
          "$(decorate pair "Password:" "${#password} chars")"
      fi
    fi
    shift
  done
}

# Fetch application values
__applicationValues() {
  local usage="_return"
  local application="$1"

  __catchEnvironment "$usage" muzzle pushd "$application" || return $?

  # Set the context - ensure tools is loaded locally

  # shellcheck source=/dev/null
  source "$application/bin/tools.sh"

  buildEnvironmentLoad APPLICATION_NAME
  buildEnvironmentLoad APPLICATION_CODE
  environmentApplicationLoad APPLICATION_NAME APPLICATION_CODE
  __catchEnvironment "$usage" muzzle popd || return $?
}

#
# Install the environment file
#
# Argument: source - Source to load to generate application environment.
# Argument: variables - EnvironmentName. Optional. Require these.
__installEnvironment() {
  local usage="_return"
  local source target

  source=$(usageArgumentFile "$usage" "source" "${1-}") && shift || return $?
  target=$(usageArgumentFileDirectory "$usage" "target" "${1-}") && shift || return $?
  application=$(usageArgumentDirectory "$usage" "application" "${1-}") && shift || return $?
  __catchEnvironment "$usage" cp -f "$source" "$target" || return $?

  __catchEnvironment "$usage" environmentFileLoad "$source" || return $?

  while [ $# -gt 0 ]; do
    local name="$1"
    export "${name?}"
    local value="${!1-}"
    [ -n "$value" ] || __throwEnvironment "$usage" "Required environment variable $(decorate code "$name") is blank" || _undo $? dumpPipe < <(declare -px) || return $?
    if ! environmentValueRead "$source" "$name"; then
      __catchEnvironment "$usage" environmentValueWrite "$name" "$value" >>"$target" || return $?
    fi
    shift
  done
  production=$(__catchEnvironment "$usage" environmentValueRead "$target" "PRODUCTION" "unset") || return $?

  __dsnExpansions "$usage" "$target" DSN || return $?
  __catchEnvironment "$usage" __productionValues "$production" >>"$target" || return $?
  __catchEnvironment "$usage" __applicationValues "$application" >>"$target" || return $?
  # Sanity check I guess with Docker layers:
  if [ -f "$target" ]; then
    __catchEnvironment "$usage" statusMessage --last decorate success "$target exists" || return $?
    return 0
  fi
  __throwEnvironment "$usage" statusMessage --last decorate error "$target does NOT exist" 1>&2 || return $?
}

# Install xdebug
__installPHPXdebug() {
  local iniFile

  iniFile=$(phpIniFile)
  if [ ! -f "$iniFile" ]; then
    printf -- "%s\n" "$iniFile file not found" 1>&2
    return 1
  fi
  packageInstall php-dev
  decorate info "Setting php ini path to $iniFile"
  pear config-set php_ini "$iniFile"

  decorate info "Installing xdebug ..."
  pecl install xdebug >/dev/null
}

# IDENTICAL _return 26

# Usage: {fn} [ exitCode [ message ... ] ]
# Argument: exitCode - Required. Integer. Exit code to return. Default is 1.
# Argument: message ... - Optional. String. Message to output to stderr.
# Exit Code: exitCode
# Requires: isUnsignedInteger printf _return
_return() {
  local r="${1-:1}" && shift 2>/dev/null
  isUnsignedInteger "$r" || _return 2 "${FUNCNAME[1]-none}:${BASH_LINENO[1]-} -> ${FUNCNAME[0]} non-integer $r" "$@" || return $?
  printf -- "[%d] ❌ %s\n" "$r" "${*-§}" 1>&2 || : && return "$r"
}

# Test if an argument is an unsigned integer
# Source: https://stackoverflow.com/questions/806906/how-do-i-test-if-a-variable-is-a-number-in-bash
# Credits: F. Hauri - Give Up GitHub (isnum_Case)
# Original: is_uint
# Usage: {fn} argument ...
# Exit Code: 0 - if it is an unsigned integer
# Exit Code: 1 - if it is not an unsigned integer
# Requires: _return
isUnsignedInteger() {
  [ $# -eq 1 ] || _return 2 "Single argument only: $*" || return $?
  case "${1#+}" in '' | *[!0-9]*) return 1 ;; esac
}

# <-- END of IDENTICAL _return

# ALTERNATE __source
# Load a source file and run a command
# Argument: source - Required. File. Path to source relative to application root..
# Argument: relativeHome - Required. Directory. Path to application root.
# Argument: command ... - Optional. Callable. A command to run and optional arguments.
# Requires: _return
# Security: source
__source() {
  local me="${BASH_SOURCE[0]}" e=253
  local here="${me%/*}"
  local source="$here/${2:-".."}/${1-}" && shift 2 || _return $e "missing source" || return $?
  [ -d "${source%/*}" ] || _return $e "${source%/*} is not a directory" || return $?
  [ -f "$source" ] && [ -x "$source" ] || _return $e "$source not an executable file" "$@" || return $?
  local a=("$@") && set --
  # shellcheck source=/dev/null
  source "$source" || _return $e source "$source" "$@" || return $?
  [ ${#a[@]} -gt 0 ] || return 0
  "${a[@]}" || return $?
}

# IDENTICAL __tools 8

# Load build tools and run command
# Argument: relativeHome - Required. Directory. Path to application root.
# Argument: command ... - Optional. Callable. A command to run and optional arguments.
# Requires: __source _return
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
    if ! __aptWrapper install -y apt-utils toilet toilet-fonts jq pcregrep "$URL_FETCHER" >>"$tempFile"; then
      printf -- "%s\n" "apt-get install failed?" 1>&2
      cat "$tempFile"
      rm -rf "$tempFile"
      return 1
    fi
  fi
  [ $# -eq 0 ] || __tools .. "$@"
}

__buildRequirements "$@"
