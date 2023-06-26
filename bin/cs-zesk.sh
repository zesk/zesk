#!/bin/bash
top="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." || exit 1; pwd)"

PATH=$top/vendor/bin:$PATH

php_fixer=php-cs-fixer
fixer=$(which $php_fixer)
if [ ! -x "$fixer" ]; then
	echo "no $php_fixer found in path $PATH" 1>&2
	exit 1
fi
$fixer fix --allow-risky=yes
