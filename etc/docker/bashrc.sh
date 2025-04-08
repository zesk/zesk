#!/usr/bin/env bash
#
# Copyright &copy; 2025, Market Acumen, Inc.
#
# User bash configuration
#

__bashInitialize() {
  local file files=("build/tools.sh" "application.sh")
  for file in "${files[@]}"; do
    file="/usr/local/bin/$file"
    if [ ! -f "$file" ]; then
      printf -- "%s\n" "$file is missing" 1>&2
      return 1
    fi
    # shellcheck source=/dev/null
    if ! source "$file"; then
      printf -- "%s\n" "$file is corrupt" 1>&2
      return 1
    fi
  done
  applicationBashPrompt "$1 [$(decorate code "$2")]"
}

__bashShellOptions() {
  shopt -s shift_verbose
  # If set, the extended pattern matching features described above (see Pattern Matching) are enabled.
  shopt -u extglob
  # If this is set, an argument to the cd builtin command that is not a directory is assumed to be the name of a variable whose value is the directory to change to.
  shopt -s cdable_vars
  # If set, minor errors in the spelling of a directory component in a cd command will be corrected. The errors checked for are transposed characters, a missing character, and a character too many. If a correction is found, the corrected path is printed, and the command proceeds. This option is only used by interactive shells.
  shopt -s cdspell
  # check window size after each command and update LINES and COLUMNS if necessary
  shopt -s checkwinsize
  # If this is set, Bash checks that a command found in the hash table exists before trying to execute it. If a hashed command no longer exists, a normal path search is performed.
  shopt -s checkhash
  # If set, Bash includes filenames beginning with a ‘.’ in the results of filename expansion. The filenames ‘.’ and ‘..’ must always be matched explicitly, even if dotglob is set.
  shopt -s dotglob
  # If set, and Readline is being used, Bash will not attempt to search the PATH for possible completions when completion is attempted on an empty line.
  shopt -s no_empty_cmd_completion
  # If set, Bash attempts to save all lines of a multiple-line command in the same history entry. This allows easy re-editing of multi-line commands. This option is enabled by default, but only has an effect if command history is enabled (see Bash History Facilities).
  shopt -s cmdhist
  # If enabled, and the cmdhist option is enabled, multi-line commands are saved to the history with embedded newlines rather than using semicolon separators where possible.
  shopt -s lithist
}

__bashInitialize "{APPLICATION_NAME}" "{BUILD_CODE}"
__bashShellOptions
! isiTerm2 || iTerm2Init

export PATH="$PATH:/usr/local/bin"
