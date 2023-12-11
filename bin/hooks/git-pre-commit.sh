#!/usr/bin/env bash
#
# Zesk pre-commit hook
#
# Copyright &copy; 2023 Market Acumen, Inc.
#

# IDENTICAL errorEnvironment 1
errorEnvironment=1

set -eou pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/../.."

fromTo=(bin/hooks/git-pre-commit.sh .git/hooks/pre-commit)
if ! diff -q "${fromTo[@]}" >/dev/null; then
    echo -n "Git pre-commit hook was updated ..."
    cp "${fromTo[@]}"
    exec .git/hooks/pre-commit "$@"
fi

# shellcheck source=/dev/null
. ./bin/build/tools.sh

failed() {
    printf "%s: %s\n" "$(consoleError "Pre Commit Check Failed")" "$(consoleValue "$*")"
    return "$errorEnvironment"
}

gitCommitAgainst() {
    if git rev-parse --verify HEAD >/dev/null 2>&1; then
        printf %s HEAD
    else
        # Initial commit: diff against an empty tree object
        printf %s 4b825dc642cb6eb9a060e54bf8d69288fbee4904
    fi
}

checkAsciiFiles() {
    local allowOnASCII

    # If you want to allow non-ASCII filenames set this variable to true.
    allowOnASCII=$(git config --bool hooks.allownonascii)

    # Cross platform projects tend to avoid non-ASCII filenames; prevent
    # them from being added to the repository. We exploit the fact that the
    # printable range starts at the space character and ends with tilde.
    if [ "$allowOnASCII" != "true" ] &&
        # Note that the use of brackets around a tr range is ok here, (it's
        # even required, for portability to Solaris 10's /usr/bin/tr), since
        # the square bracket bytes happen to fall in the designated range.
        [ "$(($(git diff --cached --name-only --diff-filter=A -z "$(gitCommitAgainst)" | LC_ALL=C tr -d '[ -~]\0' | wc -c) + 0))" != "0" ]; then
        cat <<\EOF
Error: Attempt to add a non-ASCII file name.

This can cause problems if you want to work with people on other platforms.

To be portable it is advisable to rename the file.

If you know what you are doing you can disable this check using:

  git config hooks.allownonascii true
EOF
        return $errorEnvironment
    fi
}

phpCodingStyle() {
    local quietLog

    quietLog="$(buildQuietLog "phpCodingStyle")"
    statusMessage consoleInfo "Fixing Coding Style ..."
    clearLine
    if ! bin/cs-zesk.sh "$@" >"$quietLog" 2>&1; then
        buildFailed "$quietLog"
        return $?
    fi
    statusMessage consoleInfo "Linting PHP ..."
    for f; do
        if ! php -l "$f" >/dev/null 2>&1; then
            clearLine
            consoleError "$f failed lint test" 1>&2
            return "$errorEnvironment"
        fi
    done
    clearLine
}
gitCommitedFilesLike() {
    git diff --cached --name-status | awk '{ if ( $0 ~ /^[^DR]/) { print $2 } else if ( $0 ~ /^R/ ) { print $3 } }'
}
#
# The `git-pre-commit` hook will be installed as a `git` pre-commit hook in your project and will
# overwrite any existing `pre-commit` hook.
#
# fn: {base}
hookGitPreCommit() {
    local files

    if ! checkAsciiFiles; then
        return $errorEnvironment
    fi

    IFS=$'\n' read -r -a files < <(gitCommitedFilesLike | grep -E '.(php|inc|tpl)$' || :) || :
    if [ "${#files[@]}" -gt 0 ]; then
        statusMessage consoleInfo "Fixing Coding Style ..."
        bin/cs-zesk.sh "${files[@]}"
        statusMessage consoleInfo "Linting PHP ..."
        for f in "${files[@]}"; do
            if ! php -l "$f" >/dev/null 2>&1; then
                clearLine
                consoleError "$f failed lint test" 1>&2
                return "$errorEnvironment"
            fi
        done
    fi
    statusMessage consoleSuccess Making shell files executable ...
    if ! ./bin/build/chmod-sh.sh >/dev/null; then
        failed chmod-sh.sh
        return $?
    fi
    clearLine
    # phpCodingStyle outputs newlines
    if ! phpCodingStyle "${files[@]+${files[@]}}"; then
        failed phpCodingStyle
        return $?
    fi
    statusMessage consoleSuccess Running shellcheck ...
    if ! testShellScripts ! -path '*/vendor/*' ! -path '*/DEPRECATED/*' ! -path '*/bin/build/*'; then
        failed testShellScripts
        return $?
    fi
    # Unusual quoting here is to avoid having this match as an identical
    if ! ./bin/build/identical-check.sh --prefix '# ''IDENTICAL' --extension sh; then
        failed identical-check.sh
        return $?
    fi
    clearLine

    # If there are whitespace errors, print the offending file names and fail.
    statusMessage consoleInfo "Checking whitespace ..."
    if ! git diff-index --check --cached "$(gitCommitAgainst)" --; then
        failed "Whitespace"
        return $?
    fi
    clearLine
    # consoleSuccess "Zesk pre-commit passed"
}

hookGitPreCommit "$@"
