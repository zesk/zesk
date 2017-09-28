<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Database.php $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2014, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */
namespace zesk;

/**
 *
 * @package zesk
 * @subpackage system
 */
abstract class Database extends Hookable {
	
	/**
	 * Setting this option on a database will convert all SQL to automatically set the table names
	 * from class names
	 *
	 * @var string
	 */
	const option_auto_table_names = 'auto_table_names';
	/**
	 * Does this database support creation of other databases?
	 *
	 * @var string
	 */
	const feature_create_database = "create_database";
	/**
	 * Does this database support listing of tables?
	 *
	 * @var string
	 */
	const feature_list_tables = "list_tables";
	/**
	 *
	 * @var string
	 */
	const feature_max_blob_size = "max_blob_size";
	/**
	 * Can this database perform queries across databases on the same connection?
	 *
	 * @var string
	 */
	const feature_cross_database_queries = "cross_database_queries";
	/**
	 * Does the database support timestamps which are relative to a session or global time zone
	 * setting?
	 *
	 * @var string
	 */
	const feature_time_zone_relative_timestamp = "time_zone_relative_timestamp";
	
	/**
	 *
	 * @var Application
	 */
	public $application = null;
	
	/**
	 * Debug database connections
	 *
	 * @var boolean
	 */
	public static $debug = false;
	
	/**
	 *
	 * @var Database_Parser
	 */
	protected $parser = null;
	
	/**
	 * SQL Generation
	 *
	 * @var Database_SQL
	 */
	protected $sql = null;
	
	/**
	 * Data Type
	 *
	 * @var Database_Data_Type
	 */
	protected $data_type = null;
	
	/**
	 *
	 * @var string
	 */
	private $internal_name = null;
	
	/**
	 * Internal query timer
	 *
	 * @var Timer
	 */
	protected $timer = null;
	
	/**
	 * URL for the current connection
	 *
	 * @var string
	 */
	protected $URL = null;
	
	/**
	 * Parsed URL
	 */
	protected $url_parts = array();
	
	/**
	 * URL without password
	 *
	 * @var string
	 */
	protected $safe_url = null;
	
	/**
	 * Class to use for singleton creation
	 *
	 * @var string
	 */
	protected $singleton_prefix = null;
	
	/**
	 * Global database name => url mapping
	 *
	 * @var array
	 */
	private static $database_names = array();
	
	/**
	 * Global databases
	 *
	 * @var array
	 */
	private static $databases = array();
	
	/**
	 * Default database name
	 */
	private static $database_name_default = '';
	
	/**
	 * For auto table, cache of class name -> table name
	 *
	 * @var array of string => string
	 */
	private $table_name_cache = array();
	
	/**
	 * Options to be passed to new objects when generating table names.
	 *
	 * @var array
	 */
	private $auto_table_names_options = array();
	
	/**
	 * Construct a new Database
	 *
	 * @param string $url        	
	 */
	public function __construct(Application $application, $url = null, array $options = array()) {
		$this->application = $application;
		parent::__construct($options);
		$this->inherit_global_options($application);
		// TODO Pass this in __construct and propagate
		$application->hooks->register_class(__CLASS__);
		$application->hooks->register_class(get_class($this));
		if ($url) {
			$this->_init_url($url);
		}
	}
	
	/**
	 * Internal function to manage factories for Database functionality
	 *
	 * @param string $var        	
	 * @param string $suffix        	
	 * @return Ambigous <stdClass, object>
	 */
	private function _singleton($var, $suffix) {
		$class = ($this->singleton_prefix ? $this->singleton_prefix : get_class($this)) . $suffix;
		return $this->$var ? $this->$var : ($this->$var = $this->application->objects->factory($class, $this));
	}
	/**
	 * Factory for native database code parser
	 *
	 * @return Database_Parser
	 */
	function parser() {
		return $this->_singleton("parser", '_Parser');
	}
	
	/**
	 * Factory for native code generator
	 *
	 * @return Database_SQL
	 */
	function sql() {
		return $this->_singleton("sql", '_SQL');
	}
	
	/**
	 * Factory for native data type handler
	 *
	 * @return Database_Data_Type
	 */
	function data_type() {
		return $this->_singleton("data_type", '_Type');
	}
	
	/**
	 * Retrieve additional column attributes which are supported by this database, in the form
	 * array("attribute1" => "default_value1")
	 *
	 * @return array
	 */
	function column_attributes(Database_Column $column) {
		return array();
	}
	
	/**
	 * Retrieve additional table attributes which are supported by this database, in the form
	 * array("attribute1" => "default_value1")
	 *
	 * @return array
	 */
	function table_attributes() {
		return array();
	}
	
	/**
	 * Generator utilities - native NOW string for database
	 *
	 * @return string
	 */
	final function now() {
		return $this->sql()->now();
	}
	/**
	 * Generator utilities - native NOW string for database
	 *
	 * @return string
	 */
	final function now_utc() {
		return $this->sql()->now_utc();
	}
	/*
	 * Are table names case-sensitive?
	 *
	 * @return boolean
	 */
	function tables_case_sensitive() {
		return $this->option_bool("tables_case_sensitive", true);
	}
	
	/**
	 * Select a single row from a table
	 *
	 * @param string $table        	
	 * @param array $where        	
	 * @param string $order_by        	
	 * @return array
	 */
	public function select_one_where($table, $where, $order_by = false) {
		$sql = $this->sql()->select(array(
			'what' => '*',
			'tables' => $table,
			'where' => $where,
			'order_by' => $order_by,
			'limit' => 1,
			'offset' => 0
		));
		return $this->query_one($sql);
	}
	
	/**
	 * Change URL associated with this database and related settings
	 *
	 * @param string $url        	
	 */
	private function _init_url($url) {
		$this->url_parts = $parts = self::url_parse($url);
		$this->set_option($parts);
		self::set_option(URL::query_parse(avalue($parts, "query")), null, false);
		$this->Connection = false;
		$this->URL = $url;
		$this->Safe_URL = URL::remove_password($url);
	}
	
	/**
	 * Register system-wide hooks
	 */
	public static function hooks(Kernel $zesk) {
		$zesk->hooks->add(Hooks::hook_database_configure, __CLASS__ . "::_configured", "first");
		$zesk->hooks->add('exit', __CLASS__ . "::disconnect_all", "last");
		
		//		$zesk->hooks->add('pcntl_fork-parent', "Database::reconnect_all");
		$zesk->hooks->add('pcntl_fork-child', __CLASS__ . "::reconnect_all");
	}
	
	/**
	 * Parse SQL to determine type of command
	 *
	 * @param string $sql        	
	 * @param string $field
	 *        	Optional desired field.
	 * @return multitype:string NULL |Ambigous <mixed, array>
	 */
	public function parse_sql($sql, $field = null) {
		return $this->parser()->parse_sql($sql, $field);
	}
	
	/**
	 * Retrieve just the comand from a SQL statement
	 *
	 * @param string $sql        	
	 * @return string or NULL
	 */
	public function parse_sql_command($sql) {
		return $this->parse_sql($sql, 'command');
	}
	
	/**
	 * Given a list of SQL commands separated by ;, extract individual statements
	 *
	 * @param string $sql        	
	 * @return array
	 */
	public function split_sql_commands($sql) {
		return $this->parser()->split_sql_commands($sql);
	}
	/**
	 * Convert database to string representation
	 *
	 * @see Options::__toString()
	 */
	public function __toString() {
		return $this->Safe_URL;
	}
	
	/**
	 * Internal name of Database
	 *
	 * @return string
	 */
	public function code_name() {
		return $this->internal_name;
	}
	
	/**
	 * Retrieve URL or url component
	 *
	 * @param string $component        	
	 * @return string
	 */
	public function url($component = null) {
		$url = $this->URL;
		if ($component === null) {
			return $url;
		}
		return avalue($this->url_parts, $component);
	}
	
	/**
	 * Database Type (specifically, the URI scheme)
	 *
	 * @return string
	 */
	final public function type() {
		return $this->url('scheme');
	}
	
	/**
	 * Name of the database
	 *
	 * @return string
	 */
	public function database_name() {
		return ltrim($this->url('path'), '/');
	}
	
	/**
	 * Synonym for factory
	 *
	 * @param string $url        	
	 * @param array $options        	
	 * @return Database
	 */
	public static function instance(Application $application, $url = null, array $options = array()) {
		zesk()->deprecated();
		return self::factory($application, $url, $options);
	}
	/**
	 * Parse a Database URL into components
	 *
	 * @param string $url        	
	 * @param string $component
	 *        	Optional component to return
	 *        	
	 * @return string
	 */
	public static function url_parse($url, $component = null) {
		$parts = URL::parse($url);
		if (!$parts) {
			return $parts;
		}
		$parts['name'] = trim(avalue($parts, 'path'), '/ ');
		return $component === null ? $parts : avalue($parts, $component);
	}
	
	/**
	 * Return all connected databases in the system
	 *
	 * @return multitype:
	 */
	public static function databases() {
		return self::$databases;
	}
	
	/**
	 * Reconned databases on fork
	 */
	public static function reconnect_all() {
		/* @var $database Database */
		foreach (self::$databases as $url => $database) {
			zesk()->logger->info("Reconnecting database: $url");
			$database->reconnect();
		}
	}
	
	/**
	 * Reconned databases on fork
	 */
	public static function disconnect_all() {
		/* @var $database Database */
		foreach (self::$databases as $url => $database) {
			$database->disconnect();
		}
	}
	static $scheme_to_class = array();
	
	/**
	 * Does this database support URL schemes as passed in?
	 *
	 * @param string $scheme        	
	 * @return boolean
	 */
	public function supports_scheme($scheme) {
		if (empty($scheme)) {
			return false;
		}
		$class = self::register_scheme($scheme);
		return $class === get_class($this) ? true : false;
	}
	public static function valid_schemes() {
		return array_keys(self::$scheme_to_class);
	}
	/**
	 * Register or retrieve a class for a database scheme prefic
	 *
	 * @param string $scheme        	
	 * @param string $classname        	
	 * @return string
	 */
	public static function register_scheme($scheme, $classname = null) {
		$scheme = strtolower($scheme);
		$exists = array_key_exists($scheme, self::$scheme_to_class);
		if ($classname === null) {
			return $exists ? self::$scheme_to_class[$scheme] : null;
		}
		if ($exists) {
			zesk()->logger->warning("Registered {scheme} again for class {classname}", compact("scheme", "classname"));
		}
		self::$scheme_to_class[$scheme] = $classname;
		return $classname;
	}
	
	/**
	 *
	 * @param Application $application        	
	 * @param unknown $scheme        	
	 * @param array $options        	
	 * @throws Exception_NotFound
	 * @return object|\zesk\stdClass
	 */
	public static function scheme_factory(Application $application, $scheme, array $options = array()) {
		$class = self::register_scheme($scheme);
		if (!$class) {
			throw new Exception_NotFound("Database scheme {scheme} does not have a registered handler. Available schemes: {schemes}", array(
				"scheme" => $scheme,
				"schemes" => self::valid_schemes()
			));
		}
		return $application->factory($class, $application, null, $options);
	}
	
	/**
	 * Create a new database
	 *
	 * @deprecated 2016-07-06
	 * @param string $url
	 *        	Connection URL in the form
	 *        	dbtype://user:password@host/databasename?option0=value0&option1=value1. Currently
	 *        	MySQL and SQLite3 supported.
	 * @return Database
	 */
	public static function factory(Application $application, $mixed = null, array $options = array()) {
		zesk()->deprecated();
		return self::_factory($application, $mixed, $options);
	}
	
	/**
	 * Create a new database
	 *
	 * @param string $url
	 *        	Connection URL in the form
	 *        	dbtype://user:password@host/databasename?option0=value0&option1=value1. Currently
	 *        	MySQL and SQLite3 supported.
	 * @return Database
	 */
	public static function _factory(Application $application, $mixed = null, array $options = array()) {
		$original = $mixed;
		if (URL::valid($mixed)) {
			$url = URL::normalize($mixed);
			$codename = avalue(array_flip(self::register()), $url, $url);
		} else {
			if (empty($mixed)) {
				$mixed = self::database_default();
				if (empty($mixed)) {
					$mixed = "default";
				}
			}
			$url = self::register($mixed);
			$codename = $mixed;
			$databases = self::register();
			if (count($databases) === 0) {
				throw new Exception_Configuration("Database::names", "No default database URL configured: \"{default}\"", array(
					"default" => self::database_default()
				));
			}
			if (!$url) {
				throw new Exception_NotFound("Database not found: \"{name}\" from databases: {databases}", array(
					"name" => $original,
					"databases" => JSON::encode(array_keys(self::register()))
				));
			}
		}
		$safe_url = URL::remove_password($url);
		if (to_bool(avalue($options, 'reuse', true))) {
			$db = avalue(self::$databases, $codename);
			if ($db) {
				return $db;
			}
		} else {
			if (array_key_exists($codename, self::$databases)) {
				$codename .= "#" . count(self::$databases);
			}
		}
		$scheme = URL::scheme($url);
		$class = self::register_scheme($scheme);
		if (!$class) {
			throw new Database_Exception_Unknown_Schema("Database::factory({url}) {scheme} not registered. Valid schemes: {schemes}", array(
				"url" => $safe_url,
				"scheme" => $scheme,
				"schemes" => self::valid_schemes()
			));
		}
		try {
			$db = $application->objects->factory($class, $application, $url, $options);
		} catch (Exception $e) {
			$application->hooks->call("exception", $e);
			throw $e;
		}
		if (!$db instanceof Database) {
			throw new Exception_Unimplemented("Database::factory({url}) {scheme} did not return a Database", array(
				"url" => $safe_url,
				"scheme" => $scheme
			));
		}
		$db->internal_name = $codename;
		$db->set_option("internal_name", $codename);
		self::$databases[$codename] = $db;
		if (avalue($options, 'connect', true)) {
			if (!$db->connect()) {
				zesk()->logger->warning("Failed to connect to database: $safe_url");
				return null;
			}
			if ($db->option_bool("debug")) {
				zesk()->logger->debug("Connected to database: $safe_url");
			}
		}
		return $db;
	}
	
	/**
	 * Change the URL for this database.
	 * Useful for pointing an existing Database instance to a slave for read-only operations, etc.
	 *
	 * @param unknown $url        	
	 * @return self
	 */
	public function change_url($url) {
		$connected = $this->connected();
		if ($connected) {
			$this->disconnect();
		}
		$this->_init_url($url);
		if ($connected) {
			$this->connect();
		}
		return $this;
	}
	
	/**
	 * Returns the connection URL with the password removed
	 *
	 * @param string $filler
	 *        	To put garbage in place of the password, pass in what should appear instead (e.g.
	 *        	"*****")
	 * @return string
	 */
	public final function safe_url($filler = false) {
		$parts = $this->url_parts;
		if ($filler) {
			$parts['pass'] = $filler;
		} else {
			unset($parts['pass']);
		}
		return URL::unparse($parts);
	}
	
	/**
	 * Connect to the database
	 *
	 * @return boolean true if the connection is successful, false if not
	 */
	final public function connect() {
		if (!$this->_connect()) {
			throw new Database_Exception_Connect($this->URL, "Unable to connect to database {safe_url}", $this->variables());
		}
		$this->call_hook("connect");
		return true;
	}
	public function connected() {
		return $this->connection() !== null;
	}
	/**
	 * Connect to the database
	 *
	 * @return boolean true if the connection is successful, false if not
	 */
	abstract protected function _connect();
	
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
	abstract public function feature($feature, $set = null);
	
	/**
	 * Disconnect from database
	 */
	public function disconnect() {
		if (self::$debug) {
			zesk()->logger->debug("Disconnecting from database {url}", array(
				"url" => $this->safe_url()
			));
		}
		$this->call_hook('disconnect');
	}
	
	/**
	 * Retrieve raw database connection.
	 * Return null if not connected.
	 *
	 * @return mixed|null
	 */
	abstract public function connection();
	
	/**
	 * Run a database shell command to perform various actions
	 *
	 * @param array $options        	
	 */
	abstract public function shell_command(array $options = array());
	
	/**
	 * Reconnect the database
	 */
	public function reconnect() {
		$this->disconnect();
		return $this->connect();
	}
	
	/**
	 * Can I create another database in the current connection?
	 *
	 * @return boolean
	 */
	public function can($permission) {
		return false;
	}
	
	/**
	 * Create a new database with the current connection
	 *
	 * @param string $url        	
	 */
	function create_database($url, array $hosts) {
		throw new Exception_Unimplemented(get_class($this) . "::create_database($url)");
	}
	
	/**
	 * Does this table exist?
	 *
	 * @return boolean
	 */
	abstract function table_exists($table_name);
	
	/**
	 * Retrieve a list of tables from the databse
	 *
	 * @return array
	 */
	function list_tables() {
		throw new Exception_Unimplemented("{method} in {class}", array(
			"method" => __METHOD__,
			"class" => get_class($this)
		));
	}
	
	/**
	 * Output a file which is a database dump of the database
	 *
	 * @param string $filename
	 *        	The path to where the database should be dumped
	 * @param array $options
	 *        	Options for dumping the database - dependent on database type
	 * @return boolean Whether the operation succeeded (true) or not (false)
	 */
	abstract public function dump($filename, array $options = array());
	
	/**
	 * Given a database file, restore the database
	 *
	 * @param string $filename
	 *        	A file to restore the database from
	 * @param array $options
	 *        	Options for dumping the database - dependent on database type
	 * @return boolean Whether the operation succeeded (true) or not (false)
	 */
	abstract public function restore($filename, array $options = array());
	
	/**
	 * Switches to another database in this connection.
	 *
	 * Not supported by all databases.
	 *
	 * @return Database
	 * @param string $name        	
	 */
	abstract public function select_database($name);
	
	/**
	 * Create a Database_Table object from the database's schema
	 *
	 * @param string $table
	 *        	A database table name
	 * @return Database_Table The database table parsed from the database's definition of a table
	 */
	abstract public function database_table($table);
	
	/**
	 * Create a Database_Table object from a create table SQL statement
	 *
	 * @param string $sql
	 *        	A CREATE TABLE sql command
	 * @param string $source
	 *        	Debugging information as to where the SQL originated
	 * @return Database_Table The database table parsed from the sql command
	 */
	public function parse_create_table($sql, $source = null) {
		$parser = Database_Parser::parse_factory($this, $sql, $source);
		return $parser->create_table($sql);
	}
	
	/**
	 * Execute a SQL statment with this database
	 *
	 * @param string $query
	 *        	A SQL statement
	 * @param array $options
	 *        	Settings, options for this query
	 *        	
	 * @return mixed A resource or boolean value which represents the result of the query
	 */
	abstract public function query($query, array $options = array());
	
	/**
	 * Replace functionality
	 *
	 * @param string $table        	
	 * @param array $columns        	
	 * @param array $options
	 *        	Database-specific options
	 *        	
	 * @return integer
	 */
	public function replace($table, array $values, array $options = array()) {
		$sql = $this->sql()->insert(array(
			'table' => $table,
			'values' => $values,
			'verb' => 'REPLACE'
		) + $options);
		if (!$this->query($sql)) {
			return avalue($options, 'default', null);
		}
		return $this->insert_id();
	}
	
	/**
	 * Execute a SQL statment with this database
	 *
	 * @param string $query
	 *        	A SQL statement
	 * @return mixed A resource or boolean value which represents the result of the query
	 */
	public function insert($table, array $columns, array $options = array()) {
		$sql = $this->sql()->insert(array(
			'table' => $table,
			'values' => $columns
		) + $options);
		if (!$this->query($sql)) {
			return avalue($options, 'default', null);
		}
		return avalue($options, 'id', true) ? $this->insert_id() : true;
	}
	
	/**
	 * Clean up any loose data from a database query.
	 * Frees any resources from the query.
	 *
	 * @param mixed $result
	 *        	The result of a query command.
	 * @return void
	 * @see Database::query
	 */
	abstract public function free($result);
	
	/**
	 * After an insert statement, retrieves the most recent statement's insertion ID
	 *
	 * @return mixed The most recent insertion ID
	 */
	abstract public function insert_id();
	
	/**
	 * Given a database select result, fetch a row as a 0-indexed array
	 *
	 * @param mixed $result        	
	 * @return array
	 */
	abstract public function fetch_array($result);
	
	/**
	 * Given a database select result, fetch a row as a name/value array
	 *
	 * @param mixed $result        	
	 * @return array
	 */
	abstract public function fetch_assoc($result);
	
	/**
	 * Retrieve a single field or fields from the database
	 *
	 * @param string $sql        	
	 * @param string $field
	 *        	A named field, or an integer index to retrieve
	 * @param string $default        	
	 * @return string
	 */
	final public function query_one($sql, $field = null, $default = null, array $options = array()) {
		$res = $this->query($sql, $options);
		if ($res === null || $res === false) {
			return $default;
		}
		$row = is_numeric($field) ? $this->fetch_array($res) : $this->fetch_assoc($res);
		$this->free($res);
		if (!is_array($row)) {
			return $default;
		}
		if ($field === false || $field === null) {
			return $row;
		}
		return avalue($row, $field, $default);
	}
	
	/**
	 * Retrieve a single row which should contain an integer
	 *
	 * @param string $sql        	
	 * @param string $field        	
	 * @param number $default        	
	 * @return number
	 */
	final public function query_integer($sql, $field = null, $default = 0) {
		$result = $this->query_one($sql, $field, null);
		if ($result === null) {
			return $default;
		}
		return intval($result);
	}
	
	/**
	 * Internal implementation of query_array and query_array_index
	 *
	 * @param string $method
	 *        	Fetch method
	 * @param mixed $sql
	 *        	Query to execute
	 * @param string $k
	 *        	Use this column as a result key in the resulting array
	 * @param string $v
	 *        	Use this column as the value in the resulting array
	 * @param mixed $default
	 *        	Default result if query fails
	 * @return array mixed
	 */
	private function _query_array($method, $sql, $k = null, $v = null, $default = array()) {
		$res = $this->query($sql);
		if (!$res) {
			return $default;
		}
		$result = array();
		if ($k === null || $k === false) {
			if ($v === null || $v === false) {
				while (is_array($row = $this->$method($res))) {
					$result[] = $row;
				}
			} else {
				while (is_array($row = $this->$method($res))) {
					$result[] = avalue($row, $v);
				}
			}
		} else {
			if ($v === null || $v === false) {
				while (is_array($row = $this->$method($res))) {
					$result[avalue($row, $k)] = $row;
				}
			} else {
				while (is_array($row = $this->$method($res))) {
					$result[avalue($row, $k)] = avalue($row, $v);
				}
			}
		}
		$this->free($res);
		return $result;
	}
	
	/**
	 * Retrieve rows as name-based array and index keys or values
	 *
	 * @param mixed $sql
	 *        	Query to execute
	 * @param string $k
	 *        	Use this column as a result key in the resulting array
	 * @param string $v
	 *        	Use this column as the value in the resulting array
	 * @param mixed $default
	 *        	Default result if query fails
	 * @return array mixed
	 */
	final public function query_array($sql, $k = null, $v = null, $default = array()) {
		return $this->_query_array("fetch_assoc", $sql, $k, $v, $default);
	}
	
	/**
	 * Retrieve rows as order-based array and index keys or values
	 *
	 * @param mixed $sql
	 *        	Query to execute
	 * @param string $k
	 *        	Use this column as a result key in the resulting array
	 * @param string $v
	 *        	Use this column as the value in the resulting array
	 * @param mixed $default
	 *        	Default result if query fails
	 * @return array mixed
	 */
	final public function query_array_index($sql, $k = null, $v = null, $default = array()) {
		return $this->_query_array("fetch_array", $sql, $k, $v, $default);
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $word        	
	 * @return unknown
	 */
	function is_reserved_word($word) {
		$word = strtolower($word);
		return false;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $value        	
	 * @return unknown
	 */
	function sql_parse_boolean($value) {
		return $value ? "1" : "0";
	}
	
	/**
	 * Begin a transaction in the database
	 *
	 * @return boolean
	 */
	function transaction_start() {
		// TODO: Ensure database is in auto-commit mode
		// TODO Move to subclasses
		return $this->query("START TRANSACTION");
	}
	
	/**
	 * Finish transaction in the database
	 *
	 * @param boolean $success
	 *        	Whether to commit (true) or roll back (false)
	 * @return boolean
	 */
	function transaction_end($success = true) {
		// TODO Move to subclasses
		$sql = $success ? "COMMIT" : "ROLLBACK";
		return $this->query($sql);
	}
	public function default_engine() {
		return $this->option("table_type_default");
	}
	public function default_index_structure($table_type) {
		return $this->option("index_structure_default");
	}
	
	/**
	 * Factory method to allow subclasses of Database to create Database_Table subclasses
	 *
	 * @param string $table
	 *        	Name of the table
	 * @param string $type
	 *        	Type of table structure (e.g. MyISQM, InnoDB, etc.)
	 * @return Database_Table Newly created Database_Table
	 */
	public function new_database_table($table, $type = false) {
		return new Database_Table($this, $table, $type);
	}
	
	/**
	 * Retrieve the database table prefix
	 *
	 * @return string A string which is pre-pended to some database table names
	 */
	function table_prefix() {
		return $this->option("table_prefix", $this->option("tableprefix", ""));
	}
	/**
	 * Update a table
	 *
	 * @param string $table        	
	 * @param array $values        	
	 * @param array $where        	
	 * @param array $options        	
	 * @return mixed
	 */
	public function update($table, array $values, array $where = array(), array $options = array()) {
		$sql = $this->sql()->update(array(
			"table" => $table,
			"values" => $values,
			"where" => $where
		) + $options);
		return $this->query($sql);
	}
	
	/**
	 * Run a delete query
	 *
	 * @param string $table        	
	 * @param array $where        	
	 * @return mixed
	 */
	public function delete($table, array $where = array()) {
		$sql = $this->sql()->delete(array(
			'table' => $table,
			'where' => $where
		));
		return $this->query($sql);
	}
	/**
	 * Return the number of rows affected by the most recent insert/update/delete
	 *
	 * @param resource $result
	 *        	Query result
	 */
	abstract function affected_rows($result = null);
	public function quote_name($name) {
		return $this->sql()->quote_column($name);
	}
	public function unquote_name($name) {
		return $this->sql()->unquote_name($name);
	}
	public function quote_table($name) {
		return $this->sql()->quote_table($name);
	}
	public function quote_text($text) {
		return $this->sql()->quote_text($text);
	}
	/**
	 * Quote text
	 *
	 * @param
	 *        	string$text
	 */
	abstract public function native_quote_text($text);
	
	/**
	 * Utility function to unquote a table
	 *
	 * @param string $name        	
	 * @return string
	 */
	public function unquote_table($name) {
		return $this->sql()->unquote_table($name);
	}
	private function valid_sql_name($name) {
		return preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $name) !== 0;
	}
	public function valid_index_name($name) {
		return self::valid_sql_name($name);
	}
	public function valid_column_name($name) {
		return self::valid_sql_name($name);
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
	 * Retrieve table column, if exists
	 *
	 * @param string $table        	
	 * @throws Exception_Unsupported
	 */
	public function table_column($table, $column = null) {
		$columns = $this->table_columns($table);
		if ($column === null) {
			return $columns;
		}
		return avalue($columns, $column);
	}
	
	/**
	 * Should be called before running queries in subclasses
	 *
	 * @param string $query
	 *        	The query
	 * @param array $options
	 *        	Various options
	 * @return mixed
	 */
	final protected function _query_before($query, array $options) {
		$this->change_database = null;
		$matches = false;
		if (!is_string($query)) {
			dump(type($query));
			backtrace(false);
		}
		if (preg_match('/^\s*[uU][sS][eE]\s+([A-Za-z]+)\s*;?$/', $query, $matches)) {
			$this->change_database = $matches[1];
		}
		if (avalue($options, 'debug') || ($this->option_bool("debug") && !$this->option_bool("log"))) {
			zesk()->logger->debug($query);
		}
		if (avalue($options, 'auto_table_names', $this->option_bool('auto_table_names'))) {
			$query = $this->auto_table_names_replace($query);
		}
		if (avalue($options, 'log', $this->option_bool("log"))) {
			$this->timer = microtime(true);
		}
		return $query;
	}
	
	/**
	 * Should be called after running queries in subclasses
	 *
	 * @param string $query        	
	 */
	final protected function _query_after($query, array $options) {
		if (avalue($options, 'log', $this->option_bool("log"))) {
			$elapsed = microtime(true) - $this->timer;
			$level = ($elapsed > $this->option_integer('slow_query_seconds', 1)) ? "warning" : "debug";
			zesk()->logger->log($level, "Elapsed: {elapsed}, SQL: {sql}", array(
				"elapsed" => $elapsed,
				"sql" => str_replace("\n", " ", $query)
			));
			$this->timer = null;
		}
		if ($this->change_database) {
			$this->Database = $this->change_database;
		}
	}
	
	/**
	 * Get lock
	 *
	 * @param string $name        	
	 * @return boolean
	 */
	abstract public function get_lock($name, $wait_seconds = 0);
	
	/**
	 * Release lock
	 *
	 * @param string $name        	
	 * @return boolean
	 */
	abstract public function release_lock($name);
	
	/**
	 * Set or get the default internal database name
	 *
	 * @param string $set        	
	 * @return string
	 */
	public static function database_default($set = null) {
		zesk()->hooks->register_class(__CLASS__);
		if ($set === null) {
			return self::$database_name_default;
		}
		$set = strtolower($set);
		self::$database_name_default = $set;
	}
	public static function unregister($name) {
		$name = strtolower($name);
		self::$database_names[$name] = null;
	}
	/**
	 * Register a database name, or get a database url
	 *
	 * @param unknown $name        	
	 * @param unknown $url        	
	 * @param string $is_default        	
	 */
	public static function register($name = null, $url = null, $is_default = false) {
		global $zesk;
		/* @var $zesk \zesk\Kernel */
		if ($name === null) {
			return self::$database_names;
		}
		$name = strtolower($name);
		if ($url === null) {
			return avalue(self::$database_names, $name);
		}
		if (!URL::valid($url)) {
			throw new Exception_Semantics("{url} is not a valid database URL ({name})", compact("name", "url"));
		}
		$url = URL::normalize($url);
		if (array_key_exists($name, self::$databases) && $url !== self::$database_names[$name]) {
			$zesk->logger->debug("Changing database url {name} {url} (old is {old})", array(
				"name" => $name,
				"url" => $url,
				"old" => self::$database_names[$name]
			));
			self::$databases[$name]->change_url($url);
		} else {
			//$zesk->logger->debug("Registering database $name $url");
		}
		self::$database_names[$name] = $url;
		if ($is_default) {
			self::database_default($name);
		}
		return $name;
	}
	
	/**
	 * Internal function to load database settings from globals
	 */
	public static function _configured(Application $application) {
		$config = $application->configuration;
		if ($config->has("table_prefix")) {
			zesk()->deprecated("Using table_prefix - no longer supported n 2017");
		}
		if ($config->has("db_url")) {
			zesk()->deprecated("Using DB_URL - no longer supported after 2016");
			$old_style = arr::kunprefix($application->configuration->to_array(), "db_url", true);
			foreach ($old_style as $name => $url) {
				$name = empty($name) ? "default" : str::unprefix($name, '_');
				self::register($name, $url);
			}
		}
		$config->deprecated("Database::database_names", __CLASS__ . "::names");
		$config->deprecated(__CLASS__ . "::database_names", __CLASS__ . "::names");
		$databases = to_array($config->path(array(
			__CLASS__,
			'names'
		)));
		foreach ($databases as $name => $database) {
			$name = strtolower($name);
			self::register($name, $database);
		}
		$database_default_config_path = array(
			__CLASS__,
			"default"
		);
		$config->deprecated("Database::default", $database_default_config_path);
		if ($config->path_exists($database_default_config_path)) {
			Database::database_default($config->path_get($database_default_config_path));
		}
	}
	
	/**
	 * Remove all single-quote-delimited strings in a series of SQL statements, taking care of
	 * backslash-quotes in strings
	 * assuming the SQL is well-formed.
	 *
	 * @todo Note, this doesn't work on arbitrary binary data if passed through, should probably
	 *       handle that case - use PDO interface
	 * @param string $sql        	
	 * @param mixed $state
	 *        	A return value to save undo information
	 * @return string SQL with strings removed
	 */
	public static function unstring($sql, &$state) {
		$unstrung = strtr($sql, array(
			"\\'" => chr(1)
		));
		$matches = null;
		if (!preg_match_all("/'[^']*'/s", $unstrung, $matches, PREG_PATTERN_ORDER)) {
			return $sql;
		}
		$state = array();
		// When $replace is a long string, say, 29000 characters or more, can not do array_flip
		// PHP has a limit on the key size, so strtr inline below
		foreach ($matches[0] as $index => $match) {
			$search = "#\$%$index%\$#";
			$replace = strtr($match, array(
				chr(1) => "\\'"
			));
			$state[$search] = $replace;
			$sql = str_replace($replace, $search, $sql);
		}
		return $sql;
	}
	
	/**
	 * Undo the "unstring" step, exactly
	 *
	 * @param string $sql        	
	 * @param mixed $state        	
	 * @return string SQL after strings are put back in
	 */
	public static function restring($sql, $state) {
		if (!is_array($state)) {
			return $sql;
		}
		return strtr($sql, $state);
	}
	
	/**
	 * Getter/setter for whether SQL is converted to use table names from class names in {} in SQL
	 *
	 * @param boolean $set        	
	 * @return boolean|self
	 */
	public function auto_table_names($set = null) {
		return ($set !== null) ? $this->set_option(self::option_auto_table_names, to_bool($set)) : $this->option_bool(self::option_auto_table_names);
	}
	
	/**
	 * Getter/setter for auto_table_names options, passed to object creation for ALL tables for
	 * table
	 *
	 * @param array $set        	
	 * @return \zesk\Database
	 */
	public function auto_table_names_options(array $set = null) {
		if ($set === null) {
			return $this->auto_table_names_options;
		}
		$this->auto_table_names_options = $set;
		return $this;
	}
	/**
	 * Convert SQL and replace table names magically.
	 *
	 * @param string $sql        	
	 * @return string SQL with table names replaced magically
	 */
	public function auto_table_names_replace($sql, array $options = array()) {
		if (is_array($sql)) {
			foreach ($sql as $k => $v) {
				$sql[$k] = self::auto_table_names_replace($v, $options);
			}
			return $sql;
		}
		$matches = false;
		$state = null;
		$sql = self::unstring($sql, $state);
		$sql = map($sql, $this->table_name_cache, true);
		if (!preg_match_all('/\{([A-Za-z][A-Za-z0-9_]*)(\*?)\}/', $sql, $matches, PREG_SET_ORDER)) {
			return self::restring($sql, $state);
		}
		$options = $options + $this->auto_table_names_options;
		$map = $this->table_name_cache;
		foreach ($matches as $match) {
			list($full_match, $class, $no_cache) = $match;
			// Possible bug: How do we NOT cache table name replacements which are parameterized?, e.g Site_5343 - table {Site} should not cache this result, right?
			// TODO
			$table = $this->application->object_table_name($class, null, $options);
			if (count($options) === 0 && $no_cache !== "*") {
				$this->table_name_cache[$full_match] = $table;
			}
			$map[$full_match] = $this->quote_table($table);
		}
		$sql = strtr($sql, $map);
		return self::restring($sql, $state);
	}
	
	/**
	 * Get/set time zone
	 *
	 * @param string $set
	 *        	Time zone to Settings
	 * @return self|string
	 * @throws Exception_Unsupported
	 */
	public function time_zone($set = null) {
		throw new Exception_Unsupported("Database {class} does not support {feature}", array(
			"class" => get_class($this),
			"feature" => self::feature_time_zone_relative_timestamp
		));
	}
	
	/**
	 *
	 * @return Database_Query_Select
	 */
	public function query_select() {
		return new Database_Query_Select($this);
	}
	
	/**
	 *
	 * @return Database_Query_Update
	 */
	public function query_update() {
		return new Database_Query_Update($this);
	}
	
	/**
	 *
	 * @return Database_Query_Insert
	 */
	public function query_insert() {
		return new Database_Query_Insert($this);
	}
	
	/**
	 *
	 * @return Database_Query_Delete
	 */
	public function query_delete() {
		return new Database_Query_Delete($this);
	}
	
	/**
	 *
	 * @return Database_Query_Union
	 */
	public function query_union() {
		return new Database_Query_Union($this);
	}
	
	/**
	 * Return the total bytes used by the database, or the bytes used by a particular table
	 *
	 * @param string $table        	
	 * @return integer
	 */
	abstract public function bytes_used($table = null);
	public function variables() {
		return $this->url_parts + array(
			"type" => $this->type(),
			"url" => $this->URL,
			"safe_url" => $this->safe_url,
			"code" => $this->code_name(),
			"code_name" => $this->code_name()
		);
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
	abstract public function column_differences(Database_Column $self, Database_Column $that, array $diffs);
}
