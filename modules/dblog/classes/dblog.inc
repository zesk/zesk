<?php
/**
 * Database Logging Object
 *
 * @author kent
 * @copyright (C) 2013 Market Acumen, Inc.
 * @package zesk
 * @subpackage dblog
 */

/**
 * The object in the database
 *
 * @author kent
 *        
 */
class DBLog extends Object {
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
		'arguments' => 'serialize',
		'when' => 'datetime',
		'ip' => 'ip'
	);
	/**
	 * Our columns
	 *
	 * @var array
	 */
	protected $columns = array(
		'id',
		'when',
		'microsec',
		'module',
		'message',
		'level',
		'level_string',
		'pid',
		'server',
		'ip',
		'user',
		'session',
		'arguments'
	);
}
