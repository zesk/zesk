#!/bin/bash 
cannon_opts="--verbose"

#zesk cannon  --also-match 'extends Object'  'log::' '$this->application->logger->'
#zesk cannon $cannon_opts HTML::cdn_img HTML::img

pause() {
	echo -n "Return to continue: "
	read
}

# echo 'Function `zesk\Kernel::sort_weight_array[_reverse]` has been renamed to `zesk_sort_weight_array[_reverse]`'
# php-find.sh sort_weight_array | grep -v 'zesk_sort_weight_array'
# pause

echo 'Function `Currency::from_code` now takes `$application` as the first parameter'
php-find.sh Currency::from_code | grep -v 'app'
pause

echo 'Function `System::ip_addresses` now takes `$application` as the first parameter'
php-find.sh System::ip_addresses | grep -v 'app'
pause

echo 'Function `Job::instance` now takes `$application` as the first parameter'
php-find.sh Job::instance | grep -v 'app'
pause

