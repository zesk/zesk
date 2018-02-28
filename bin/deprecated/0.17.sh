#!/bin/bash 
cannon_opts=""

#zesk cannon  --also-match 'extends Object'  'log::' '$this->application->logger->'
#zesk cannon $cannon_opts HTML::cdn_img HTML::img

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

heading "Fixing database constant case"
zesk cannon option_auto_table_names OPTION_AUTO_TABLE_NAMES
zesk cannon feature_create_database FEATURE_CREATE_DATABASE
zesk cannon feature_list_tables FEATURE_LIST_TABLES
zesk cannon feature_max_blob_size FEATURE_MAX_BLOB_SIZE
zesk cannon feature_cross_database_queries FEATURE_CROSS_DATABASE_QUERIES
zesk cannon feature_time_zone_relative_timestamp FEATURE_TIME_ZONE_RELATIVE_TIMESTAMP
pause

heading 'All Configuration::pave calls are removed'
php-find.sh '>pave('
php-find.sh '>pave_set('
pause

heading 'Many Database:: calls are deprecated'
php-find.sh 'Database::register'
php-find.sh 'Database::database_default'
php-find.sh 'Database::unregister'
php-find.sh 'Database::valid_schemes'
php-find.sh 'Database::register_scheme'
php-find.sh 'Database::schema_factory'
php-find.sh 'Database::_factory'
php-find.sh 'Database::instance'
php-find.sh 'Database::databases'
pause

heading 'All URL::current* calls have been removed'
php-find.sh 'URL::current'
pause

heading 'All URL::has_ref, URL::add_ref calls are removed'
php-find.sh 'URL::has_ref'
php-find.sh 'URL::add_ref'
pause

heading 'All Controller::factory calls are removed'
php-find.sh 'Controller::factory'
pause


# echo 'Mail constant changes'
# zesk cannon ::header_content_type ::HEADER_CONTENT_TYPE
# zesk cannon ::header_message_id ::HEADER_MESSAGE_ID
# zesk cannon ::header_to ::HEADER_TO
# zesk cannon ::header_from ::HEADER_FROM
# zesk cannon ::header_subject ::HEADER_SUBJECT
# 
# echo 'Locale API changes'
# zesk cannon Locale::dialect Locale::parse_dialect
# zesk cannon Locale::language Locale::parse_language
# 
# echo 'arr:: -> ArrayTools::'
# zesk cannon zesk\\arr zesk\\ArrayTools
# zesk cannon arr:: ArrayTools::
# 
# echo 'str:: -> StringTools::'
# zesk cannon zesk\\str zesk\\StringTools
# zesk cannon str:: StringTools::
# 
# 
# echo '_W() -> StringTools::wrap()'
# zesk cannon '_W(' 'StringTools::wrap('
# 
# echo 'Response_Text_HTML => Response'
# zesk cannon Response_Text_HTML Response
# 
# 
# echo 'Most `Locale::` calls are deprecated'
# php-find.sh Locale::
# pause
# 
# echo 'Function `Locale::number_format` is deprecated and should be replaced with $locale->number_format()'
# php-find.sh Locale::number_format
# pause
# 
# echo 'Globals `Locale::date_format|datetime_format|time_format` are all deprecated and should be replaced with $locale->foo()'
# php-find.sh Locale::date_format
# php-find.sh Locale::datetime_format
# php-find.sh Locale::time_format
# pause
# 
# echo 'Most `>response->` calls need to be fixed'
# php-find.sh '>response->'
# pause
