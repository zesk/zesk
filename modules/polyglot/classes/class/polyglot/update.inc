<?php
namespace zesk;

/**
 * @see PolyGlot_Update
 * @author kent
 *
 */
class Class_PolyGlot_Update extends Class_Object {
	public $id_column = "locale";
	public $auto_column = false;
	public $column_types = array(
		"locale" => self::type_string,
		"updated" => self::type_timestamp,
		"user" => self::type_object
	);
	protected $database_group = "zesk\\PolyGlot_Token";
}
