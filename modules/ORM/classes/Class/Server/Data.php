<?php declare(strict_types=1);
/**
 *
 */
namespace zesk\ORM;

/**
 * @see Server_Data
 * @author kent
 *
 */
class Class_Server_Data extends Class_Base {
	public string $id_column = 'id';

	public array $primary_keys = [
		'id',
	];

	public array $find_keys = [
		'server',
		'name',
	];

	public array $column_types = [
		'id' => self::type_id,
		'server' => self::type_object,
		'name' => self::type_string,
		'value' => self::type_serialize,
	];

	public array $has_one = [
		'server' => Server::class,
	];

	public string $database_group = Server::class;
}
