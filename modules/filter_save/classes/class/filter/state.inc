<?php declare(strict_types=1);
namespace zesk;

class Class_Filter_State extends Class_ORM {
	public string $id_column = 'id';

	public array $has_one = [
		'user' => 'zesk\\User',
	];

	public array $column_types = [
		'id' => self::type_id,
		'scope' => self::type_string,
		'user' => self::type_object,
		'name' => self::type_string,
		'filters' => self::type_string,
	];
}
