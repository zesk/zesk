<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage sqlite3
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 * @author kent
 */

namespace sqlite3;

// PHP classes
use \Exception as Exception;
use \SQLite3 as SQLite3;
use \SQLite3Result as SQLite3Result;
use \SQLite3Stmt as SQLite3Stmt;

// Zesk classes
use zesk\Exception_Parameter;
use zesk\Exception_Unimplemented;
use zesk\Exception_Configuration;
use zesk\Exception_Directory_NotFound;
use zesk\Exception_Semantics;
use zesk\Database_Table;
use zesk\Database_Index;
use zesk\Database_Exception;
use zesk\Database_Exception_Table_NotFound;
use zesk\Database_Exception_Connect;
use zesk\PHP;
use zesk\Directory;
use zesk\Timer;
use zesk\Date;
use zesk\Time;
use zesk\Timestamp;
use zesk\dir;
use zesk\File;
use zesk\ArrayTools;
use zesk\Database_Column;

/**
 * SQLite Implementation
 *
 * @author kent
 *
 */
class Database extends \zesk\Database {
	private $call_prefix = null;

	/**
	 *
	 * @var SQLite3
	 */
	protected $conn = null;

	/**
	 * Support database features
	 *
	 * @see zesk\Database::feature($feature, $set)
	 */
	public function feature($feature, $set = null): void {
		switch ($feature) {
			case self::FEATURE_MAX_BLOB_SIZE:
				break;
		}

		throw new Exception_Unimplemented('Database {type} does not support feature {feature}', [
			'type' => $this->type(),
			'feature' => $feature,
		]);
	}

	public function _to_php() {
		return 'new sqlite3\\Database(' . PHP::dump($this->URL) . ')';
	}

	public function defaultIndexStructure($table_type) {
		return '';
	}

	/**
	 * Output a file which is a database dump of the database
	 *
	 * @param string $filename
	 *            The path to where the database should be dumped
	 * @param array $options
	 *            Options for dumping the database - dependent on database type
	 * @return boolean Whether the operation succeeded (true) or not (false)
	 */
	public function dump($filename, array $options = []) {
		throw new Exception_Unimplemented(__CLASS__ . '::' . __METHOD__);
	}

	/**
	 * Given a database file, restore the database
	 *
	 * @param string $filename
	 *            A file to restore the database from
	 * @param array $options
	 *            Options for dumping the database - dependent on database type
	 * @return boolean Whether the operation succeeded (true) or not (false)
	 */
	public function restore($filename, array $options = []) {
		throw new Exception_Unimplemented(__CLASS__ . '::' . __METHOD__);
	}

	/**
	 * Connect to the database
	 *
	 * @return boolean true if the connection is successful, false if not
	 * @see zesk\Database::connect()
	 */
	protected function _connect() {
		$path = avalue($this->url_parts, 'path');
		if (!$path) {
			throw new Exception_Configuration('No database path for {class}', ['class' => __CLASS__, ]);
		}
		$path = map($path, ArrayTools::prefixKeys($this->application->paths->variables(), 'zesk::paths::'));
		$dir = dirname($path);
		if (!is_dir($dir)) {
			throw new Exception_Directory_NotFound($dir, '{path} not found', ['path' => $path, ]);
		}
		$error_message = null;
		$flags = 0;
		$flags |= ($this->optionBool('create', true) ? SQLITE3_OPEN_CREATE : 0);
		$flags |= ($this->optionBool('readwrite', true) ? SQLITE3_OPEN_READWRITE : 0);
		$flags |= ($this->optionBool('readonly', false) ? SQLITE3_OPEN_READONLY : 0);
		$encryption_key = $this->option('encryption_key', null);
		$this->Connection = new SQLite3($path, $flags, $encryption_key);
		if (!$this->Connection) {
			throw new Database_Exception_Connect($this->URL, 'Unable to open file');
		}
		$this->conn = $this->Connection;
		$this->conn->enableExceptions(true);
		return true;
	}

	public function parser() {
		return new Database_Parser($this);
	}

	public function selectDatabase($name = null) {
		return true;
	}

	/**
	 * Return connection object
	 *
	 * @return SQLite3
	 */
	public function connection() {
		return $this->conn;
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see zesk\Database::free()
	 * @var $result SQLIte3Result
	 */
	final public function free($result): void {
	}

	final public function fetchArray(mixed $result): ?array {
		return $result->fetchArray(SQLITE3_NUM);
	}

	/**
	 *
	 * @param $result SQLite3Result
	 * @throws Exception_Parameter
	 */
	final public function fetchAssoc(mixed $result): ?array {
		if (!$result instanceof SQLite3Result) {
			throw new Exception_Parameter('Requires a SQLite3Result {class} (of {type}) given', [
				'class' => $result::class,
				'type' => type($result),
			]);
		}
		return $result->fetchArray(SQLITE3_ASSOC);
	}

	final public function nativeQuoteText($value) {
		return '\'' . $this->conn->escapeString($value) . '\'';
	}

	final public function affectedRows($result = null) {
		return $this->conn->changes();
	}

	final public function insertID(): ?int {
		return $this->conn->lastInsertRowID();
	}

	public function shellCommand(array $options = []) {
		static $shell_command = null;
		static $try_commands = ['sqlite3', 'sqlite2', 'sqlite', ];
		if ($shell_command) {
			return $shell_command;
		}
		foreach ($try_commands as $try) {
			$shell_command = $this->application->paths->which($try);
			echo "Try $try = $shell_command\n";
			if ($shell_command) {
				return [$shell_command, [$this->url_parts['path'], ], ];
			}
		}
		$shell_command = false;
		return false;
	}

	/*
	 * Database capabilities
	 */
	public function can($feature) {
		switch ($feature) {
			case self::FEATURE_CREATE_DATABASE:
				return true;
			case self::FEATURE_LIST_TABLES:
				return true;
		}
		return false;
	}

	/**
	 * Create a database at URL
	 *
	 * @see zesk\Database::createDatabase()
	 */
	public function createDatabase($url) {
		try {
			$db = $this->application->objects->factory(__CLASS__, $url);
			$db->connect();
		} catch (Exception $e) {
			$this->application->hooks->call('exception', $e);
			return false;
		}
		return true;
	}

	/**
	 * List tables
	 *
	 * @return array
	 * @see zesk\Database::listTables()
	 */
	public function listTables() {
		// TODO
		$result = $this->query('SHOW TABLES');
		$tables = [];
		return $tables;
	}

	public function sql_get_create_table($table) {
		$sql = "SHOW CREATE TABLE `$table`";
		$result = $this->query($sql);

		throw new Exception_Unimplemented();
		$this->free($result);
		return $result;
	}

	public function sql_create_table(Database_Table $dbTableObject) {
		$columns = $dbTableObject->columns();

		$types = [];
		foreach ($columns as $dbCol) {
			if (!$dbCol->hasSQLType() && !$this->type_set_sql_type($dbCol)) {
				die(__CLASS__ . "::sql_create_table: no SQL Type for column $dbCol");
			} else {
				$types[] = $this->quoteColumn($dbCol->name()) . ' ' . $dbCol->sql_type($dbCol, true);
			}
		}
		$indexes = $dbTableObject->indexes();
		$alters = [];
		if ($indexes) {
			foreach ($indexes as $index) {
				/* @var $index Database_Index */
				$typeSQL = $index->typeSQL();
				if ($typeSQL) {
					if ($index->type() === Database_Index::TYPE_PRIMARY) {
						$columns = $index->columns();
						if (count($columns) == 1) {
							reset($columns);
							$col = $dbTableObject->column(key($columns));
							if ($col->primary_key()) {
								continue;
							}
						}
					}
					$types[] = $typeSQL;
				} else {
					$alters[] = $index->createSQL();
				}
			}
		}
		$types = implode(",\n\t", $types);
		$result = [];
		$result[] = 'CREATE TABLE ' . $dbTableObject->name() . " (\n\t$types\n)";

		return array_merge($result, $alters);
	}

	public function sql_type_default($type, $default_value = null) {
		if ($type === 'text' || $type === 'blob') {
			return null;
		}
		switch ($this->sqlBasicType($type)) {
			case 'integer':
				return intval($default_value);
			case 'real':
				return floatval($default_value);
			case 'boolean':
				return toBool($default_value, false);
			case 'timestamp':
			case 'datetime':
				if ($default_value === 0 || $default_value === '0') {
					return '0000-00-00 00:00:00';
				}
				return strval($default_value);
		}
		return $default_value;
	}

	/**
	 *
	 * @return Database_Table
	 * @see zesk\Database::databaseTable()
	 */
	public function databaseTable($table) {
		$conn = $this->conn;
		$statement_sql = 'SELECT sql FROM sqlite_master WHERE name=:name AND type=\'table\'';
		$statement = $conn->prepare($statement_sql);
		$statement->bindParam(':name', $table, SQLITE3_TEXT);
		$sql = $this->queryOne($statement, 'sql', null, ['statement_sql' => $statement_sql, ]);
		if (!$sql) {
			throw new Database_Exception_Table_NotFound($this, null, $table);
		}
		$sql .= ";\n";
		// AND sql != '' ignores sqlite_autoindex declarations
		$statement_sql = 'SELECT sql FROM sqlite_master WHERE type=\'index\' AND tbl_name=:name AND sql != \'\'';
		$statement = $this->conn->prepare($statement_sql);
		$statement->bindParam(':name', $table, SQLITE3_TEXT);
		$indexes_sql = $this->queryArray($statement, null, 'sql', [], ['statement_sql' => $statement_sql, ]);
		if (count($indexes_sql) > 0) {
			$sql .= implode(";\n", $indexes_sql);
		}
		return $this->parseCreateTable($sql, 'extracted from sqlite_master');
	}

	private function exception(Exception $e): void {
		$message = $e->getMessage();
		if (preg_match('/no such table: (.*)/', $message, $matches)) {
			throw new Database_Exception_Table_NotFound($this, $matches[1]);
		}
		die($e::class . "\n" . $e->getMessage() . '<br />' . $e->getTraceAsString());
	}

	protected function _query(string $query, array $options = []): mixed {
		if (is_array($query)) {
			$result = [];
			foreach ($query as $index => $sql) {
				$result[$index] = $this->query($sql, $options);
			}
			return $result;
		}
		if (!$this->Connection) {
			throw new Database_Exception(null, __CLASS__ . '::query: Not connected');
		}
		if (is_string($query) && begins($query, '-- ')) {
			return true;
		}

		try {
			if ($query instanceof SQLite3Stmt) {
				$statement_sql = avalue($options, 'statement_sql', '-no-statement-sql-');
				$this->_queryBefore($statement_sql, $options);
				$result = $query->execute();
				$this->_queryAfter($statement_sql, $options);
			} else {
				$this->_queryBefore($query, $options);
				if ($this->optionBool('auto_table_names')) {
					$query = $this->autoTableNamesReplace($query);
				}
				$result = $this->conn->query($query);
				$this->_queryAfter($query, $options);
			}
		} catch (\Exception $e) {
			$this->exception($e);
		}
		return $result;
	}

	public function mixed_query($sql) {
		if (is_array($sql)) {
			$result = [];
			foreach ($sql as $k => $q) {
				$q = trim($q);
				if (empty($q)) {
					continue;
				}
				$result[$k] = $this->query($q);
			}
			return $result;
		}
		return $this->query($sql);
	}

	/*
	 * Date functions
	 */
	public function sql_now() {
		return 'datetime(\'now\')';
	}

	public function sql_now_utc() {
		return $this->sql_now();
	}

	public function sql_validate_datetime($value) {
		return (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/', $value) == 1);
	}

	public function sql_validate_Date($value) {
		return (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value) == 1);
	}

	public function sql_validate_time($value) {
		return (preg_match('/^[0-9]{2}:[0-9]{2}:[0-9]{2}$/', $value) == 1);
	}

	public function sql_validate_timeStamp($value) {
		return (preg_match('/^[0-9]{14}$/', $value) == 1);
	}

	final public function sql_parse_datetime($value) {
		if ($value === null) {
			return 'NULL';
		}
		if (is_string($value)) {
			if ($value == 'now') {
				return $this->sqlNow();
			} elseif ($this->sql_validate_datetime($value)) {
				return $value;
			}
		}
		if (!$value instanceof Timestamp) {
			throw new Exception_Unimplemented(__METHOD__ . ' invalid type: ' . gettype($value) . ',' . $value::class);
			return '0000-00-00 00:00:00';
		}
		if ($value->isEmpty()) {
			return 'NULL';
		}
		return '\'' . $value->format('{YYYY}-{MM}-{DD} {hh}:{mm}:{ss}') . '\'';
	}

	final public function sql_parse_date($value) {
		if ($value === null) {
			return 'NULL';
		}
		if (is_string($value)) {
			if ($value == 'now') {
				return $this->sqlNow();
			} elseif ($this->sql_validate_date($value)) {
				return $value;
			}
		}
		if (!$value instanceof Timestamp && !$value instanceof Date) {
			throw new Exception_Unimplemented(__METHOD__ . ' invalid type: ' . gettype($value) . ',' . $value::class);
		}
		if ($value->isEmpty()) {
			return 'NULL';
		}
		return '\'' . $value->format('{YYYY}-{MM}-{DD}') . '\'';
	}

	final public function sql_parse_time($value) {
		if ($value === null) {
			return 'NULL';
		}
		if (is_string($value)) {
			if ($value == 'now') {
				return $this->sqlNow();
			} elseif ($this->sql_validate_time($value)) {
				return $value;
			}
		}
		if (!$value instanceof Timestamp && !$value instanceof Time) {
			throw new Exception_Unimplemented(__METHOD__ . ' invalid type: ' . gettype($value) . ',' . $value::class);
			return '00:00:00';
		}
		if ($value->isEmpty()) {
			return 'NULL';
		}
		return '\'' . $value->format('{hh}:{mm}:{ss}') . '\'';
	}

	public function tableExists($table) {
		if (empty($table)) {
			return false;
		}
		$result = $this->queryArray('SHOW TABLES LIKE ' . $this->quoteTable($table));
		return (count($result) !== 0);
	}

	public function sql_format_datetime($sql) {
		// "0123456789012345678"
		// "YYYY-MM-DD hh:mm:ss"
		if ($sql === '0000-00-00 00:00:00' || empty($sql)) {
			return new Timestamp();
		}
		return Timestamp::instance((int) substr($sql, 0, 4), (int) substr($sql, 5, 2), (int) substr($sql, 8, 2), (int) substr($sql, 11, 2), (int) substr($sql, 14, 2), (int) substr($sql, 17, 2));
	}

	public function sqlToDate($sql) {
		// "0123456789"
		// "YYYY-MM-DD"
		if ($sql === '0000-00-00' || empty($sql)) {
			return new Timestamp();
		}
		return Date::instance((int) substr($sql, 0, 4), (int) substr($sql, 5, 2), (int) substr($sql, 8, 2));
	}

	public function sql_parse_timeStamp($value) {
		if ($value === null) {
			return 'NULL';
		}
		if (is_string($value)) {
			if ($value == 'now') {
				return $this->sqlNow();
			} elseif ($this->sql_validate_timeStamp($value)) {
				return $value;
			}
		}
		if (!$value instanceof Timestamp) {
			throw new Exception_Unimplemented(__METHOD__ . ' invalid type: ' . gettype($value) . ',' . $value::class);
			return '00000000000000';
		}
		if ($value->isEmpty()) {
			return 'NULL';
		}
		return '\'' . $value->format('{YYYY}-{MM}-{DD} {hh}:{mm}:{ss}') . '\'';
	}

	public function sql_format_timestamp($sql) {
		if (empty($sql)) {
			return new Timestamp(null);
		}
		// "0123456789012345678"
		// "YYYY-MM-DD hh:mm:ss"
		if (strlen($sql) == 14) {
			return Timestamp::instance((int) substr($sql, 0, 4), (int) substr($sql, 4, 2), (int) substr($sql, 6, 2), (int) substr($sql, 8, 2), (int) substr($sql, 10, 2), (int) substr($sql, 12, 2));
		} else {
			$dt = new Timestamp();
			if (!$dt->set($sql)) {
				return false;
			}
			return $dt;
		}
	}

	public function sql_function_date_add($sqlDate, $number, $units = 'second') {
		$dbUnits = $this->_convertUnits($number, $units);
		return "DATE_ADD($sqlDate, INTERVAL $number $dbUnits)";
	}

	public function sql_function_date_subtract($sqlDate, $number, $units = 'second') {
		$dbUnits = $this->_convertUnits($number, $units);
		return "DATE_SUB($sqlDate, INTERVAL $number $dbUnits)";
	}

	private function _convertUnits(&$number, $TIMEUNIT) {
		switch ($TIMEUNIT) {
			case 'millisecond':
				$number = intval($number / 1000);
				return 'SECOND';
			case 'second':
				return 'SECOND';
			case 'hour':
				return 'HOUR';
			case 'day':
				return 'DAY';
			case 'weekday':
				return 'DAY';
			case 'month':
				return 'MONTH';
			case 'quarter':
				$number = $number * 3;
				return 'MONTH';
			case 'year':
				return 'YEAR';
			default:
				throw new Exception_Semantics(__METHOD__ . "($number, $TIMEUNIT): Unknown time unit.");
		}
	}

	/*
	 * Platform SQL Tools
	 */
	public function sql_table_as($table, $name = '') {
		if (empty($name)) {
			return $table;
		}
		return "$table AS $name";
	}

	public function isReservedWord($word) {
		// Updated 2004-10-19 from MySQL Website YEARLY-TODO
		static $reserved = [
			'ADD',
			'ALL',
			'ALTER',
			'ANALYZE',
			'AND',
			'AS',
			'ASC',
			'ASENSITIVE',
			'BEFORE',
			'BETWEEN',
			'BIGINT',
			'BINARY',
			'BLOB',
			'BOTH',
			'BY',
			'CALL',
			'CASCADE',
			'CASE',
			'CHANGE',
			'CHAR',
			'CHARACTER',
			'CHECK',
			'COLLATE',
			'COLUMN',
			'COLUMNS',
			'CONDITION',
			'CONNECTION',
			'CONSTRAINT',
			'CONTINUE',
			'CONVERT',
			'CREATE',
			'CROSS',
			'CURRENT_DATE',
			'CURRENT_TIME',
			'CURRENT_TIMESTAMP',
			'CURRENT_USER',
			'CURSOR',
			'DATABASE',
			'DATABASES',
			'DAY_HOUR',
			'DAY_MICROSECOND',
			'DAY_MINUTE',
			'DAY_SECOND',
			'DEC',
			'DECIMAL',
			'DECLARE',
			'DEFAULT',
			'DELAYED',
			'DELETE',
			'DESC',
			'DESCRIBE',
			'DETERMINISTIC',
			'DISTINCT',
			'DISTINCTROW',
			'DIV',
			'DOUBLE',
			'DROP',
			'DUAL',
			'EACH',
			'ELSE',
			'ELSEIF',
			'ENCLOSED',
			'ESCAPED',
			'EXISTS',
			'EXIT',
			'EXPLAIN',
			'FALSE',
			'FETCH',
			'FIELDS',
			'FLOAT',
			'FOR',
			'FORCE',
			'FOREIGN',
			'FOUND',
			'FROM',
			'FULLTEXT',
			'GOTO',
			'GRANT',
			'GROUP',
			'HAVING',
			'HIGH_PRIORITY',
			'HOUR_MICROSECOND',
			'HOUR_MINUTE',
			'HOUR_SECOND',
			'IF',
			'IGNORE',
			'IN',
			'INDEX',
			'INFILE',
			'INNER',
			'INOUT',
			'INSENSITIVE',
			'INSERT',
			'INT',
			'INTEGER',
			'INTERVAL',
			'INTO',
			'IS',
			'ITERATE',
			'JOIN',
			'KEY',
			'KEYS',
			'KILL',
			'LEADING',
			'LEAVE',
			'LEFT',
			'LIKE',
			'LIMIT',
			'LINES',
			'LOAD',
			'LOCALTIME',
			'LOCALTIMESTAMP',
			'LOCK',
			'LONG',
			'LONGBLOB',
			'LONGTEXT',
			'LOOP',
			'LOW_PRIORITY',
			'MATCH',
			'MEDIUMBLOB',
			'MEDIUMINT',
			'MEDIUMTEXT',
			'MIDDLEINT',
			'MINUTE_MICROSECOND',
			'MINUTE_SECOND',
			'MOD',
			'NATURAL',
			'NOT',
			'NO_WRITE_TO_BINLOG',
			'NULL',
			'NUMERIC',
			'ON',
			'OPTIMIZE',
			'OPTION',
			'OPTIONALLY',
			'OR',
			'ORDER',
			'OUT',
			'OUTER',
			'OUTFILE',
			'PRECISION',
			'PRIMARY',
			'PRIVILEGES',
			'PROCEDURE',
			'PURGE',
			'READ',
			'REAL',
			'REFERENCES',
			'REGEXP',
			'RENAME',
			'REPEAT',
			'REPLACE',
			'REQUIRE',
			'RESTRICT',
			'RETURN',
			'REVOKE',
			'RIGHT',
			'RLIKE',
			'SCHEMA',
			'SCHEMAS',
			'SECOND_MICROSECOND',
			'SELECT',
			'SENSITIVE',
			'SEPARATOR',
			'SET',
			'SHOW',
			'SMALLINT',
			'SONAME',
			'SPATIAL',
			'SPECIFIC',
			'SQL',
			'SQLEXCEPTION',
			'SQLSTATE',
			'SQLWARNING',
			'SQL_BIG_RESULT',
			'SQL_CALC_FOUND_ROWS',
			'SQL_SMALL_RESULT',
			'SSL',
			'STARTING',
			'STRAIGHT_JOIN',
			'TABLE',
			'TABLES',
			'TERMINATED',
			'THEN',
			'TINYBLOB',
			'TINYINT',
			'TINYTEXT',
			'TO',
			'TRAILING',
			'TRIGGER',
			'TRUE',
			'UNDO',
			'UNION',
			'UNIQUE',
			'UNLOCK',
			'UNSIGNED',
			'UPDATE',
			'USAGE',
			'USE',
			'USING',
			'UTC_DATE',
			'UTC_TIME',
			'UTC_TIMESTAMP',
			'VALUES',
			'VARBINARY',
			'VARCHAR',
			'VARCHARACTER',
			'VARYING',
			'WHEN',
			'WHERE',
			'WHILE',
			'WITH',
			'WRITE',
			'XOR',
			'YEAR_MONTH',
			'ZEROFILL',
		];
		$word = strtoupper($word);
		return in_array($word, $reserved);
	}

	/*
	 * String Comparison
	 */
	public function sql_function_compare_binary($column_name, $cmp, $string) {
		return "$column_name $cmp BINARY " . $this->sql_format_string($string);
	}

	/*
	 * String Manipulation
	 */
	public function sql_format_string($sql) {
		return '\'' . addslashes($sql) . '\'';
	}

	public static function parseType($sql_type, &$size) {
		$matches = false;
		$result = preg_match('/([a-z]+)\(([^)]*)\)( unsigned)?/', strtolower($sql_type), $matches);
		if (!$result) {
			$size = false;
		} else {
			$size = $matches[2];
			$sql_type = $matches[1];
		}
		if (count($matches) == 4) {
			return $sql_type; // . $matches[3]
		}
		return $sql_type;
	}

	public static function _basicType($t) {
		static $basicTypes = [
			'string' => ['char', 'varchar', 'varbinary', 'binary', 'text', ],
			'integer' => ['int', 'tinyint', 'mediumint', 'smallint', 'bigint', 'integer', ],
			'real' => ['float', 'double', 'decimal', ],
			'date' => ['date', ],
			'time' => ['time', ],
			'datetime' => ['datetime', 'timestamp', ],
			'boolean' => ['enum', ],
		];
		$t = trim(strtolower($t));
		foreach ($basicTypes as $type => $types) {
			if (in_array($t, $types)) {
				return $type;
			}
		}
		return false;
	}

	public static function sqlBasicType($sql_type) {
		$s0 = false;
		$t = self::parseType($sql_type, $s0);
		return self::_basicType($t);
	}

	private function _types_compatible($t0, $t1) {
		$t0 = self::_basicType($t0);
		$t1 = self::_basicType($t1);
		return ($t0 === $t1);
	}

	public function types_compatible($sql_type0, $sql_type1) {
		$s0 = false;
		$s1 = false;
		$t0 = self::parseType($sql_type0, $s0);
		$t1 = self::parseType($sql_type1, $s1);

		$bt0 = self::_basicType($t0);
		$bt1 = self::_basicType($t1);
		// echo "$sql_type0 -> $t0, $s0, $bt0\n"; echo "$sql_type1 -> $t1, $s1, $bt1\n"; echo "===\n";
		if ($bt0 !== $bt1) {
			return false;
		}
		// Sizes don't matter with integer types
		if ($bt0 !== 'integer' && $s0 !== $s1) {
			return false;
		}
		if ($t0 === $t1) {
			return true;
		}
		return self::_types_compatible($t0, $t1);
	}

	/*
	 * Boolean Type
	 */
	public function sqlParseBoolean($value) {
		return $value ? '\'true\'' : '\'false\'';
	}

	public function sql_format_boolean($sql) {
		return $sql == 'true' ? true : false;
	}

	/*
	 * Password Type
	 */
	public function sql_format_password($value) {
		return 'MD5(' . $this->sql_format_string($value) . ')';
	}

	/*
	 * Functions
	 */
	public function sql_format_function($func, $memberName, $alias = '') {
		switch (strtolower(trim($func))) {
			case 'min':
				return $this->sql_table_as("MIN($memberName)", $alias);
			case 'max':
				return $this->sql_table_as("MAX($memberName)", $alias);
			case 'sum':
				return $this->sql_table_as("SUM($memberName)", $alias);
			case 'count':
				return $this->sql_table_as("COUNT($memberName)", $alias);
			case 'average':
				return $this->sql_table_as("AVG($memberName)", $alias);
			case 'stddev':
				return $this->sql_table_as("STDDEV($memberName)", $alias);
			case 'year':
				return $this->sql_table_as("YEAR($memberName)", $alias);
			case 'quarter':
				return $this->sql_table_as("QUARTER($memberName)", $alias);
			case 'month':
				return $this->sql_table_as("MONTH($memberName)", $alias);
			case 'day':
				return $this->sql_table_as("DAY($memberName)", $alias);
			case 'hour':
				return $this->sql_table_as("HOUR($memberName)", $alias);
			case 'minute':
				return $this->sql_table_as("MINUTE($memberName)", $alias);
			default:
				return false;
		}
	}

	public function quoteColumn($column) {
		return '"' . strtr($column, ['"' => '""', ]) . '"';
	}

	public function quoteTable($table) {
		return self::quoteColumn($table);
	}

	public function unquoteTable($table) {
		return unquote($table, '""');
	}

	public function quoteName($table) {
		return self::quoteColumn($table);
	}

	protected function integer_size_type($lookup) {
		return avalue([
			'1' => 'tinyint',
			'tiny' => 'tinyint',
			'2' => 'smallint',
			'small' => 'smallint',
			'4' => 'integer',
			'default' => 'integer',
			'big' => 'bigint',
			'large' => 'bigint',
			'8' => 'bigint',
		], $lookup, 'integer');
	}

	public function tableColumns($table) {
		return $this->databaseTable($table)->columns();
	}

	private function _lock_path() {
		return $this->application->paths->cache('sqlite3/locks/' . md5($this->databaseName()));
	}

	private $locks = [];

	public function release_all_locks(): void {
		foreach ($this->locks as $name => $file) {
			$this->releaseLock($name);
		}
	}

	/**
	 * SQLite3 locking
	 *
	 * @param unknown $name
	 * @param number $wait_seconds
	 * @return boolean
	 */
	public function getLock($name, $wait_seconds = 0) {
		$lock_path = $this->_lock_path();
		Directory::depend($lock_path);
		$name = File::name_clean($name);
		$lock_file = path($lock_path, $name);
		$f = fopen($lock_file, 'w+b');
		$timer = new Timer();
		do {
			if (flock($f, LOCK_EX | LOCK_NB)) {
				if (count($this->locks) === 0) {
					$this->application->hooks->add('exit', [$this, 'release_all_locks', ]);
				}
				$this->locks[$name] = $f;
				return true;
			}
			if ($wait_seconds > 0) {
				sleep(1);
			}
		} while ($timer->elapsed() < $wait_seconds);
		fclose($f);
		return false;
	}

	/**
	 * SQLite3 Release lock
	 *
	 * @param unknown $name
	 * @return boolean
	 */
	public function releaseLock($name) {
		$lock_path = self::_lock_path();
		Directory::depend($lock_path);
		$name = File::name_clean($name);
		if (array_key_exists($name, $this->locks)) {
			$f = $this->locks[$name];
			flock($f, LOCK_UN);
			fclose($f);
			unset($this->locks[$name]);
			return true;
		}
		return false;
	}

	public function bytesUsed($table = null) {
		return 0;
	}

	/**
	 * Begin a transaction in the database
	 *
	 * @return boolean
	 */
	public function transactionStart() {
		// TODO: Ensure database is in auto-commit mode
		return $this->query('BEGIN TRANSACTION');
	}

	/**
	 * Finish transaction in the database
	 *
	 * @param boolean $success
	 *            Whether to commit (true) or roll back (false)
	 * @return boolean
	 */
	public function transactionEnd($success = true) {
		$sql = $success ? 'COMMIT TRANSACTION' : 'ROLLBACK TRANSACTION';
		return $this->query($sql);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @see \zesk\Database::columnDifferences()
	 */
	public function columnDifferences(Database_Column $a, Database_Column $b, array $differences) {
		return $differences;
	}

	/**
	 * Returns an array of TABLE_INFO constants, or null if not found
	 *
	 * @param string $table
	 * @return array
	 */
	public function tableInformation($table) {
		throw new Exception_Unimplemented('Need to implement {method}', ['method' => __METHOD__, ]);
	}
}
