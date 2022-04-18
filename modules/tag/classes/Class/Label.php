<?php declare(strict_types=1);
/**
 * @package zesk-modules
 * @subpackage tag
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace zesk\Tag;

use zesk\Class_ORM;

/**
 * @see zesk\Tag_Label
 * @author kent
 *
 */
class Class_Label extends Class_ORM {
	/**
	 *
	 * @var string
	 */
	public string $id_column = "id";

	/**
	 *
	 * @var string
	 */
	public string $code_name = "Tag_Label";

	/**
	 *
	 * @var string
	 */
	public $name_column = "name";

	/**
	 *
	 * @var array
	 */
	public array $has_one = [
		'owner' => 'User',
	];

	/**
	 *
	 * @var array
	 */
	public array $find_keys = [
		"code",
	];

	/**
	 *
	 * @var array
	 */
	public array $column_types = [
		'id' => self::type_id,
		'code' => self::type_string,
		'name' => self::type_string,
		'is_internal' => self::type_boolean,
		'is_translated' => self::type_boolean,
		'owner' => self::type_object,
		'created' => self::type_created,
		'modified' => self::type_modified,
		'last_used' => self::type_timestamp,
	];

	/**
	 *
	 * @var array
	 */
	public array $column_defaults = [
		'last_used' => 'now',
	];
}
