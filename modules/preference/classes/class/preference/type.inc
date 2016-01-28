<?php
/**
* $URL: https://code.marketacumen.com/zesk/trunk/modules/preference/classes/class/preference/type.inc $
* @package zesk
* @subpackage user
* @author Kent Davidson <kent@marketacumen.com>
* @copyright Copyright &copy; 2005, Market Acumen, Inc.
*/
class Class_Preference_Type extends Class_Object {
	public $id_column = "id";

	public $column_types = array(
		"id" => self::type_id,
		"code" => self::type_string,
		"name" => self::type_string,
		"description" => self::type_string
	);

	public $has_many = array(
		'preferences' => array(
			'class' => "Preference",
			'foreign_key' => 'type'
		)
	);

	public $find_keys = array(
		"code"
	);

	public $database_group = "Preference";
}
