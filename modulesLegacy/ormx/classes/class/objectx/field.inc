<?php declare(strict_types=1);
namespace zesk;

class Class_BaseX_Field extends Class_Base {
	public string $id_column = 'id';

	public array $find_keys = [
		'class',
		'column',
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		'class' => self::TYPE_STRING,
		'active' => self::TYPE_STRING,
		'column' => self::TYPE_STRING,
		'name' => self::TYPE_STRING,
		'help' => self::TYPE_STRING,
		'type' => self::TYPE_STRING,
		'order_index' => self::TYPE_INTEGER,
		'data' => self::type_serialize,
	];
}
