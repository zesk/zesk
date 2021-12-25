<?php declare(strict_types=1);
namespace zesk\WebApp;

class Class_Cluster extends Class_ORM {
	public $id_column = "id";

	public $column_types = [
		"id" => self::type_id,
		"name" => self::type_string,
		"code" => self::type_string,
		"sitecode" => self::type_string,
		"min_members" => self::type_integer,
		"max_members" => self::type_integer,
		"active" => self::type_timestamp,
	];

	public $find_keys = [
		"code",
	];
}
