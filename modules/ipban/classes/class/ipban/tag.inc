<?php

/**
 * @see IPBan_Tag
 * @author kent
 */
class Class_IPBan_Tag extends Class_Object {

	public $find_keys = array(
		"type",
		"value"
	);

	public $has_one = array(
		"type" => "IPBan_Tag_Type"
	);

	protected $database_group = "IPBan";

	public $column_types = array(
		'id' => self::type_integer,
		'type' => self::type_object,
		'value' => self::type_string
	);
}
