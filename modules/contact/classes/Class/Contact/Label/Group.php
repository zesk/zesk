<?php declare(strict_types=1);
namespace zesk;

class Class_Contact_Label_Group extends Class_ORM {
	protected $column_types = [
		"id" => self::type_id,
		"name" => self::type_string,
	];

	protected $find_keys = [
		"name",
	];
}
