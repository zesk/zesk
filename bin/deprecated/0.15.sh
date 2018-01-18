#!/bin/bash 
cannon_opts=""

#zesk cannon  --also-match 'extends Object'  'log::' '$this->application->logger->'
#zesk cannon $cannon_opts HTML::cdn_img HTML::img

pause() {
	echo -n "Return to continue: "
	read
}

echo 'Function ->object_iterator() is now ->orm_iterator() ...'
echo '->object_iterator() -> ->orm_iterator()'

zesk cannon $cannon_opts '>object_iterator(' '>orm_iterator('
echo 'Function ->objects_iterator() is now ->orms_iterator() ...'
zesk cannon $cannon_opts '>objects_iterator(' '>orms_iterator('

echo 'Function `File::temporary` now takes `$path` as the first parameter'
php-find.sh ile::temporary
pause

