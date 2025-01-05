#!/usr/bin/env bash
#
# pre-commit.sh
#
# Copyright &copy; 2025 Market Acumen, Inc.

# shellcheck source=/dev/null
source "${BASH_SOURCE[0]%/*}/../../bin/build/tools.sh" || exit 99

# Make shell files executable
# Make all other files read by user and group
__preCommit() {
  local usage="_${FUNCNAME[0]}"

  home=$(__usageEnvironment "$usage" buildHome) || return $?

  version=$(__usageEnvironment "$usage" docker run -q -v "$home:/zesk" -w /zesk php:8 /zesk/bin/zesk version) || return $?
  statusMessage --last bigText "$(printf "%s v%s\n" "$(buildEnvironmentGet APPLICATION_NAME)" "$version")" | wrapLines "$(decorate bold-blue)" "$(decorate reset)"
  __usageEnvironment "$usage" makeShellFilesExecutable "$home" || return $?
  __usageEnvironment "$usage" find "$home" -type f -name '*.php' ! -path '*/bin/*' ! -path '*/.*/*' ! -path '*/vendor/*' -exec chmod 0440 {} \; || return $?
  __usageEnvironment "$usage" find "$home" -type f -name '*.php' -path '*/bin/*' ! -path '*/.*/*' ! -path '*/vendor/*' -exec chmod 0770 {} \; || return $?
  local extensions=(conf classes css csv data gif html inc ini js json less md png py router rst sql svg tpl ttf txt xls xml yml)

  local extension aa=()
  for extension in "${extensions[@]}"; do
    aa+=(-or -name "*.$extension")
  done
  unset "a[0]"
  aa=("${aa[@]}")
  __usageEnvironment "$usage" find "$home" -type f -name "${aa[@]}" ! -path '*/.*/*' -exec chmod -v 0440 {} \; || return $?
}
___preCommit() {
  # IDENTICAL usageDocument 1
  usageDocument "${BASH_SOURCE[0]}" "${FUNCNAME[0]#_}" "$@"
}

__preCommit "$@"
