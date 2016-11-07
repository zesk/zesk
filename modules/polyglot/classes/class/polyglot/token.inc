<?php

/**
 * @see PolyGlot_Token
 * @author kent
 *
 */
class Class_PolyGlot_Token extends zesk\Class_Object {
	public $id_column = "id";

	public $has_one = array(
		"user" => "User"
	);

	public $column_types = array(
		'id' => self::type_id,
		'language' => self::type_string,
		'dialect' => self::type_string,
		'md5' => self::type_hex,
		'original' => self::type_string,
		'translation' => self::type_string,
		'user' => self::type_object,
		'context' => self::type_string,
		'status' => self::type_string,
		'updated' => self::type_modified
	);
}
