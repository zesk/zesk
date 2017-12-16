<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/mysql/classes/Database.php $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */
namespace MySQL;

use zesk\Exception_Semantics;
use zesk\Exception_NotFound;
use zesk\Exception_File_NotFound;
use zesk\Database_Exception_Connect;
use zesk\Database_Exception_Database_NotFound;
use zesk\Database_Exception_Permission;
use zesk\Database_Exception_Duplicate;
use zesk\Database_Exception_Table_NotFound;
use zesk\Database_Exception_SQL;
use zesk\Database_Table;
use zesk\Database_Column;
use zesk\arr;
use zesk\Text;
use zesk\PHP;
use zesk\Kernel;
use zesk\Timestamp;
use zesk\Exception_Parameter;
use zesk\Database_Exception;
use phpDocumentor\Reflection\Types\Null_;

/**
 *
 * @package zesk
 * @subpackage system
 */
class Database extends \zesk\Database {
	/**
	 *
	 * @var string
	 */
	protected $singleton_prefix = __CLASS__;

	/**
	 * Should we reconnect automatically if we are disconnected?
	 *
	 * @var boolean
	 */
	protected $auto_reconnect = false;

	/**
	 * Database connection
	 *
	 * @var mysqli
	 */
	protected $Connection = null;

	/**
	 *
	 * @var string
	 */
	const attribute_default_charset = "default charset";

	/**
	 *
	 * @var string
	 */
	const attribute_character_set = "character set";

	/**
	 *
	 * @var string
	 */
	const attribute_collation = "collate";

	/**
	 * Current MySQL version
	 *
	 * @var string
	 */
	const attribute_version = "version";

	/**
	 *
	 * @var string
	 */
	const attribute_engine = "engine";

	// utf8 is the future
	//
	// After MySQL 5.5.2 use:
	// ALTER DATABASE databasename CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
	// ALTER TABLE tablename CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
	//
	// Before or using MySQL 5.5.2 use:
	// ALTER DATABASE databasename CHARACTER SET utf8 COLLATE utf8_unicode_ci;
	// ALTER TABLE tablename CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;

	/**
	 *
	 * @var string
	 */
	const default_character_set = "utf8";
	/**
	 *
	 * @var string
	 */
	const default_collation = "utf8_unicode_ci";

	/**
	 *
	 * @var string
	 */
	const default_engine = "InnoDB";

	/**
	 * Current selected database
	 *
	 * @var string
	 */
	private $change_database = null;

	/*
	 * Set to empty array to have it refreshed from the database
	 *
	 * @var string
	 */
	protected $default_settings = array();

	/**
	 *
	 * @var string
	 */
	private static $mysql_variables = array(
		self::attribute_engine => "@@default_storage_engine",
		self::attribute_character_set => "@@character_set_database",
		self::attribute_collation => "@@collation_database",
		self::attribute_version => "@@version"
	);
	private static $mysql_default_attributes = array(
		self::attribute_engine => self::default_engine,
		self::attribute_character_set => self::default_character_set,
		self::attribute_collation => self::default_engine
	);

	/**
	 * Register schemes this class support
	 */
	public static function hooks(Kernel $zesk) {
		// Kernel should find some way to store this registration data instead of per-class
		// TODO
		\zesk\Database::register_scheme("mysql", __CLASS__);
		\zesk\Database::register_scheme("mysqli", __CLASS__);
	}
	/**
	 * Set default table type
	 */
	function hook_construct() {
		$this->Connection = null;
		// Is this here for backwards compatibility?
		$this->set_option("tabletype", $this->option(self::attribute_engine, $this->default_engine()));
	}

	/**
	 * Remove comments from a block of SQL statements
	 *
	 * @param string $sql
	 * @return string
	 */
	public function remove_comments($sql) {
		$sql = Text::remove_line_comments($sql, "--");
		return $sql;
	}

	/**
	 * Retrieve a database setting and store it locally as an option
	 *
	 * @param unknown $attribute
	 * @throws Exception_Semantics
	 * @return mixed|string|array|string
	 */
	private function _fetch_setting($attribute) {
		if ($this->has_option($attribute)) {
			return $this->option($attribute);
		}
		if (!array_key_exists($attribute, self::$mysql_variables)) {
			throw new Exception_Semantics("No such MySQL variable for attribute {attribute}", compact("attribute"));
		}
		$variable = self::$mysql_variables[$attribute];
		$this->set_option($attribute, $value = $this->query_one("select $variable", $variable, avalue(self::$mysql_default_attributes, $attribute, null)));
		return $value;
	}
	public function default_engine() {
		return $this->_fetch_setting(self::attribute_engine);
	}
	public function default_character_set() {
		return $this->_fetch_setting(self::attribute_character_set);
	}
	public function default_collation() {
		return $this->_fetch_setting(self::attribute_collation);
	}
	/**
	 * Retrieve additional column attributes which are supported by this database, in the form
	 * array("attribute1" => "default_value1")
	 *
	 * @return array
	 */
	function column_attributes(Database_Column $column) {
		$attributes = array();
		if ($column->sql_type() === "timestamp") {
			if ($column->not_null()) {
				$attributes['default'] = 'CURRENT_TIMESTAMP';
			} else {
				$attributes['default'] = null;
			}
		}
		$sql_type = $column->sql_type();
		if (ends($sql_type, "blob") || ends($sql_type, "text")) {
			if ($column->not_null()) {
				$attributes['default'] = '';
			} else {
				$attributes['default'] = null;
			}
		}
		$attributes['extra'] = null;
		$table = $column->table();
		return $attributes + array(
			self::attribute_character_set => $table->option(self::attribute_character_set, $this->default_character_set()),
			self::attribute_collation => $table->option(self::attribute_collation, $this->default_collation())
		);
	}

	/**
	 * Handle database-specific differences between two columns
	 *
	 * @param Database_Column $self
	 * @param Database_Column $that
	 * @param array $diffs
	 */
	function column_differences(Database_Column $self, Database_Column $that, array $diffs) {
		if ($self->is_text()) {
			return $diffs + $self->attributes_differences($this, $that, array(
				self::attribute_character_set,
				self::attribute_collation
			));
		}
		return array();
	}

	/**
	 *
	 * @param array $attributes
	 * @return unknown[]
	 */
	function normalize_attributes(array $attributes) {
		$newattrs = array();
		foreach ($attributes as $k => $v) {
			$k = strtolower(preg_replace("/[-_]/", " ", strtolower($k)));
			$newattrs[$k] = $v;
		}
		return $newattrs;
	}
	/**
	 * Retrieve additional table attributes which are supported by this database, in the form
	 * array("attribute1" => "default_value1")
	 *
	 * @return array
	 */
	function table_attributes() {
		return array(
			self::attribute_engine => $this->option(self::attribute_engine, $this->default_engine()),
			self::attribute_default_charset => $this->option(self::attribute_default_charset, $this->default_character_set()),
			self::attribute_collation => $this->option(self::attribute_collation, $this->default_collation())
		);
	}

	/**
	 *
	 * @return Database
	 * @see zesk\Database::select_database()
	 */
	public function select_database($name = null) {
		if ($name === null) {
			$name = $this->database_name();
		}
		if ($this->change_database === $name) {
			return $this;
		}
		try {
			$this->query("USE " . $this->sql()->quote_table($name));
			$this->change_database = $name;
			return $this;
		} catch (\Exception $e) {
			throw $e;
		}
	}
	function mixed_query($sql) {
		if (is_array($sql)) {
			$result = array();
			foreach ($sql as $k => $q) {
				$q = trim($q);
				if (empty($q))
					continue;
				$result[$k] = $this->query($q);
			}
			return $result;
		}
		return $this->query($sql);
	}

	/**
	 * Retrieve the table's column definitions
	 *
	 * @see zesk\Database::table_columns()
	 */
	function table_columns($table) {
		$columns = array();
		$tobject = new Database_Table($this, $table);
		$result = $this->query_array("DESC " . $this->quote_table($table), "Field");
		foreach ($result as $name => $result) {
			$Type = $Null = $Key = $Default = $Extra = null;
			extract($result, EXTR_IF_EXISTS);
			$columns[$name] = $col = new Database_Column($tobject, $name);
			$col->sql_type($Type);
			$col->increment(strpos($Extra, "auto_increment") !== false);
			$col->default_value($Default);
			$col->not_null(!to_bool($Null));
		}
		return $columns;
	}

	/**
	 * Dump a database to $path using mysqldump
	 *
	 * @param string $filename
	 * @param array $options
	 *        	"lock" => boolean Lock tables before dumping (avoids inconsistent state - bad for
	 *        	busy databases)
	 *        	"tables" => list of tables Dump these tables
	 * @see zesk\Database::dump()
	 */
	function dump($filename, array $options = array()) {
		$parts = $this->url_parts;

		$parts['port'] = avalue($parts, "port", 3306);
		$database = $this->database_name();

		$tables = to_list(avalue($options, 'tables', array()));

		$cmd_options = array(
			"--add-drop-table",
			"-c",
			"--host={host}",
			"--port={port}",
			"--password={pass}",
			"--user={user}"
		);
		$lock_first = avalue($options, 'lock', false);
		if ($lock_first) {
			$cmd_options[] = "--lock-tables";
		}
		$cmd_options[] = $database;
		$cmd_options[] = implode(" ", $tables);
		$result = 0;
		$cmd = "mysqldump " . implode(" ", $cmd_options) . " > {filename}";
		$this->application->process->execute_arguments($cmd, $parts + array(
			'filename' => $filename
		));
		return file_exists($filename);
	}

	/**
	 * Restore a database from $path using mysql command-line tool
	 *
	 * @param string $filename
	 * @param array $options
	 * @see zesk\Database::dump()
	 */
	function restore($filename, array $options = array()) {
		if (!file_exists($filename)) {
			throw new Exception_File_NotFound($filename);
		}
		$parts = $this->url_parts;

		$parts['port'] = avalue($parts, "port", 3306);
		$database = $this->database_name();

		$cmd_options = array(
			"--host={host}",
			"--port={port}",
			"--password={pass}",
			"--user={user}"
		);
		$cmd_options[] = $database;
		$result = 0;
		$cmd = "mysql " . implode(" ", $cmd_options) . " < {filename}";
		$this->application->process->execute_arguments($cmd, $parts + array(
			'filename' => $filename
		));
		return file_exists($filename);
	}

	/**
	 *
	 * @see zesk\Database::connect()
	 */
	final public function _connect() {
		$parts = $this->url_parts;

		$server = avalue($parts, "host");
		$port = avalue($parts, "port", null);
		$user = avalue($parts, "user");
		$password = avalue($parts, "pass");
		$this->Database = $database = substr(avalue($parts, "path"), 1);

		if (!$port) {
			$port = 3306;
		}
		if (!$this->_mysql_connect($server, $user, $password, $database, $port)) {
			return false;
		}

		$this->set_option("Database", $this->Database);
		$this->set_option("User", $user);
		$this->set_option("Port", $port);
		$this->set_option("Server", $server);

		$character_set = $this->option(self::attribute_character_set, self::default_character_set);
		if ($character_set) {
			$sql = "SET NAMES '$character_set'";
			$collate = $this->option(self::attribute_collation, self::default_collation);
			if ($collate) {
				$sql .= " COLLATE '$collate'";
			}
			$this->query($sql);
		}
		$this->version_settings();
		return true;
	}
	private function version_settings() {
		$this->set_option(self::attribute_version, null);
		$this->_fetch_setting(self::attribute_version);

		$version = $this->version;
		if (preg_match('/([0-9]+)\.([0-9]+)\.([0-9]+)/', $version, $matches)) {
			list($v, $major, $minor, $patch) = $matches;
			if ($major !== "5") {
				$this->application->logger->warning("Unknown major version of MySQL (We know MySQL 5 only): {version}", array(
					"version" => $version
				));
			}
			if ($minor <= 6) {
				$this->set_option("invalid_dates_ok", true);
			}
		}
	}

	/**
	 * Connection error
	 *
	 * @param array $words
	 *        	Tokens for error message
	 * @throws Database_Exception_Connect
	 */
	protected function _connection_error(array $words) {
		if (!array_key_exists('error', $words)) {
			$words['error'] = mysqli_error();
		}
		if (!array_key_exists('errno', $words)) {
			$words['errno'] = mysqli_errno();
		}
		$errno = intval($words['errno']);
		if ($errno === 1049) {
			throw new Database_Exception_Database_NotFound($this->URL, __("mysql\Database:=Can not connect to {database} at {server}:{port} as {user} (MySQL Error: #{errno} {error})"), $words, $words['errno']);
		}
		throw new Database_Exception_Connect($this->URL, __("mysql\Database:=Can not connect to {database} at {server}:{port} as {user} (MySQL Error: #{errno} {error})"), $words, $words['errno']);
	}

	/**
	 * Throw a MySQL Error
	 *
	 * @param string $query
	 * @param integer $errno
	 * @param string $message
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_Table_NotFound
	 */
	protected function _mysql_throw_error($query, $errno, $message) {
		if ($errno == 1062) {
			$match = false;
			if (preg_match("/key ([0-9]+)/", $message, $match)) {
				$match = intval($match[1]);
			}
			if (empty($match)) {
				$match = -1;
			} else {
				$match -= 1;
			}
			throw new Database_Exception_Duplicate($this, $query, $message, array(), $errno);
		} else if ($errno === 1146) {
			throw new Database_Exception_Table_NotFound($this, $query, $message, array(), $errno);
		} else {
			throw new Database_Exception_SQL($this, $query, $message, array(), $errno);
		}
	}
	/*
	 * Database capabilities
	 */
	function can($feature) {
		switch ($feature) {
			case self::feature_create_database:
				return true;
			case self::feature_list_tables:
				return true;
			case self::feature_time_zone_relative_timestamp:
				return true;
		}
		return false;
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
		if ($set === null) {
			return $this->query_one("SELECT @@time_zone as tz", "tz", "UTC");
		}
		$this->query("SET time_zone=" . $this->quote_text($set));
		return $this;
	}

	/**
	 * Create a database at URL
	 *
	 * @see zesk\Database::create_database()
	 */
	function create_database($url, array $hosts) {
		$parts = parse_url($url);

		$server = avalue($parts, "host");
		$user = avalue($parts, "user");
		$password = avalue($parts, "pass");
		$database = substr(avalue($parts, "path"), 1);

		$query = "CREATE DATABASE IF NOT EXISTS $database;";
		if (!$this->query($query)) {
			return false;
		}
		foreach ($hosts as $host) {
			$query = "GRANT ALL PRIVILEGES ON `$database`.* TO `$user`@`$host` IDENTIFIED BY '" . addslashes($password) . "' WITH GRANT OPTION;";
			if (!$this->query($query)) {
				return false;
			}
		}
		$query = "FLUSH PRIVILEGES;";
		if (!$this->query($query)) {
			return false;
		}
		return true;
	}

	/**
	 * List tables
	 *
	 * @see zesk\Database::list_tables()
	 * @return array
	 */
	function list_tables() {
		$result = $this->query("SHOW TABLES");
		$tables = array();
		$caseSensitive = $this->tables_case_sensitive();
		if ($caseSensitive) {
			while (($arr = $this->fetch_array($result)) != false) {
				$tables[$arr[0]] = $arr[0];
			}
		} else {
			while (($arr = $this->fetch_array($result)) != false) {
				$tables[strtolower($arr[0])] = $arr[0];
			}
		}
		return $tables;
	}

	/**
	 *
	 * @param string $table
	 * @param string $sql
	 *        	SQL used to show the table
	 * @return boolean|string
	 */
	private function show_create_table($table, &$sql = null) {
		$sql = "SHOW CREATE TABLE " . $this->quote_table($table);
		$result = $this->query($sql);
		$row = $this->fetch_array($result);
		if (count($row) === 0) {
			return false;
		}
		$data = $row[1];
		$this->free($result);
		return $data;
	}

	/**
	 *
	 * @param string $table
	 * @todo Move into type\Database_Table
	 * @return array|null
	 */
	final function table_information($table) {
		$result = $this->query("SHOW TABLE STATUS LIKE '$table'");
		$arr = $this->fetch_assoc($result);
		if (!$arr) {
			return null;
		}
		$this->free($result);
		return array(
			self::TABLE_INFO_ENGINE => avalue($arr, 'Engine', avalue($arr, 'Type', null)),
			self::TABLE_INFO_ROW_COUNT => $arr['Rows'],
			self::TABLE_INFO_DATA_SIZE => $arr['Data_length'],
			self::TABLE_INFO_INDEX_SIZE => $arr['Index_length'],
			self::TABLE_INFO_FREE_SIZE => $arr['Data_free'],
			self::TABLE_INFO_CREATED => $arr['Create_time'] ? Timestamp::factory($arr['Create_time']) : null,
			self::TABLE_INFO_UPDATED => $arr['Update_time'] ? Timestamp::factory($arr['Update_time']) : null
		);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see zesk\Database::database_table()
	 */
	function database_table($table) {
		$source = null;
		$sql = $this->show_create_table($table, $source);
		if ($sql) {
			return $this->parse_create_table($sql, $source);
		}
		return null;
	}

	/*
	 * String Manipulation
	 * @deprecated 2017-08
	 */
	function sql_format_string($sql) {
		zesk()->deprecated();
		return "'" . addslashes($sql) . "'";
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see zesk\Database::basic_types_compatible()
	 */
	final protected function basic_types_compatible($t0, $t1) {
		if ($t0 === $t1) {
			return true;
		}
		static $map = array(
			"float" => "double",
			"integer" => "double",
			"boolean" => "integer"
		);
		return avalue($map, $t0, $t0) === avalue($map, $t1, $t1);
	}

	/*
	 * Boolean Type
	 */
	function sql_parse_boolean($value) {
		return $value ? "'true'" : "'false'";
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see zesk\Database::default_index_structure()
	 */
	public function default_index_structure($table_type) {
		switch (strtolower($table_type)) {
			case "innodb":
				return "BTREE";
			case "memory":
			case "heap":
				return "HASH";
			default :
			case "myisam":
				return "BTREE";
		}
	}

	/**
	 * Figure out how many rows a query will hit
	 *
	 * @param unknown $sql
	 * @throws Database_Exception
	 * @return integer
	 */
	function estimate_rows($sql) {
		$rows = $this->query_array("EXPLAIN $sql");
		$n = 1;
		foreach ($rows as $row) {
			$x = avalue($row, "rows");
			if (!empty($x)) {
				$n *= $x;
			}
		}
		return $n;
	}

	/**
	 * MySQL parse RENAME TABLE syntax
	 *
	 * Where is this used?
	 *
	 * @param string $sql
	 * @return NULL
	 */
	function parse_rename_table($sql) {
		$sql = preg_replace('/\s+/', ' ', rtrim(trim($sql), ';'));
		if (preg_match("/RENAME TABLE (`[^`]+`|[^` ]+) TO (`[^`]+`|[^` ]+)/i", $sql, $matches)) {
			dump($matches);
			exit(1);
		}
		return null;
	}
	function table_exists($table) {
		if (empty($table)) {
			return false;
		}
		$result = $this->query_array("SHOW TABLES LIKE " . $this->quote_text($table));
		return (count($result) !== 0);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see zesk\Database::shell_command()
	 */
	function shell_command(array $options = array()) {
		$parts = $this->url_parts;
		$scheme = $host = $user = $pass = $path = null;
		extract($parts, EXTR_IF_EXISTS);
		$args = array();
		if ($host) {
			$args[] = "-h";
			$args[] = $host;
		}
		if ($user) {
			$args[] = "-u";
			$args[] = $user;
		}
		if ($pass) {
			$args[] = "-p" . $pass;
		}
		if (to_bool(avalue($options, 'force'))) {
			$args[] = "-f";
		}
		$path = substr($path, 1);
		$args[] = $path;

		return array(
			"mysql",
			$args
		);
	}

	/**
	 * MySQL locking
	 *
	 * @param unknown $name
	 * @param number $wait_seconds
	 * @return boolean
	 */
	public function get_lock($name, $wait_seconds = 0) {
		$wait_seconds = intval($wait_seconds);
		$name = $this->quote_text($name);
		$result = $this->query_integer("SELECT GET_LOCK($name, $wait_seconds) AS X", "X", 0) === 1;
		return $result;
	}

	/**
	 * MySQL Release lock
	 *
	 * @param unknown $name
	 * @return boolean
	 */
	public function release_lock($name) {
		$name = $this->quote_text($name);
		$result = $this->query_integer("SELECT RELEASE_LOCK($name) AS X", "X", null);
		if ($result !== 1) {
			$this->application->logger->error("Released lock {name} FAILED (raw_result={raw_result}): ", array(
				"name" => $name,
				"backtrace" => debug_backtrace(false),
				"raw_result" => PHP::dump($result)
			));
			return false;
		}
		return true;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see zesk\Database::is_reserved_word()
	 */
	function is_reserved_word($word) {
		// Updated 2004-10-19 from MySQL Website YEARLY-TODO
		static $reserved = array(
			"ADD",
			"ALL",
			"ALTER",
			"ANALYZE",
			"AND",
			"AS",
			"ASC",
			"ASENSITIVE",
			"BEFORE",
			"BETWEEN",
			"BIGINT",
			"BINARY",
			"BLOB",
			"BOTH",
			"BY",
			"CALL",
			"CASCADE",
			"CASE",
			"CHANGE",
			"CHAR",
			"CHARACTER",
			"CHECK",
			"COLLATE",
			"COLUMN",
			"COLUMNS",
			"CONDITION",
			"CONNECTION",
			"CONSTRAINT",
			"CONTINUE",
			"CONVERT",
			"CREATE",
			"CROSS",
			"CURRENT_DATE",
			"CURRENT_TIME",
			"CURRENT_TIMESTAMP",
			"CURRENT_USER",
			"CURSOR",
			"DATABASE",
			"DATABASES",
			"DAY_HOUR",
			"DAY_MICROSECOND",
			"DAY_MINUTE",
			"DAY_SECOND",
			"DEC",
			"DECIMAL",
			"DECLARE",
			"DEFAULT",
			"DELAYED",
			"DELETE",
			"DESC",
			"DESCRIBE",
			"DETERMINISTIC",
			"DISTINCT",
			"DISTINCTROW",
			"DIV",
			"DOUBLE",
			"DROP",
			"DUAL",
			"EACH",
			"ELSE",
			"ELSEIF",
			"ENCLOSED",
			"ESCAPED",
			"EXISTS",
			"EXIT",
			"EXPLAIN",
			"FALSE",
			"FETCH",
			"FIELDS",
			"FLOAT",
			"FOR",
			"FORCE",
			"FOREIGN",
			"FOUND",
			"FROM",
			"FULLTEXT",
			"GOTO",
			"GRANT",
			"GROUP",
			"HAVING",
			"HIGH_PRIORITY",
			"HOUR_MICROSECOND",
			"HOUR_MINUTE",
			"HOUR_SECOND",
			"IF",
			// WL #7395			"IGNORE",
			"IN",
			"INDEX",
			"INFILE",
			"INNER",
			"INOUT",
			"INSENSITIVE",
			"INSERT",
			"INT",
			"INTEGER",
			"INTERVAL",
			"INTO",
			"IS",
			"ITERATE",
			"JOIN",
			"KEY",
			"KEYS",
			"KILL",
			"LEADING",
			"LEAVE",
			"LEFT",
			"LIKE",
			"LIMIT",
			"LINES",
			"LOAD",
			"LOCALTIME",
			"LOCALTIMESTAMP",
			"LOCK",
			"LONG",
			"LONGBLOB",
			"LONGTEXT",
			"LOOP",
			"LOW_PRIORITY",
			"MATCH",
			"MEDIUMBLOB",
			"MEDIUMINT",
			"MEDIUMTEXT",
			"MIDDLEINT",
			"MINUTE_MICROSECOND",
			"MINUTE_SECOND",
			"MOD",
			"NATURAL",
			"NOT",
			"NO_WRITE_TO_BINLOG",
			"NULL",
			"NUMERIC",
			"ON",
			"OPTIMIZE",
			"OPTION",
			"OPTIONALLY",
			"OR",
			"ORDER",
			"OUT",
			"OUTER",
			"OUTFILE",
			"PRECISION",
			"PRIMARY",
			"PRIVILEGES",
			"PROCEDURE",
			"PURGE",
			"READ",
			"REAL",
			"REFERENCES",
			"REGEXP",
			"RENAME",
			"REPEAT",
			"REPLACE",
			"REQUIRE",
			"RESTRICT",
			"RETURN",
			"REVOKE",
			"RIGHT",
			"RLIKE",
			"SCHEMA",
			"SCHEMAS",
			"SECOND_MICROSECOND",
			"SELECT",
			"SENSITIVE",
			"SEPARATOR",
			"SET",
			"SHOW",
			"SMALLINT",
			"SONAME",
			"SPATIAL",
			"SPECIFIC",
			"SQL",
			"SQLEXCEPTION",
			"SQLSTATE",
			"SQLWARNING",
			"SQL_BIG_RESULT",
			"SQL_CALC_FOUND_ROWS",
			"SQL_SMALL_RESULT",
			"SSL",
			"STARTING",
			"STRAIGHT_JOIN",
			"TABLE",
			"TABLES",
			"TERMINATED",
			"THEN",
			"TINYBLOB",
			"TINYINT",
			"TINYTEXT",
			"TO",
			"TRAILING",
			"TRIGGER",
			"TRUE",
			"UNDO",
			"UNION",
			"UNIQUE",
			"UNLOCK",
			"UNSIGNED",
			"UPDATE",
			"USAGE",
			"USE",
			"USING",
			"UTC_DATE",
			"UTC_TIME",
			"UTC_TIMESTAMP",
			"VALUES",
			"VARBINARY",
			"VARCHAR",
			"VARCHARACTER",
			"VARYING",
			"WHEN",
			"WHERE",
			"WHILE",
			"WITH",
			"WRITE",
			"XOR",
			"YEAR_MONTH",
			"ZEROFILL"
		);
		$word = strtoupper($word);
		return in_array($word, $reserved);
	}
	private function _variable($name, $set = null) {
		if ($set === null) {
			return $this->query_one("SHOW VARIABLES LIKE " . $this->quote_text($name), "Value", null);
		}
		try {
			$this->query("SET GLOBAL $name=" . $this->quote_text($set));
		} catch (\Exception $e) {
			throw new Database_Exception_Permission("Unable to set global {name}", array(
				"name" => $name
			));
		}
	}
	public function feature($feature, $set = null) {
		switch ($feature) {
			case self::feature_max_blob_size:
				return to_integer($this->_variable('max_allowed_packet', $set === null ? null : intval($set)), null);
			case self::feature_cross_database_queries:
				return true;
		}
		throw new Exception_NotFound("Feature {feature} not available in database {name}", array(
			"feature" => $feature,
			"name
" => $this->type()
		));
	}
	public function bytes_used($table = null) {
		if ($table !== null) {
			if (!$this->table_exists($table)) {
				throw new Database_Exception_Table_NotFound($this, $table);
			}
			return self::query_one("SHOW TABLE STATUS LIKE '$table'", 'Data_length', 0);
		} else {
			$total = 0;
			foreach (self::query_array("SHOW TABLE STATUS", null, "Data_length") as $data_length) {
				$total += $data_length;
			}
			return $total;
		}
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see zesk\Database::connection()
	 */
	final public function connection() {
		return $this->Connection;
	}

	// 		MYSQLI_CLIENT_COMPRESS	    Use compression protocol
	// 		MYSQLI_CLIENT_FOUND_ROWS	return number of matched rows, not the number of affected rows
	// 		MYSQLI_CLIENT_IGNORE_SPACE	Allow spaces after function names. Makes all function names reserved words.
	// 		MYSQLI_CLIENT_INTERACTIVE	Allow interactive_timeout seconds (instead of wait_timeout seconds) of inactivity before closing the connection
	// 		MYSQLI_CLIENT_SSL           Use SSL
	private static $flag_map = array(
		'compress' => MYSQLI_CLIENT_COMPRESS,
		'found rows' => MYSQLI_CLIENT_FOUND_ROWS,
		'ignore space' => MYSQLI_CLIENT_IGNORE_SPACE,
		'interactive' => MYSQLI_CLIENT_INTERACTIVE,
		'ssl' => MYSQLI_CLIENT_SSL
	);

	/**
	 * Internal connection method
	 *
	 * @param string $server
	 * @param string $user
	 * @param string $password
	 * @param string $database
	 * @param integer $port
	 * @return resource
	 */
	final protected function _mysql_connect($server, $user, $password, $database, $port) {
		$conn = mysqli_init(); //@new mysqli($server, $user, $password, $database, $port);
		if ($this->option_bool("infile")) {
			mysqli_options($conn, MYSQLI_OPT_LOCAL_INFILE, true);
		}
		mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, $this->option_integer('connect_timeout', 5));
		$flags = $this->option("connect_flags", 0);
		if (is_numeric($flags)) {
			$flags = intval($flags);
		} else if (is_string($flags) || is_array($flags)) {
			$flag_tokens = arr::change_value_case(to_list($flags));
			$flags = 0;
			foreach ($flag_tokens as $token) {
				if (!array_key_exists($token, self::$flag_map)) {
					$this->application->logger->warning("Unknown flag {token} in {method}: possible flags are {flags}", array(
						"method" => __METHOD__,
						"token" => $token,
						"flags" => array_keys(self::$flag_map)
					));
				} else {
					$flags |= self::$flag_map[$token];
				}
			}
		} else {
			$this->application->logger->warning("Unknown connect_flags option value type passed to {method} {type}", array(
				"method" => __METHOD__,
				"type" => gettype($flags)
			));
			$flags = 0;
		}
		if (!@mysqli_real_connect($conn, $server, $user, $password, $database, $port, null, $flags)) {
			$error = mysqli_connect_error();
			if ($error) {
				$this->_connection_error(compact("database", "server", "user", "port") + array(
					"error" => $error,
					"errno" => mysqli_connect_errno()
				));
			}
			$this->application->logger->error("Connection to database $user@$server:$port/$database FAILED, no connection error");
			return false;
		}
		$this->Connection = $conn;
		return true;
	}
	public function auto_reconnect($set = null) {
		if ($set === null) {
			return $this->auto_reconnect;
		}
		$this->auto_reconnect = to_bool($set);
		return $this;
	}
	public final function disconnect() {
		parent::disconnect();
		if ($this->Connection) {
			mysqli_close($this->Connection);
			$this->Connection = null;
		}
	}

	/**
	 * Main query entry point
	 *
	 * {@inheritdoc}
	 *
	 * @see \zesk\Database::query()
	 */
	public final function query($query, array $options = array()) {
		if (empty($query)) {
			return null;
		}
		if (is_array($query)) {
			$result = array();
			foreach ($query as $index => $sql) {
				$result[$index] = $this->query($sql, $options);
			}
			return $result;
		}
		if (!$this->Connection && avalue($options, 'auto_connect', $this->option_bool("auto_connect", true))) {
			$this->connect();
		}
		if (!$this->Connection) {
			throw new Database_Exception_Connect($this->URL, "Not connected to database {safe_url} when attempting query: {sql}", array(
				"sql" => $query,
				"safe_url" => $this->safe_url("")
			));
		}
		$tries = 0;
		do {
			$query = $this->_query_before($query, $options);
			$result = @mysqli_query($this->Connection, $query);
			$this->_query_after($query, $options);
			if ($result) {
				return $result;
			}
			$message = mysqli_error($this->Connection);
			$errno = mysqli_errno($this->Connection);
			if ($errno === 2006 && $this->auto_reconnect) /* CR_SERVER_GONE_ERROR */ {
				$this->application->logger->warning("Reconnecting to database {url}", array(
					"url" => $this->safe_url()
				));
				$this->reconnect();
			} else {
				break;
			}
		} while (++$tries < 10);
		$this->_mysql_throw_error($query, $errno, $message);
	}
	public final function affected_rows($result = null) {
		if (is_resource($result)) {
			return mysqli_num_rows($result);
		}
		return mysqli_affected_rows($this->Connection);
	}
	public final function free($result) {
		if (empty($result)) {
			return;
		}
		mysqli_free_result($result);
	}
	public final function insert_id() {
		$id = mysqli_insert_id($this->Connection);
		if ($id == 0)
			return false;
		return $id;
	}
	public final function fetch_assoc($result) {
		if (!$result instanceof \mysqli_result) {
			throw new Exception_Parameter("{method} requires first parameter to be {class}", array(
				"method" => __METHOD__,
				"class" => "mysqli_result"
			));
		}
		return mysqli_fetch_assoc($result);
	}
	public final function fetch_array($result) {
		if (!$result instanceof \mysqli_result) {
			throw new Exception_Parameter("{method} requires first parameter to be {class}", array(
				"method" => __METHOD__,
				"class" => "mysqli_result"
			));
		}
		return mysqli_fetch_array($result, MYSQLI_NUM);
	}
	public final function native_quote_text($value) {
		if (!is_string($value)) {
			throw new Exception_Parameter("Incorrect type passed, string required: {type}", array(
				"type" => type($value)
			));
		}
		if (!$this->Connection) {
			// Usually means the database is down
			return "'" . addslashes($value) . "'";
		}
		return "'" . mysqli_real_escape_string($this->Connection, $value) . "'";
	}
	public final function has_innodb() {
		$ver = mysqli_get_server_info($this->Connection);
		if (strpos($ver, "4.0") !== false) {
			return true;
		}
		return false;
	}

	/**
	 * Begin a transaction in the database
	 *
	 * @return boolean
	 */
	function transaction_start() {
		// TODO: Ensure database is in auto-commit mode
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
		$sql = $success ? "COMMIT" : "ROLLBACK";
		return $this->query($sql);
	}
}
