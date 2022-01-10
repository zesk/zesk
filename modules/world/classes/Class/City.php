<?php declare(strict_types=1);
namespace zesk;

class Class_City extends Class_ORM {
	public string $id_column = "id";

	public $find_keys = [
		"name",
	];

	public array $has_one = [
		'province' => 'zesk\\Province',
		'county' => 'zesk\\County',
	];

	public array $column_types = [
		"id" => self::type_id,
		"province" => self::type_object,
		"county" => self::type_object,
		'name' => self::type_string,
	];
}
