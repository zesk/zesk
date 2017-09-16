#!/bin/bash
#
# Super-handy script to find matches for a string in files in a project.
#
# Use this while refactoring, or when finding and removing deprecated functionality.
#
gargs=
if [ "$1" = "-l" ]; then
	gargs=$1
	shift
fi
find . -type f \
	\( \
	-name '*.php' \
	-or -name '*.classes' \
	-or -name '*.conf' \
	-or -name '*.css' \
	-or -name '*.htm' \
	-or -name '*.html' \
	-or -name '*.inc' \
	-or -name '*.install' \
	-or -name '*.js' \
	-or -name '*.json' \
	-or -name '*.jsx' \
	-or -name '*.less' \
	-or -name '*.module' \
	-or -name '*.php4' \
	-or -name '*.php5' \
	-or -name '*.phpt' \
	-or -name '*.router' \
	-or -name '*.sql' \
	-or -name '*.tpl' \
	\) -and -type f -and -not -name '*.min.js' -and -not -name '*.min.css' -print0 \
	| xargs -0 grep $gargs "$*" | cut -c -1024
