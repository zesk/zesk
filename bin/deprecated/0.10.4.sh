#!/bin/bash 
cannon_opts="--verbose"

#zesk cannon  --also-match 'extends Object'  'log::' '$this->application->logger->'
zesk cannon $cannon_opts --skip-when-matches 'function cdn_javascript' cdn_css css
zesk cannon $cannon_opts --skip-when-matches 'function cdn_javascript' cdn_javascript javascript

pause() {
	echo -n "Enter to continue: "
	read
}
echo 'Controller::factory is deprecated'
php-find.sh 'Controller::factory'
pause

echo '`Lock::crack` now takes `$application` as first parameter'
php-find.sh 'Lock::crack'
pause

