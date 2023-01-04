<?php declare(strict_types=1);
namespace zesk;

class Class_Contact_Label_Group extends Class_Base {
	protected $column_types = [
		'id' => self::TYPE_ID,
		'name' => self::TYPE_STRING,
	];

	protected $find_keys = [
		'name',
	];
}
