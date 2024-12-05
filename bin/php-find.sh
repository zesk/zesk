#!/bin/bash
#
# Copyright &copy; 2024 Market Acumen, Inc.
#
gargs=()
if [ "$1" = "-l" ]; then
	gargs=("$1")
	shift
fi
find . -type f \
	\( \
	-name '*.php' \
	-or -name '*.inc' \
	-or -name '*.php4' \
	-or -name '*.phpt' \
	-or -name '*.php5' \
	-or -name '*.tpl' \
	-or -name '*.html' \
	-or -name '*.htm' \
	-or -name '*.js' \
	-or -name '*.jsx' \
	-or -name '*.css' \
	-or -name '*.less' \
	-or -name '*.sql' \
	-or -name '*.phpt' \
	-or -name '*.module' \
	-or -name '*.router' \
	-or -name '*.classes' \
	-or -name '*.install' \
	-or -name '*.conf' \
	\) -and -type f -and -not -name '*.min.js' -and -not -name '*.min.css' \
	! -path '*/vendor/*' \
	! -path '*/.*/*' \
	-print0 | xargs -0 grep "${gargs[@]}" "$@"
