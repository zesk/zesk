#!/usr/bin/env bash
#
# Copyright &copy; 2025 Market Acumen, Inc.
#
errorEnvironment=1

set -eou pipefail

start=$(pwd)
scriptHome=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)

current="$start"
previous=
cd "$current"
while [ ! -f "./composer.json" ]; do
  previous="$current"
  cd ..
  current=$(pwd)
  if [ "$current" = "/" ] || [ -z "$current" ] || [ "$current" = "$previous" ]; then
    echo "Unable to find composer.json" 1>&2
    exit $errorEnvironment
  fi
done

#shellcheck source=/dev/null
. "$scriptHome/bin/build/tools.sh"

usage() {
  usageDocument ./bin/latest.sh "zeskLatest" "$@"
  return $?
}

# fn: {base}
# Usage: {fn}
# Get the latest version of Zesk and install it at `./vendor/zesk/zesk`
# Exit Code: 0 - Up to date
# Exit Code: 1 - Failed
zeskLatest() {
  local vendorZesk="./vendor/zesk"
  local repo="https://github.com/zesk/zesk"
  local zeskHome="$vendorZesk/zesk" old="$vendorZesk/zesk.COMPOSER" new="$vendorZesk/zesk.GIT"
  if [ ! -d $vendorZesk ]; then
    usage "$errorEnvironment" "$vendorZesk must be a directory. Stopping."
  fi
  if [ -d "$zeskHome/.git" ]; then
    if ! git -C "$zeskHome" pull origin main; then
      usage "$errorEnvironment" "Unable to pull into zesk repository found"
    fi
    decorate success "$vendorZesk is up to date"
    return 0
  fi
  [ ! -d "$old" ] || rm -rf "$old"

  if ! git clone "$repo" "$new"; then
    decorate error "Unable to clone from $repo" 1>&2
    return $errorEnvironment
  fi
  if [ ! -d "$new" ]; then
    decorate error "Directory not created: $new" 1>&2
    return $errorEnvironment
  fi
  mv $zeskHome $old
  mv $new $zeskHome
  decorate success 'Zesk now linked to the latest'
  return 0
}

zeskLatest "$@"
