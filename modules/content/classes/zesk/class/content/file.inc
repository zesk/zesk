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
abstract class Class_Content_File extends Class_Object {
	public $column_types = array(
		"ID" => self::type_id,
		"MIMEType" => self::type_string,
		"Original" => self::type_string,
		"Name" => self::type_string,
		"Content_Data" => self::type_object,
		"Description" => self::type_string,
		"User" => self::type_object,
		"Created" => self::type_created,
		"Modified" => self::type_modified
	);
	public $has_one = array(
		'Content_Data' => 'Content_Data',
		'User' => 'User'
	);
}
