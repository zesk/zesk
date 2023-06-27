<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk\ORM;

/**
 * @see ServerMeta
 * @author kent
 *
 */
class Class_ServerMeta extends Class_Base
{
	public string $id_column = 'id';

	public array $primary_keys = [
		'id',
	];

	public array $find_keys = [
		'server',
		'name',
	];

	public array $column_types = [
		'id' => self::TYPE_ID,
		'server' => self::TYPE_OBJECT,
		'name' => self::TYPE_STRING,
		'value' => self::TYPE_SERIALIZE,
	];

	public array $has_one = [
		'server' => Server::class,
	];

	public string $database_group = Server::class;
}
