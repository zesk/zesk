<?php
/**
 * 
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Class_Mail_Header extends Class_Object {
	public $find_keys = array(
		"mail",
		"type",
		"hash"
	);
	public $column_types = array(
		'id' => self::type_integer,
		'hash' => self::type_hex,
		'mail' => self::type_object,
		'type' => self::type_object,
		'value' => self::type_string
	);
	public $has_one = array(
		'mail' => 'zesk\\Mail_Message',
		'type' => 'zesk\\Mail_Header_Type'
	);
}
