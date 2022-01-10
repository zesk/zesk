<?php declare(strict_types=1);
namespace zesk;

class Class_County extends Class_ORM {
	public $name = "County";

	public string $id_column = "id";

	public $name_column = "name";

	public array $has_one = [
		'province' => "zesk\\Province",
	];

	public array $column_types = [
		'id' => self::type_id,
		'province' => self::type_object,
		'name' => self::type_string,
	];
}
