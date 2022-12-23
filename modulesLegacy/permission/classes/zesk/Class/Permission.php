<?php declare(strict_types=1);
/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Class_Permission extends Class_Base {
	/**
	 * ORM id column
	 *
	 * @var string
	 */
	public string $id_column = 'id';

	/**
	 * How to find one of these without an id?
	 *
	 * @var array
	 */
	public array $find_keys = [
		'name',
	];

	/**
	 * Special handling of columns
	 *
	 * @var array
	 */
	public array $column_types = [
		'id' => self::type_id,
		'name' => self::type_string,
		'title' => self::type_string,
		'class' => self::type_string,
		'hook' => self::type_string,
		'options' => self::type_serialize,
	];

	public string $database_group = 'User';
}
