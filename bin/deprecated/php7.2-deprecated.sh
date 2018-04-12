#!/bin/bash 
cannon_opts=""

Color_Off='\033[0m'       # Text Reset
Red='\033[0;31m'          # Red
Blue='\033[0;34m'         # Blue

IBlack='\033[0;90m'       # Black

pause() {
	echo -ne $Blue"Return to continue: "$Color_Off
	read
}

heading() {
	echo -e $Red$*$Color_Off
	echo -ne $IBlack
}

deprecated() {
	heading "$* is deprecated"
	php-find.sh "$*"
	echo -ne $Color_Off
}

deprecated __autoload
deprecated '$php_errormsg'
deprecated create_function
deprecated '(unset)'
deprecated '( unset )'
deprecated 'gmp_random'
deprecated ' each('
deprecated '(binary)'

heading "parse_str should always take 2 arguments - confirm this below"
php-find.sh parse_str
pause

heading "\$errcontext argument of error handler is deprecated - ensure handler has 4 argments or less"
php-find.sh set_error_handler
pause

