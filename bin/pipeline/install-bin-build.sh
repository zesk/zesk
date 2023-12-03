#!/usr/bin/env bash
#
# Since scripts may copy this file directly, must replicate until deprecated
#
# Load build system - part of zesk/build
#
# Copy this into your project to install the build system during development and in pipelines
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
# set -x
#
# Change this line when placing in your project to point to your application root (where `bin/build` will be based)
#
#     e.g.
#     relTop=..
#     relTop=../../..
#
# or wherever you put it in your project to install it
#

relTop=../..

# Usage: install-bin-build.sh [ --mock mockBuildRoot ]
# fn: install-bin-build.sh
# Installs the build system in `./bin/build` if not installed. Also
# will overwrite this binary with the latest version after installation.
#
# Environment: Needs internet access and creates a directory `./bin/build`
# Exit Code: 1 - Environment error
installBinBuild() {
  # IDENTICAL installBinBuild 83
  local start ignoreFile tarArgs diffLines binName replace
  if [ ! -d bin/build ]; then
    start=$(($(date +%s) + 0))
    if [ "${1-}" = "--mock" ]; then
      shift
      cp -R "$1" ./bin/build
      shift
    else
      curl -L -s "$(curl -s https://api.github.com/repos/zesk/build/releases/latest | jq -r .tarball_url)" -o build.tar.gz
      if [ "$(uname)" = "Darwin" ]; then
        tarArgs=(--include='*/bin/build/*')
      else
        tarArgs=(--wildcards '*/bin/build/*')
      fi
      tar xf build.tar.gz --strip-components=1 "${tarArgs[@]}"
      rm build.tar.gz
    fi
    if [ ! -d bin/build ]; then
      echo "Unable to download and install bin/build" 1>&2
      return "$errorEnvironment"
    fi

    # shellcheck source=/dev/null
    . bin/build/tools.sh

    consoleInfo -n "Installed bin/build "
    reportTiming "$start"
  else
    if [ ! -f bin/build/tools.sh ]; then
      exec 1>&2
      echo "Incorrect build version or broken install (can't find tools.sh):"
      echo
      echo "  rm -rf bin/build"
      echo "  ${BASH_SOURCE[0]}"
      return "$errorEnvironment"
    fi
    # shellcheck source=/dev/null
    . bin/build/tools.sh
  fi

  ignoreFile=.gitignore
  if [ -f "$ignoreFile" ] && ! grep -q "/bin/build/" "$ignoreFile"; then
    consoleWarning "$ignoreFile does not ignore ./bin/build, recommend adding it:"
    echo
    consoleCode "    echo /bin/build/ >> $ignoreFile"
    echo
  fi

  diffLines=NONE
  for binName in install-bin-build.sh build-setup.sh; do
    binName="./bin/build/$binName"
    if [ ! -x "$binName" ]; then
      continue
    fi
    diffLines=$(diff "$binName" "$myBinary" | grep -v 'relTop=' | grep -c '[<>]' || :)
    if [ "$diffLines" -eq 0 ]; then
      echo "$(consoleValue -n "$myBinary") $(consoleSuccess -n is up to date.)"
      return 0
    fi
    break
  done
  if [ "$diffLines" = "NONE" ]; then
    echo "$(consoleValue -n "$binName") $(consoleSuccess -n not found in downloaded build.)" 1>&2
    return 1
  fi

  replace=$(quoteSedPattern "relTop=$relTop")
  sed -e "s/^relTop=.*/$replace/" <"$binName" >"$myBinary.$$"
  chmod +x "$myBinary.$$"
  (mv "$myBinary.$$" "$myBinary")
  echo "$(consoleValue -n "$myBinary") $(consoleWarning -n was updated.)"
}

# The remaining lines will be replaced by the main script every time.
errorEnvironment=1
set -eou pipefail
# set -x # Debugging

myBinary="${BASH_SOURCE[0]}"
me=$(basename "$myBinary")
cd "$(dirname "$myBinary")"
myBinary="$(pwd)/$me"
cd "$relTop"

installBinBuild "$@"
