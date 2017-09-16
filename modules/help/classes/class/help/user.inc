<?php
namespace zesk;

class Class_Help_User extends Class_Object {
	public $primary_keys = array(
		'help',
		'user'
	);
	public $has_one = array(
		'help' => 'zesk\\Help',
		'user' => 'zesk\\User'
	);
	public $column_types = array(
		'help' => self::type_object,
		'user' => self::type_object,
		'dismissed' => self::type_created
	);
}
