<?php declare(strict_types=1);
namespace zesk;

class Class_Browser extends Class_Base {
	public string $id_column = 'id';

	public array $find_keys = [
		'md5',
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		'md5' => self::TYPE_HEX32,
		'is_phone' => self::TYPE_BOOL,
		'is_tablet' => self::TYPE_BOOL,
		'is_desktop' => self::TYPE_BOOL,
		'tags' => self::type_serialize,
		'name' => self::TYPE_STRING,
		'created' => self::TYPE_CREATED,
		'parsed' => self::TYPE_TIMESTAMP,
	];
}
