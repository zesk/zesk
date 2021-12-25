#!/bin/bash
export APPLICATION_ROOT="$(cd $(dirname "$BASH_SOURCE")/..; pwd)"

PATH=$APPLICATION_ROOT/vendor/bin:$PATH

php_fixer=php-cs-fixer
fixer=$(which $php_fixer)
if [ ! -x "$fixer" ]; then
	echo "no $php_fixer found in path $PATH" 1>&2
	exit 1
fi
$fixer fix --allow-risky=yes
