<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/pdo/classes/pdo/database.inc $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2013, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */
namespace pdo;

use zesk\Database_Column;
use zesk\Database_Exception_Connect;
use zesk\Exception_Configuration;
use zesk\arr;
use zesk\URL;
use zesk\Exception_Unsupported;
use zesk\Exception_Unimplemented;
use \PDO;

class Database extends \zesk\Database {
	
	/**
	 *
	 * @var PDO
	 */
	private $pdo = null;
	
	/**
	 * Actual driver
	 *
	 * @var string
	 */
	private $driver = null;
	
	/**
	 * Actual DSN string
	 *
	 * @var string
	 */
	private $dsn = null;
	/**
	 * Actual DSN options
	 *
	 * @var array
	 */
	private $dsn_options = null;
	static $driver_dsn_keys = array(
		"mysql" => array(
			"charset" => "charset"
		),
		"odbc" => array()
	);
	static $driver_options = array(
		"mysql" => array(
			"charset" => "charset"
		),
		"odbc" => array()
	);
	private static $shell_commands = array(
		'mysql' => 'mysql',
		'pgsql' => 'psql',
		'sqlite3' => 'sqlite3'
	);
	private function _init() {
		if ($this->dsn !== null) {
			return;
		}
		$scheme = $host = $port = $name = null;
		if ($scheme === "pdo") {
			$scheme = $this->option('driver', null);
			if ($scheme === null) {
				throw new Exception_Configuration("driver", "URL scheme for Database_PDO lacks driver {url}", array(
					"url" => URL::remove_password($this->URL)
				));
			}
		}
		extract($this->url_parts, EXTR_IF_EXISTS);
		$this->driver = $scheme;
		$this->dsn = "$scheme:dbname=$name;host=$host";
		if ($port !== null) {
			$this->dsn .= ";port=$port";
		}
		$dsn_keys = avalue(self::$driver_dsn_keys, $scheme, array());
		foreach ($dsn_keys as $option_key => $dsn_key) {
			if ($this->has_option($option_key)) {
				$this->dsn .= ";" . $dsn_key . "=" . $this->option($option_key);
			}
		}
		$map = avalue(self::$driver_options, $scheme, array());
		$this->dsn_options = arr::map_keys(arr::filter($this->options, array_keys($map)), $map);
	}
	/**
	 * Connect to the database
	 *
	 * @return boolean true if the connection is successful, false if not
	 */
	protected function _connect() {
		$this->_init();
		if ($this->pdo instanceof PDO) {
			return $this->pdo;
		}
		$user = $password = null;
		extract($this->url_parts, EXTR_IF_EXISTS);
		try {
			$this->pdo = new PDO($this->dsn, $user, $password, $this->dsn_options);
		} catch (\PDOException $e) {
			throw new Database_Exception_Connect($this->URL, $e->getMessage(), array(), $e->getCode());
		}
	}
	public function shell_command(array $options = array()) {
		$this->_init();
		return avalue(self::$shell_commands, $this->driver, null);
	}
	public function connection() {
		return $this->pdo;
	}
	
	/**
	 * Does this table exist?
	 *
	 * @return boolean
	 */
	function table_exists($table_name) {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__);
	}
	public function dump($filename, array $options = array()) {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__);
	}
	public function restore($filename, array $options = array()) {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__);
	}
	/**
	 * Switches to another database in this connection.
	 *
	 * Not supported by all databases.
	 *
	 * @return Database
	 * @param string $name
	 */
	public function select_database($name) {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__);
	}
	
	/**
	 * Create a Database_Table object from the database's schema
	 *
	 * @param string $table
	 *        	A database table name
	 * @return Database_Table The database table parsed from the database's definition of a table
	 */
	public function database_table($table) {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__);
	}
	
	/**
	 * Clean up any loose data from a database query.
	 * Frees any resources from the query.
	 *
	 * @param mixed $result
	 *        	The result of a query command.
	 * @return void
	 * @see zesk\Database::query
	 */
	public function free($result) {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__);
	}
	
	/**
	 * Execute a SQL statment with this database
	 *
	 * @param string $query
	 *        	A SQL statement
	 * @return mixed A resource or boolean value which represents the result of the query
	 */
	public function query($query) {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__);
	}
	
	/**
	 * After an insert statement, retrieves the most recent statement's insertion ID
	 *
	 * @return mixed The most recent insertion ID
	 */
	public function insert_id() {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $result
	 */
	public function fetch_array($result) {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $result
	 */
	public function fetch_assoc($result) {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__);
	}
	
	/**
	 * Retrieve table columns
	 *
	 * @param string $table
	 * @throws Exception_Unsupported
	 */
	public function table_columns($table) {
		throw new Exception_Unsupported();
	}
	
	/**
	 * Get lock
	 *
	 * @param unknown $name
	 * @return boolean
	 */
	public function get_lock($name, $wait_seconds = 0) {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__);
	}
	
	/**
	 * Release lock
	 *
	 * @param unknown $name
	 * @return boolean
	 */
	public function release_lock($name) {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__);
	}
	
	/**
	 * Get or set a feature of the database.
	 * See const feature_foo defined above.
	 *
	 * Also can use custom database strings.
	 *
	 * @param string $feature
	 * @param mixed $set
	 * @return mixed Database
	 */
	public function feature($feature, $set = null) {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__);
	}
	
	/**
	 * Return the number of rows affected by the most recent insert/update/delete
	 *
	 * @param resource $result
	 *        	Query result
	 */
	function affected_rows($result = null) {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__);
	}
	
	/**
	 * Quote text
	 *
	 * @param string $text
	 */
	public function native_quote_text($text) {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__);
	}
	
	/**
	 * Quote text
	 *
	 * @param string $text
	 */
	public function bytes_used($table = null) {
		throw new Exception_Unimplemented(__CLASS__ . "::" . __METHOD__);
	}
	
	/**
	 * Handle database-specific differences between two columns
	 *
	 * @param Database_Column $self
	 *        	Database column being compared
	 * @param Database_Column $that
	 *        	Database column being compared to
	 * @param array $diffs
	 *        	Existing differences bewteen the two columns, which you may add to, and then
	 *        	return.
	 * @return array Any additional diffs
	 */
	public function column_differences(Database_Column $self, Database_Column $that, array $diffs) {
		return $diffs;
	}
}

