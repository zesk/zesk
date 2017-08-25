#!/bin/bash 
cannon_opts="--verbose"

#zesk cannon  --also-match 'extends Object'  'log::' '$this->application->logger->'
zesk cannon $cannon_opts HTML::cdn_img HTML::img

pause() {
	echo -n "Return to continue: "
	read
}
echo "NOT CHANGED, please change manually: "

echo 'First parameter is now $application:'
php-find.sh Language::lang_name
pause

echo 'First parameter is now $application:'
php-find.sh Language::clean_table
pause

echo 'First parameter is now $application:'
php-find.sh Currency::usd
pause

echo 'First parameter is now $application:'
php-find.sh Selection_Query::copy_duplicate
pause

echo 'First parameter is now $application:'
php-find.sh Selection_Item::copy_duplicate
pause

echo 'First parameter is now $application:'
php-find.sh PolyGlot_Token::fetch_all
pause

echo 'First parameter is now $application:'
php-find.sh PolyGlot_Update::update_locale
pause

echo 'First parameter is now $application:'
php-find.sh PolyGlot_Token::htmlentities_all
pause

echo 'First parameter is now $application:'
php-find.sh PolyGlot_Token::locale_query
pause





echo 'First parameter is now $application:'
php-find.sh Role::root_id
pause
echo 'First parameter is now $application:'
php-find.sh HTML::img
pause
echo 'First parameter is now $application:'
php-find.sh Controller_Share::realpath
pause

echo 'Function is deprecated:'
php-find.sh Role::default_id
pause


echo 'Function is obsolete:'
php-find.sh Module_Critical::alert
pause

echo 'Function is obsolete:'
php-find.sh Object::class_instance
pause


echo 'First parameter is now $application:'
php-find.sh Content_Data::copy_from_path
php-find.sh Content_Data::move_from_path
php-find.sh Content_Data::from_string
pause

echo 'First parameter is now $application:'
php-find.sh Preference_Type::register_name
pause


echo 'Configuration_Loader no longer takes \$name as first parameter'
php-find.sh 'new Configuration_Loader'
pause

echo 'Configuration::pave_set and Configuration::pave will be depreacted shortly'
php-find.sh '>pave_set('
php-find.sh '>pave('
pause
