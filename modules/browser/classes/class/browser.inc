<?php declare(strict_types=1);
namespace zesk;

class Class_Browser extends Class_ORM {
	public $id_column = "id";

	public $find_keys = [
		'md5',
	];

	public $column_types = [
		'id' => self::type_id,
		'md5' => self::type_hex32,
		'is_phone' => self::type_boolean,
		'is_tablet' => self::type_boolean,
		'is_desktop' => self::type_boolean,
		'tags' => self::type_serialize,
		'name' => self::type_string,
		'created' => self::type_created,
		'parsed' => self::type_timestamp,
	];
}
