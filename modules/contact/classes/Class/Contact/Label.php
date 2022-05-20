<?php declare(strict_types=1);
namespace zesk;

/**
 * @see Contact_Label
 */
class Class_Contact_Label extends Class_ORM {
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
		'id' => self::type_integer,
		'account' => self::type_object,
		'group' => self::type_object,
		'type' => self::type_object,
		'code' => self::type_string,
		'name' => self::type_string,
		'created' => self::type_created,
		'modified' => self::type_modified,
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
