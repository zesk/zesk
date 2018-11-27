<?php
/**
 * @package zesk-modules
 * @subpackage tag
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
 */
namespace zesk\Tag;

use zesk\Class_ORM;

/**
 * @see zesk\Tag_Label
 * @author kent
 *
 */
class Class_Label extends Class_ORM {
	public $id_column = "id";

	public $has_one = array(
		'owner' => 'User',
	);

	public $find_keys = array(
		"code",
	);

	public $column_types = array(
		'id' => self::type_id,
		'code' => self::type_string,
		'name' => self::type_string,
		'is_internal' => self::type_boolean,
		'is_translated' => self::type_boolean,
		'owner' => self::type_object,
		'created' => self::type_created,
		'modified' => self::type_modified,
		'last_used' => self::type_timestamp,
	);
}
