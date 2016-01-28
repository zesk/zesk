<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/database/mysql.inc $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */

/**
 *
 * @package zesk
 * @subpackage system
 */
abstract class Database_MySQL extends Database {
	protected $singleton_prefix = __CLASS__;

	const attribute_default_charset = "default charset";
	const attribute_character_set = "character set";
	const attribute_collation = "collate";
	const attribute_engine = "engine";

	// utf8 is the future
	const default_character_set = "utf8";

	const default_collation = "utf8_unicode_ci";

	const default_engine = "MyISAM";

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

	private static $mysql_variables = array(
		self::attribute_engine => "@@storage_engine",
		self::attribute_character_set => "@@character_set_database",
		self::attribute_collation => "@@collation_database"
	);

	private static $mysql_default_attributes = array(
		self::attribute_engine => self::default_engine,
		self::attribute_character_set => self::default_character_set,
		self::attribute_collation => self::default_engine
	);

	/**
	 * Set default table type
	 */
	function hook_construct() {
		// Is this here for backwards compatibility?
		$this->set_option("tabletype", $this->option(self::attribute_engine, $this->default_engine()));
	}
	public function remove_comments($sql) {
		$sql = text::remove_line_comments($sql, "--");
		return $sql;
	}

	private function _default_setting($attribute) {
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
		return $this->_default_setting(self::attribute_engine);
	}
	public function default_character_set() {
		return $this->_default_setting(self::attribute_character_set);
	}
	public function default_collation() {
		return $this->_default_setting(self::attribute_collation);
	}
	/**
	 * Retrieve additional column attributes which are supported by this database, in the form
	 * array("attribute1" => "default_value1")
	 * @return array
	 */
	function column_attributes(Database_Table $table) {
		return array(
			self::attribute_character_set => $table->option(self::attribute_character_set, $this->default_character_set()),
			self::attribute_collation => $table->option(self::attribute_collation, $this->default_collation())
		);
	}

	function normalize_attributes(array $attributes) {
		$newattrs = array();
		foreach ($attributes as $k => $v) {
			$k = strtoupper(preg_replace("/[-_]/", " ", strtolower($k)));
			$newattrs[$k] = $v;
		}
		return $newattrs;
	}
	/**
	 * Retrieve additional table attributes which are supported by this database, in the form
	 * array("attribute1" => "default_value1")
	 * @return array
	 */
	function table_attributes() {
		return array(
			self::attribute_engine => $this->default_engine(),
			self::attribute_default_charset => $this->option(self::attribute_default_charset, $this->default_character_set()),
			self::attribute_collation => $this->default_collation()
		);
	}

	/**
	 *
	 * @return Database_MySQL
	 * @see Database::select_database()
	 */
	public function select_database($name = null) {
		if ($name === null) {
			$name = $this->DatabaseName;
		}
		if ($this->change_database === $name) {
			return $this;
		}
		try {
			$this->query("USE " . $this->sql()->quote_table($name));
			$this->change_database = $name;
			return $this;
		} catch (Exception $e) {
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
	 * @see Database::table_columns()
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
	 *     "lock" => boolean Lock tables before dumping (avoids inconsistent state - bad for busy databases)
	 *     "tables" => list of tables Dump these tables
	 * @see Database::dump()
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
		zesk::execute_array($cmd, $parts + array(
			'filename' => $filename
		));
		return file_exists($filename);
	}

	/**
	 * Restore a database from $path using mysql command-line tool
	 *
	 * @param string $filename
	 * @param array $options
	 * @see Database::dump()
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
		zesk::execute_array($cmd, $parts + array(
			'filename' => $filename
		));
		return file_exists($filename);
	}

	/**
	 *
	 * @see Database::connect()
	 */
	final public function _connect() {
		$parts = $this->url_parts;

		$server = avalue($parts, "host");
		$port = avalue($parts, "port", null);
		$user = avalue($parts, "user");
		$password = avalue($parts, "pass");
		$this->Database = $database = substr(avalue($parts, "path"), 1);

		if ($server === "localhost") {
			$this->options["TablesCaseSensitive"] = !zesk::$is_windows;
		}
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
		return true;
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
		$class = "Database_Exception_Connect";
		if ($errno === 1049) {
			$class = "Database_Exception_Database_NotFound";
		}
		throw new $class($this->URL, $words['errno'], __("Database_MySQL:=Can not connect to {database}:{port} at {server} as {user} (MySQL Error: #{errno} {error})", $words));
	}

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
	abstract protected function _mysql_connect($server, $user, $password, $database, $port);

	/**
	 * Throw a MySQL Error
	 *
	 * @param string $query
	 * @param integer $errno
	 * @param string $message
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
			throw new Database_Exception_Duplicate($this, $query, $errno, $message);
		} else if ($errno === 1146) {
			throw new Database_Exception_Table_NotFound($this, $query, $errno, $message);
		} else {
			throw new Database_Exception_MySQL($this, $query, $errno, $message);
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
		}
		return false;
	}

	/**
	 * Create a database at URL
	 *
	 * @see Database::create_database()
	 */
	function create_database($url) {
		$parts = parse_url($url);

		$server = avalue($parts, "host");
		$user = avalue($parts, "user");
		$password = avalue($parts, "pass");
		$database = substr(avalue($parts, "path"), 1);

		$query = "CREATE DATABASE IF NOT EXISTS $database;";
		if (!$this->query($query))
			return false;
		$ip = ip::remote();
		$query = "GRANT ALL PRIVILEGES ON `$database`.* TO `$user`@`$ip` IDENTIFIED BY '" . addslashes($password) . "' WITH GRANT
OPTION;";
		if (!$this->logQuery($query))
			return false;
		$query = "GRANT ALL PRIVILEGES ON `$database`.* TO `$user`@`$server` IDENTIFIED BY '" . addslashes($password) . "' WITH GRANT
OPTION;";
		if (!$this->logQuery($query))
			return false;
		$query = "GRANT ALL PRIVILEGES ON `$database`.* TO `$user`@`localhost` IDENTIFIED BY '" . addslashes($password) . "' WITH GRANT
OPTION;";
		if (!$this->logQuery($query))
			return false;
		$query = "FLUSH
PRIVILEGES;";
		if (!$this->logQuery($query))
			return false;
		return true;
	}

	/**
	 * List tables
	 *
	 * @see Database::list_tables()
	 * @return array
	 */
	function list_tables() {
		$result = $this->query("SHOW TABLES");
		$tables = array();
		$caseSensitive = $this->tablesCaseSensitive();
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
	private function sql_get_create_table($table) {
		$sql = "SHOW CREATE TABLE `$table`";
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
	 * @param unknown $table
	 * @return boolean Options
	 */
	function table_information($table) {
		$result = $this->query("SHOW TABLE STATUS LIKE '$table'");
		$arr = $this->fetch_assoc($result);
		if (!$arr) {
			return false;
		}
		$this->free($result);
		return new Options(array(
			"Type" => avalue($arr, 'Engine', avalue($arr, 'Type', "Unknown")),
			"RowCount" => $arr['Rows'],
			"Size" => $arr['Data_length'],
			"EmptySize" => $arr['Data_free'],
			"Created" => $this->sql_format_datetime($arr['Create_time']),
			"Updated" => $this->sql_format_datetime($arr['Update_time'])
		));
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Database::database_table()
	 */
	function database_table($table) {
		$result = $this->query_array("SHOW CREATE TABLE `" . $table . "`", null, "Create Table", null);
		if (count($result) > 0) {
			$sql = $result[0];
			return $this->parse_create_table($sql);
		}
		return null;
	}

	/*
	 * String Manipulation
	 */
	function sql_format_string($sql) {
		return "'" . addslashes($sql) . "'";
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Database::basic_types_compatible()
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
	 *
	 * @return boolean
	 */
	abstract function has_innodb();

	/**
	 * (non-PHPdoc)
	 *
	 * @see Database::default_index_structure()
	 */
	public function default_index_structure($table_type) {
		switch (strtolower($table_type)) {
			case "innodb":
				return "BTREE";
			case "memory":
			case "heap":
				return "HASH";
			default:
			case "myisam":
				return "BTREE";
		}
	}

	/**
	 * Figure out how many rows a query will hit
	 *
	 * @param unknown $sql
	 * @throws Database_Exception_MySQL
	 * @return integer
	 */
	function estimate_rows($sql) {
		$result = $this->query("EXPLAIN $sql");
		$n = 1;
		if (!is_resource($result)) {
			throw new Database_Exception_MySQL($this, "Explain failed", "EXPLAIN $sql");
		}
		while (($arr = $this->fetch_array($result)) != false) {
			$x = avalue($arr, "rows");
			if (!empty($x)) {
				$n *= $x;
			}
		}
		$this->free($result);
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
	 * @see Database::shell_command()
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
			log::error("Released lock {name} FAILED (raw_result={raw_result}): ", array(
				"name" => $name,
				"backtrace" => debug_backtrace(false),
				"raw_result" => php::dump($result)
			));
			return false;
		}
		return true;
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Database::is_reserved_word()
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
			"IGNORE",
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
		} catch (Exception $e) {
			throw new Database_Exception_Permission("Unable to set global {name}", array(
				"name" => $name
			));
		}
	}
	public function feature($feature, $set = null) {
		switch ($feature) {
			case self::feature_max_blob_size:
				return to_integer($this->_variable('max_allowed_packet', $set === null ? null : intval($set)), null);
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
}
