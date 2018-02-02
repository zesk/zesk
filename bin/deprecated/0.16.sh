#!/bin/bash 
cannon_opts=""

#zesk cannon  --also-match 'extends Object'  'log::' '$this->application->logger->'
#zesk cannon $cannon_opts HTML::cdn_img HTML::img

pause() {
	echo -n "Return to continue: "
	read
}


echo 'Locale API changes'
zesk cannon Locale::dialect Locale::parse_dialect
zesk cannon Locale::language Locale::parse_language

echo 'arr:: -> ArrayTools::'
zesk cannon zesk\\arr zesk\\ArrayTools
zesk cannon arr:: ArrayTools::

echo 'str:: -> StringTools::'
zesk cannon zesk\\str zesk\\StringTools
zesk cannon str:: StringTools::


echo '_W() -> StringTools::wrap()'
zesk cannon '_W(' 'StringTools::wrap('

echo 'Response_Text_HTML => Response'
zesk cannon Response_Text_HTML Response


echo 'Most `Locale::` calls are deprecated'
php-find.sh Locale::
pause

echo 'Function `Locale::number_format` is deprecated and should be replaced with $locale->number_format()'
php-find.sh Locale::number_format
pause

echo 'Globals `Locale::date_format|datetime_format|time_format` are all deprecated and should be replaced with $locale->foo()'
php-find.sh Locale::date_format
php-find.sh Locale::datetime_format
php-find.sh Locale::time_format
pause

echo 'Most `>response->` calls need to be fixed'
php-find.sh '>response->'
pause
