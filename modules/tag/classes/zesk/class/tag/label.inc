<?php

namespace zesk;

/**
 * @see zesk\Tag_Label
 * @author kent
 *
 */
class Class_Tag_Label extends \Class_Object {
	public $id_column = "id";
	public $has_one = array(
		'owner' => 'User'
	);
	public $find_keys = array(
		"code"
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
		'last_used' => self::type_timestamp
	);
}