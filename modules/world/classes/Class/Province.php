<?php declare(strict_types=1);
namespace zesk;

/**
 *
 * @author kent
 * Copyright &copy; 2013 Market Acumen, Inc.
 */
class Class_Province extends Class_ORM {
	public string $id_column = "id";

	public $name = "Province:=State";

	public array $column_types = [
		"id" => self::type_id,
		"country" => self::type_object,
		"code" => self::type_string,
		"name" => self::type_string,
	];

	public $find_keys = [
		"country",
		"name",
	];

	public array $has_one = [
		"country" => "zesk\\Country",
	];
}
