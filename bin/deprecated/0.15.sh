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

echo 'Function `Locale::number_format` is deprecated and should be replaced with $locale->number_format()'
php-find.sh Locale::number_format
pause

echo 'Globals `Locale::date_format|datetime_format|time_format` are all deprecated and should be replaced with $locale->foo()'
php-find.sh Locale::date_format
php-find.sh Locale::datetime_format
php-find.sh Locale::time_format
pause

echo 'Locale API changes'
zesk cannon Locale::dialect Locale::parse_dialect
zesk cannon Locale::language Locale::parse_language

echo 'arr:: -> ArrayTools::'
zesk cannon zesk\\arr zesk\\ArrayTools
zesk cannon arr:: ArrayTools::

echo 'str:: -> StringTools::'
zesk cannon zesk\\str zesk\\StringTools
zesk cannon str:: StringTools::


