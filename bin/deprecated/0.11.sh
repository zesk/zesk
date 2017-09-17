#!/bin/bash 
cannon_opts="--verbose"

#zesk cannon  --also-match 'extends Object'  'log::' '$this->application->logger->'
#zesk cannon $cannon_opts HTML::cdn_img HTML::img

pause() {
	echo -n "Return to continue: "
	read
}

echo 'Function `zesk\Kernel::sort_weight_array` has been renamed to `zesk_sort_weight_array`'
php-find.sh sort_weight_array | grep -v 'zesk_sort_weight_array'
pause

