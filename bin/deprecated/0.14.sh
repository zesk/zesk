#!/bin/bash 
cannon_opts="--verbose"

#zesk cannon  --also-match 'extends Object'  'log::' '$this->application->logger->'
#zesk cannon $cannon_opts HTML::cdn_img HTML::img

pause() {
	echo -n "Return to continue: "
	read
}

zesk cannon Session_Database Session_ORM
zesk cannon 'zesk\Object' 'zesk\ORM'
zesk cannon 'Class_Object' 'Class_ORM'
zesk cannon 'Exception_Object' 'Exception_ORM'
zesk cannon 'Controller_Object' 'Controller_ORM'
zesk cannon '(Object $' '(ORM $'
zesk cannon '(zesk\Object $' '(zesk\ORM $'
zesk cannon '(\zesk\Object $' '(\zesk\ORM $'
zesk cannon '>_polymorphic(' '>polymorphic_child('
zesk cannon '>object_factory(' '>model_factory('
zesk cannon 'protected function _polymorphic(' 'protected function polymorphic_child('
zesk cannon object_aliases class_aliases
zesk cannon object_classes model_classes
zesk cannon Control_Select_Object Control_Select_ORM
zesk cannon Object_Iterator ORMIterator
zesk cannon Objects_Iterator ORMIterators

# echo 'Function `zesk\Kernel::sort_weight_array[_reverse]` has been renamed to `zesk_sort_weight_array[_reverse]`'
# php-find.sh sort_weight_array | grep -v 'zesk_sort_weight_array'
# pause

#echo 'Function `Currency::from_code` now takes `$application` as the first parameter'
#php-find.sh Currency::from_code | grep -v 'app'
#pause
#
#echo 'Function `System::ip_addresses` now takes `$application` as the first parameter'
#php-find.sh System::ip_addresses | grep -v 'app'
#pause
#
#echo 'Function `Job::instance` now takes `$application` as the first parameter'
#php-find.sh Job::instance | grep -v 'app'
#pause
#
#echo 'Function `zesk\Application::application_root` renamed to `zesk\Application::path`'
#zesk cannon '>application_root(' '>path('
#pause
#
#echo 'Function `zesk\Options::inherit_global_options` moved `zesk\Hookable::inherit_global_options` and `$application` parameter removed'
#zesk cannon '>inherit_global_options($this->application)' '>inherit_global_options()'
#zesk cannon '>inherit_global_options($application,' '>inherit_global_options('
#zesk cannon '>inherit_global_options($application)' '>inherit_global_options()'
#pause
#
#echo '`zesk\Options::__construct` now requires an array parameter'
#php-find.sh 'extends Options'
#pause
#echo '`zesk\Hookable::__construct` now requires an array parameter'
#php-find.sh 'extends Hookable'
#pause
#echo '`zesk\Model::__construct` now requires an array parameter'
#php-find.sh 'extends Model'
#pause
#
#