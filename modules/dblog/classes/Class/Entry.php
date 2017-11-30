<?php
/**
 * @package zesk
 * @subpackage DBLog
 * @author kent
 * @copyright &copy; 2017 Market Acumen, Inc.
 */
namespace zesk\DBLog;

/**
 * @author kent
 */
class Class_Entry extends \zesk\Class_Object {
	
	/**
	 * @var string
	 */
	protected $table = "Log";
	/**
	 * Our ID column
	 *
	 * @var string
	 */
	protected $id_column = "id";
	/**
	 * How do we find an entry?
	 *
	 * @var array
	 */
	protected $find_keys = array();
	/**
	 * Column definitions
	 *
	 * @var array
	 */
	protected $column_types = array(
		'id' => self::type_id,
		'when' => self::type_timestamp,
		'microsec' => self::type_integer,
		'module' => self::type_string,
		'message' => self::type_string,
		'level' => self::type_string,
		'pid' => self::type_integer,
		'server' => self::type_object,
		'ip' => self::type_ip4,
		'user' => self::type_object,
		'session' => self::type_object,
		'arguments' => self::type_serialize
	);
}