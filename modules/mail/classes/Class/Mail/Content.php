<?php
/**
 * @copyright 2017 &copy; Market Acumen, Inc. 
 * @author kent
 *
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Class_Mail_Content extends Class_Object {
	/**
	 * 
	 * @var string
	 */
	public $id_column = "id";
	
	/**
	 * 
	 * @var array
	 */
	public $find_keys = array(
		'mail',
		'content_id',
		'content_data'
	);
	
	/**
	 * 
	 * @var array
	 */
	public $has_one = array(
		'mail' => 'zesk\\Mail_Message',
		'content_data' => 'zesk\\Content_Data'
	);
	
	/**
	 * 
	 * @var array
	 */
	public $column_types = array(
		"id" => self::type_id,
		"mail" => self::type_object,
		"content_id" => self::type_string,
		"content_type" => self::type_string,
		"filename" => self::type_string,
		"disposition" => self::type_string,
		"content_data" => self::type_object
	);
	
	/**
	 * 
	 * @var array
	 */
	public $column_defaults = array(
		'disposition' => '',
		'filename' => ''
	);
}
