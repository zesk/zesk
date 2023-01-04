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
		'id' => self::TYPE_ID,
		'name' => self::TYPE_STRING,
		'title' => self::TYPE_STRING,
		'class' => self::TYPE_STRING,
		'hook' => self::TYPE_STRING,
		'options' => self::TYPE_SERIALIZE,
	];

	public string $database_group = 'User';
}
