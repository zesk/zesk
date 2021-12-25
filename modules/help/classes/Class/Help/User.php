<?php declare(strict_types=1);
namespace zesk;

class Class_Help_User extends Class_ORM {
	public $primary_keys = [
		'help',
		'user',
	];

	public $has_one = [
		'help' => 'zesk\\Help',
		'user' => 'zesk\\User',
	];

	public $column_types = [
		'help' => self::type_object,
		'user' => self::type_object,
		'dismissed' => self::type_created,
	];
}
