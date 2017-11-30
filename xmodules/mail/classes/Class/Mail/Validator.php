<?php
/**
 * 
 */
namespace zesk;

/**
 * @see Mail_Validator
 * @author kent
 *
 */
class Class_Mail_Validator extends Class_Object {
	/**
	 * 
	 * @var string
	 */
	public $id_column = "id";
	
	/**
	 * 
	 * @var string
	 */
	public $name_column = "address";
	
	/**
	 * 
	 * @var array
	 */
	public $column_types = array(
		"id" => self::type_id,
		"hash" => self::type_hex32,
		"user" => self::type_object,
		"address" => self::type_string,
		"sent" => self::type_timestamp,
		"confirmed" => self::type_timestamp
	);
	
	/**
	 *
	 * @var array
	 */
	public $has_one = array(
		'user' => 'zesk\\User'
	);
}

