<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 * @see Server_Data
 * @author kent
 *
 */
class Class_Server_Data extends Class_ORM {
	public string $id_column = "id";

	public array $primary_keys = [
		"id",
	];

	public array $find_keys = [
		"server",
		"name",
	];

	public array $column_types = [
		'id' => self::type_id,
		'server' => self::type_object,
		'name' => self::type_string,
		'value' => self::type_serialize,
	];

	public array $has_one = [
		'server' => 'zesk\\Server',
	];

	public string $database_group = "zesk\\Server";
}
