<?php declare(strict_types=1);
namespace zesk;

class Class_Country extends Class_ORM {
	public string $id_column = "id";

	public $find_keys = [
		"code",
	];

	public array $column_types = [
		'id' => self::type_id,
		'code' => self::type_string,
		'name' => self::type_string,
	];
}
