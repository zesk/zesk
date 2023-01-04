<?php declare(strict_types=1);
namespace zesk;

/**
 * @see Contact_Label
 */
class Class_Contact_Label extends Class_Base {
	/**
	 *
	 * @var string
	 */
	public string $id_column = 'id';

	/**
	 * @todo Group does not have a class
	 *
	 * @var array
	 */
	public array $column_types = [
		'id' => self::TYPE_INTEGER,
		'account' => self::TYPE_OBJECT,
		'group' => self::TYPE_OBJECT,
		'type' => self::TYPE_OBJECT,
		'code' => self::TYPE_STRING,
		'name' => self::TYPE_STRING,
		'created' => self::TYPE_CREATED,
		'modified' => self::TYPE_MODIFIED,
	];

	/**
	 *
	 * @var array
	 */
	public array $find_keys = [
		'account',
		'code',
	];

	public array $has_one = [
		'account' => 'zesk\\Account',
	];
}
