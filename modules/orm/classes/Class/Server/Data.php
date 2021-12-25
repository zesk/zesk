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
	public $id_column = "id";

	public $primary_keys = [
		"id",
	];

	public $find_keys = [
		"server",
		"name",
	];

	public $column_types = [
		'id' => self::type_id,
		'server' => self::type_object,
		'name' => self::type_string,
		'value' => self::type_serialize,
	];

	public $has_one = [
		'server' => 'zesk\\Server',
	];

	public $database_group = "zesk\\Server";
}
