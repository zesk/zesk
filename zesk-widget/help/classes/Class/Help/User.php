<?php declare(strict_types=1);
namespace zesk;

class Class_Help_User extends Class_Base {
	public array $primary_keys = [
		'help',
		'user',
	];

	public array $has_one = [
		'help' => 'zesk\\Help',
		'user' => 'zesk\\User',
	];

	public array $column_types = [
		'help' => self::TYPE_OBJECT,
		'user' => self::TYPE_OBJECT,
		'dismissed' => self::TYPE_CREATED,
	];
}
