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

echo 'Controller::factory is deprecated'
php-find.sh 'Controller::factory'
pause

zesk cannon $cannon_opts --skip-when-matches 'function cdn_javascript' cdn_css css
zesk cannon $cannon_opts --skip-when-matches 'function cdn_javascript' cdn_javascript javascript

echo 'Controller::factory is deprecated'
php-find.sh 'Controller::factory'
pause

echo '`Lock::crack` now takes `$application` as first parameter'
php-find.sh 'Lock::crack'
pause

echo '`Settings::instance` is deprecated. Use `Settings::singleton($application)`'
php-find.sh 'Settings::instance'
pause

echo '`Server::singleton` now takes `$application`'
php-find.sh 'Settings::singleton'
pause

echo '`zesk\\Contact_Address_Parser::parse` now takes `$application`'
php-find.sh 'Contact_Address_Parser::parse'
pause

echo '`zesk\\Options::inherit_global_options` now takes `$application`'
php-find.sh '>inherit_global_options('
pause

echo '`zesk\\Net_Sync::url_to_file` now takes `$application`'
php-find.sh '::url_to_file'
pause

echo '`zesk\\Net_Sync::urls` now takes `$application`'
php-find.sh 'Net_Sync::urls'
pause

echo '`zesk\\Net_HTTP_Client::url_content_length` now takes `$application`'
php-find.sh 'Net_HTTP_Client::url_content_length'
pause

echo '`zesk\\Net_HTTP_Client::url_headers` now takes `$application`'
php-find.sh 'Net_HTTP_Client::url_headers'
pause

echo '`zesk\\Mail::multipart_send` now takes `$application`'
php-find.sh 'Mail::multipart_send'
pause

echo '`zesk\\Mail::map` now takes `$application`'
php-find.sh 'Mail::map'
pause

echo '`zesk\\Mail::mail_array` now takes `$application`'
php-find.sh 'Mail::mail_array'
pause

echo '`zesk\\Mail::mailer` now takes `$application`'
php-find.sh 'Mail::mailer'
pause

echo '`zesk\\Mail::sendmail` now takes `$application`'
php-find.sh 'Mail::sendmail'
pause

echo '`zesk\\Mail::send_sms` now takes `$application`'
php-find.sh 'Mail::send_sms'
pause

echo '`new Net_Foo` now takes `$application`'
php-find.sh 'new Net' | grep -v app
pause

