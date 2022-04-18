<?php declare(strict_types=1);
namespace zesk;

/**
 * @see Session_ORM
 * @author kent
 *
 */
class Class_Session_ORM extends Class_ORM {
	/**
	 * ID Column
	 *
	 * @var string
	 */
	public string $id_column = "id";

	public array $find_keys = [
		"cookie",
	];

	public array $has_one = [
		"user" => "zesk\User",
	];

	public array $column_types = [
		"id" => self::type_id,
		"cookie" => self::type_string,
		"is_one_time" => self::type_boolean,
		"user" => self::type_object,
		"ip" => self::type_ip4,
		"created" => self::type_created,
		"modified" => self::type_modified,
		"expires" => self::type_datetime,
		"seen" => self::type_datetime,
		"sequence_index" => self::type_integer,
		"data" => self::type_serialize,
	];

	public string $code_name = "Session";

	public array $column_defaults = [
		'data' => [],
		'sequence_index' => 0,
		'ip' => '127.0.0.1',
	];
}
