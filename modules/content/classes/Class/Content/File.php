<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/content/classes/zesk/content/file.inc $
 * @package zesk
 * @subpackage file
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 * @see Content_File
 */
class Class_Content_File extends Class_Object {
	/**
	 * 
	 * @var string
	 */
	public $id_column = "id";
	
	/**
	 * 
	 * @var array
	 */
	public $column_types = array(
		"id" => self::type_id,
		"mime" => self::type_string,
		"original" => self::type_string,
		"name" => self::type_string,
		"data" => self::type_object,
		"description" => self::type_string,
		"user" => self::type_object,
		"created" => self::type_created,
		"modified" => self::type_modified
	);
	
	/**
	 * 
	 * @var array
	 */
	public $has_one = array(
		'data' => 'zesk\\Content_Data',
		'user' => 'zesk\\User'
	);
}
