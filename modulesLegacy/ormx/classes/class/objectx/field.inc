<?php declare(strict_types=1);
namespace zesk;

class Class_BaseX_Field extends Class_Base {
	public string $id_column = 'id';

	public array $find_keys = [
		'class',
		'column',
	];

	public array $column_types = [
		'id' => self::type_id,
		'class' => self::type_string,
		'active' => self::type_string,
		'column' => self::type_string,
		'name' => self::type_string,
		'help' => self::type_string,
		'type' => self::type_string,
		'order_index' => self::type_integer,
		'data' => self::type_serialize,
	];
}
