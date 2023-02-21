<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage sqlite3
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\SQLite3;

// PHP classes
use DateTimeZone;
use Exception;
use SQLite3 as NativeSQLite3;
use SQLite3Result;
use SQLite3Stmt;
use SQLiteException;
use Throwable;
use zesk\ArrayTools;
use zesk\Database\Base as BaseDatabase;
use zesk\Database\Column;
use zesk\Database\DatabaseInterface;
use zesk\Database\Index;
use zesk\Database\QueryResult as BaseQueryResult;
use zesk\Database\Table;
use zesk\Directory;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\FileNotFound;
use zesk\Exception\Unimplemented;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\SyntaxException;
use zesk\TimeoutExpired;
use zesk\Exception\Unsupported;
use zesk\File;
use zesk\PHP;
use zesk\StringTools;
use zesk\Timer;
use function strtr;

// Zesk classes

// Database

// Exceptions

// Database Exceptions

/**
 * SQLite Implementation
 *
 * @author kent
 *
 */
class Database extends BaseDatabase implements DatabaseInterface {
	/**
	 * Options is a list of keys to bind to the sql (in options)
	 * @var string
	 */
	public const QUERY_OPTION_BIND = 'bind';

	/**
	 *
	 * @var NativeSQLite3
	 */
	protected ?NativeSQLite3 $conn = null;

	/**
	 * File-based locks
	 *
	 * @var array
	 */
	private array $locks = [];

	protected function initialize(): void {
		$this->_sqlParser = new SQLParser($this);
		$this->_sqlDialect = new SQLDialect($this);
		$this->_types = new Type($this);
	}

	/**
	 * Support database features
	 *
	 */
	public function feature($feature): mixed {
		switch ($feature) {
			case self::FEATURE_MAX_BLOB_SIZE:
				break;
		}

		throw new Unimplemented('Database {type} does not support feature {feature}', [
			'type' => $this->type(), 'feature' => $feature,
		]);
	}

	/**
	 * @param string $feature
	 * @param bool|string $set
	 * @return $this
	 * @throws Unsupported
	 */
	public function setFeature(string $feature, bool|int|string $set): self {
		throw new Unsupported(__METHOD__);
	}

	/*========================================================================================\
	 *
	 *  Connection
	 *
	\*=======================================================================================*/

	/**
	 * Connect to the database
	 *
	 * @return void
	 * @throws Database\Exception\Connect
	 * @throws SyntaxException
	 * @throws DirectoryNotFound
	 */
	public function internalConnect(): void {
		$path = $this->url_parts['path'];
		if (!$path) {
			throw new SyntaxException('No database path for {class}', ['class' => __CLASS__, ]);
		}
		$path = $this->application->paths->expand(map($path, $this->application->paths->variables()));
		$dir = dirname($path);
		if (!is_dir($dir)) {
			throw new DirectoryNotFound($dir, '{path} not found', ['path' => $path, ]);
		}
		$flags = 0;
		$flags |= ($this->optionBool('create', true) ? SQLITE3_OPEN_CREATE : 0);
		$flags |= ($this->optionBool('readwrite', true) ? SQLITE3_OPEN_READWRITE : 0);
		$flags |= ($this->optionBool('readonly', false) ? SQLITE3_OPEN_READONLY : 0);
		$encryption_key = $this->option('encryption_key', null);

		try {
			$this->conn = new NativeSQLite3($path, $flags, $encryption_key);
		} catch (Throwable $t) {
			throw new Database\Exception\Connect($this->URL, 'Unable to open file', [], $t->getCode(), $t);
		}
		$this->conn->enableExceptions(true);
	}

	/**
	 * @return mixed
	 */
	public function connection(): mixed {
		return $this->conn;
	}

	public function internalDisconnect(): void {
		$this->conn->close();
		$this->conn = null;
	}

	/*========================================================================================\
	 *
	 *  Inspection
	 *
	\*=======================================================================================*/
	/**
	 *
	 *
	 * @see Database::columnDifferences()
	 */
	public function columnDifferences(Column $self, Column $that): array {
		return [];
	}

	/**
	 * List tables
	 *
	 * @return array
	 * @see Database::listTables()
	 */
	public function listTables(): array {
		return $this->queryArray('SELECT name FROM sqlite_master WHERE type=\'table\'', null, 'name');
	}

	/**
	 *
	 * @param string $table_name
	 * @return bool
	 */
	public function tableExists(string $table_name): bool {
		$safeSQL = map('SELECT COUNT(*) FROM sqlite_master WHERE type=\'table\' AND name=\'{name}\'', [
			'name' => $this->quoteText($table_name),
		]);
		return $this->queryInteger($safeSQL) !== 0;
	}

	/**
	 * Type is usually 'table'
	 */
	public const TABLE_INFO_TYPE = 'type';

	/**
	 * Name of the table
	 */
	public const TABLE_INFO_NAME = 'name';

	/**
	 * For indexes, the name of the table where the index exists
	 */
	public const TABLE_INFO_TABLE_NAME = 'tbl_name';

	/**
	 * Not sure
	 */
	public const TABLE_INFO_ROOT_PAGE = 'rootpage';

	/**
	 * SQL for this table
	 */
	public const TABLE_INFO_SQL = 'sql';

	/**
	 * Returns an array of TABLE_INFO constants, or null if not found
	 *
	 * @param string $table_name
	 * @return array
	 * @throws Database\Exception\NoResults
	 */
	public function tableInformation(string $table_name): array {
		$safeSQL = map('SELECT * FROM sqlite_master WHERE type=\'table\' AND name=\'{name}\'', [
			'name' => $this->quoteText($table_name),
		]);

		try {
			$result = $this->queryOne($safeSQL);
		} catch (KeyNotFound $key) {
			throw new Database\Exception\NoResults($this, $safeSQL, 'Key error {message}', [
				'message' => $key->getMessage(),
			], $key->getCode(), $key);
		}
		assert(array_key_exists(self::TABLE_INFO_TYPE, $result));
		assert(array_key_exists(self::TABLE_INFO_NAME, $result));
		assert(array_key_exists(self::TABLE_INFO_TABLE_NAME, $result));
		assert(array_key_exists(self::TABLE_INFO_ROOT_PAGE, $result));
		assert(array_key_exists(self::TABLE_INFO_SQL, $result));
		return $result;
	}

	/**
	 * @param string $table_name
	 * @return array
	 * @throws Database\Exception\TableNotFound
	 */
	public function tableColumns(string $table_name): array {
		throw new Unimplemented(__METHOD__);
	}

	/**
	 *
	 * @param string $table_name
	 * @return Table
	 * @throws Database\Exception\SQLException
	 */
	public function databaseTable(string $tableName): Table {
		$conn = $this->conn;

		$statement_sql = 'SELECT sql FROM sqlite_master WHERE name=:name AND type=:type';
		$statement = $conn->prepare($statement_sql);
		$statement->bindParam(':name', $tableName);
		$statement->bindValue(':type', 'table');

		$resultSet = $statement->execute();

		$sql = $this->resultRowColumn($resultSet, 'sql');
		$sql .= ";\n";

		$statement_sql = 'SELECT sql FROM sqlite_master WHERE type=:type AND tbl_name=:name AND sql != \'\'';
		$statement = $this->conn->prepare($statement_sql);
		$statement->bindParam(':name', $table);
		$statement->bindValue(':type', 'index');

		$resultSet = $statement->execute();
		$indexes_sql = $this->resultRow($resultSet);
		if (count($indexes_sql) > 0) {
			$sql .= implode(";\n", $indexes_sql);
		}

		return $this->parseCreateTable($sql, 'extracted from sqlite_master');
	}

	/**
	 * @param string $tableName
	 * @return int
	 * @throws Unimplemented
	 */
	public function bytesUsed(string $tableName = ''): int {
		throw new Unimplemented(__METHOD__);
	}

	/*========================================================================================\
	 *
	 * SQL
	 *
	\*=======================================================================================*/
	public function quoteName(string $text): string {
		return self::quoteColumn($text);
	}

	public function quoteColumn(string $text): string {
		return '"' . strtr($text, ['"' => '""', ]) . '"';
	}

	public function quoteTable(string $text): string {
		return self::quoteColumn($text);
	}

	public function unquoteTable(string $text): string {
		return strtr(StringTools::unquote($text, '""'), ['""' => '"']);
	}

	/*========================================================================================\
	 *
	 * Queries
	 *
	\*=======================================================================================*/
	public string $zone = 'UTC';

	public function setTimeZone(string|DateTimeZone $zone): self {
		$this->zone = $zone instanceof DateTimeZone ? $zone->getName() : $zone;
		date_default_timezone_set($this->zone);
		return $this;
	}

	public function timeZone(): string {
		return $this->zone;
	}

	public function selectDatabase(string $name): self {
		throw new Unsupported('One database per connection');
	}

	/**
	 * SQLite3 locking
	 *
	 * @param string $name
	 * @param int $wait_seconds
	 * @theows TimeoutExpired
	 * @theows DirectoryNotFound
	 */
	public function getLock(string $name, int $wait_seconds = 0): void {
		$lock_path = $this->_lock_path();
		Directory::depend($lock_path);
		$name = File::nameClean($name);
		$lock_file = path($lock_path, $name);
		$f = fopen($lock_file, 'w+b');
		$timer = new Timer();
		do {
			if (flock($f, LOCK_EX | LOCK_NB)) {
				if (count($this->locks) === 0) {
					$this->application->hooks->add('exit', [$this, 'release_all_locks', ]);
				}
				$this->locks[$name] = $f;
				return;
			}
			if ($wait_seconds > 0) {
				sleep(1);
			}
		} while ($timer->elapsed() < $wait_seconds);
		fclose($f);

		throw new TimeoutExpired('{method}({name}, {wait_seconds})', [
			'name' => $name, 'wait_seconds' => $wait_seconds,
		]);
	}

	/**
	 * SQLite3 Release lock
	 *
	 * @param string $name
	 * @return void
	 */
	public function releaseLock(string $name): void {
		$lock_path = self::_lock_path();
		Directory::depend($lock_path);
		$name = File::nameClean($name);
		if (!array_key_exists($name, $this->locks)) {
			throw new KeyNotFound('No such lock');
		}
		$f = $this->locks[$name];
		flock($f, LOCK_UN);
		fclose($f);
		unset($this->locks[$name]);
	}

	/**
	 * Begin a transaction in the database
	 *
	 * @return void
	 * @throws Database\Exception\Duplicate
	 * @throws Database\Exception\SQLException
	 * @throws Database\Exception\TableNotFound
	 */
	public function transactionStart(): void {
		$this->query('BEGIN TRANSACTION')->free();
	}

	/**
	 * Finish transaction in the database
	 *
	 * @param bool $success Whether to commit (true) or roll back (false)
	 * @return void
	 * @throws Database\Exception\SQLException
	 * @throws Database\Exception\TableNotFound
	 * @throws Database\Exception\Duplicate
	 */
	public function transactionEnd(bool $success = true): void {
		$sql = $success ? 'COMMIT TRANSACTION' : 'ROLLBACK TRANSACTION';
		$this->query($sql)->free();
	}

	public function query(string $sql, array $options = []): QueryResult {
		$statement = $this->conn->prepare($sql);
		if ($options[self::QUERY_OPTION_BIND] ?? false) {
			foreach (toList($options[self::QUERY_OPTION_BIND]) as $key) {
				$statement->bindParam(':' . $key, $options[$key], self::phpTypeToSQLITE3Type($options[$key]));
			}
		}
		return QueryResult::factory($this->executeStatement($statement));
	}

	/**
	 * @param BaseQueryResult $result
	 * @return int
	 */
	public function affectedRows(BaseQueryResult $result): int {
		return $this->conn->changes();
	}

	/*========================================================================================\
	 *
	 * Dump and restore
	 *
	\*=======================================================================================*/
	/**
	 * @param array $options
	 * @return array
	 * @throws NotFoundException
	 */
	public function shellCommand(array $options = []): array {
		static $try_commands = ['sqlite3', 'sqlite', ];
		foreach ($try_commands as $try) {
			try {
				return [$this->application->paths->which($try), [$this->url_parts['path'], ], ];
			} catch (NotFoundException) {
			}
		}

		throw new NotFoundException('sqlite3');
	}

	/**
	 * @param string $filename
	 * @param array $options
	 * @return void
	 * @throws Unimplemented
	 */
	public function dump(string $filename, array $options = []): void {
		throw new Unimplemented(__CLASS__ . '::' . __METHOD__);
	}

	/**
	 * Given a database file, restore the database
	 *
	 * @param string $filename A file to restore the database from
	 * @param array $options Options for dumping the database - dependent on database type
	 * @return void
	 * @throws Unimplemented
	 */
	public function restore(string $filename, array $options = []): void {
		throw new Unimplemented(__CLASS__ . '::' . __METHOD__);
	}

	/*========================================================================================\
	 *
	 * SQLite3 classes
	 *
	\*=======================================================================================*/

	/**
	 * Given a type in PHP convert it to a type SQLite supports
	 * @param mixed $mixed
	 * @return int
	 */
	private function phpTypeToSQLITE3Type(mixed $mixed): int {
		if (is_string($mixed)) {
			return SQLITE3_TEXT;
		}
		if (is_float($mixed)) {
			return SQLITE3_FLOAT;
		}
		if (is_numeric($mixed)) {
			return SQLITE3_INTEGER;
		}
		if ($mixed === null) {
			return SQLITE3_NULL;
		}
		return SQLITE3_BLOB;
	}

	/**
	 * @param SQLite3Stmt $statement
	 * @return SQLite3Result
	 * @throws Database\Exception\SQLException
	 */
	private function executeStatement(SQLite3Stmt $statement): SQLite3Result {
		try {
			$result = $statement->execute();
			if ($result instanceof SQLite3Result) {
				return $result;
			}
		} catch (SQLiteException $e) {
			throw new Database\Exception\SQLException($this, $statement->getSQL(true));
		}

		throw new Database\Exception\SQLException($this, $statement->getSQL(true));
	}

	/**
	 * @param SQLite3Result $result
	 * @param bool $assoc
	 * @return array
	 * @throws NotFoundException
	 */
	private function resultRow(SQLite3Result $result, bool $assoc = true): array {
		$row = $result->fetchArray($assoc ? SQLITE3_ASSOC : SQLITE3_NUM);
		if ($row === false) {
			throw new NotFoundException('No more rows for {class}', ['class' => SQLite3Result::class]);
		}
		return $row;
	}

	/**
	 * @param SQLite3Result $result
	 * @param int|string $column
	 * @return mixed
	 * @throws KeyNotFound
	 * @throws NotFoundException
	 */
	private function resultRowColumn(SQLite3Result $result, int|string $column): mixed {
		$one = $this->resultRow($result, !is_int($column));
		if (!array_key_exists($column, $one)) {
			throw new KeyNotFound('Missing column {column} in row {keys}', [
				'column' => $column, 'keys' => array_keys($one),
			]);
		}
		return $one[$column];
	}

	public function _to_php() {
		return 'new sqlite3\\Database(' . PHP::dump($this->URL) . ')';
	}

	public function defaultIndexStructure(string $table_type): string {
		return '';
	}

	public function sqlParser(): \zesk\Database\SQLParser {
		return new SQLParser($this);
	}

	/**
	 *
	 * {@inheritdoc}
	 *
	 * @param SQLIte3Result $result
	 *@see Database::free()
	 */
	final public function free($result): void {
	}

	final public function fetchArray(mixed $result): ?array {
		return $result->fetchArray(SQLITE3_NUM);
	}

	/**
	 *
	 * @param $result SQLite3Result
	 * @throws ParameterException
	 */
	final public function fetchAssoc(mixed $result): ?array {
		if (!$result instanceof SQLite3Result) {
			throw new ParameterException('Requires a SQLite3Result {class} (of {type}) given', [
				'class' => $result::class, 'type' => type($result),
			]);
		}
		return $result->fetchArray(SQLITE3_ASSOC);
	}

	final public function nativeQuoteText(string $value): string {
		return '\'' . $this->conn->escapeString($value) . '\'';
	}

	final public function insertID(BaseQueryResult $result): int {
		return $this->conn->lastInsertRowID();
	}

	/*
	 * Database capabilities
	 */
	public function can(string $feature): bool {
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
	 *
	 */
	public function createDatabase(string $url, array $hosts): bool {
		try {
			$db = new self($this->application, $url, ['create' => true, 'hosts' => $hosts]);
			$db->connect();
		} catch (Exception $e) {
			$this->application->hooks->call('exception', $e);
			return false;
		}
		return true;
	}

	public function sql_get_create_table($table): void {
		$sql = "SHOW CREATE TABLE `$table`";
		$result = $this->query($sql);

		throw new Unimplemented(__METHOD__);
//		$this->free($result);
//		return $result;
	}

	public function sql_create_table(Table $dbTableObject) {
		$columns = $dbTableObject->columns();

		$types = [];
		foreach ($columns as $dbCol) {
			if (!$dbCol->hasSQLType() && !$this->types()->type_set_sql_type($dbCol)) {
				die(__CLASS__ . "::sql_create_table: no SQL Type for column $dbCol");
			} else {
				$types[] = $this->quoteColumn($dbCol->name()) . ' ' . $dbCol->sql_type($dbCol, true);
			}
		}
		$indexes = $dbTableObject->indexes();
		$alters = [];
		if ($indexes) {
			foreach ($indexes as $index) {
				/* @var $index Index */
				$typeSQL = $index->typeSQL();
				if ($typeSQL) {
					if ($index->type() === Index::TYPE_PRIMARY) {
						$columns = $index->columns();
						if (count($columns) == 1) {
							$col = $dbTableObject->column(key($columns));
							if ($col->primaryKey()) {
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

	private function exception(Exception $e): void {
		$message = $e->getMessage();
		if (preg_match('/no such table: (.*)/', $message, $matches)) {
			throw new Database\Exception\TableNotFound($this, $matches[1]);
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
			throw new Exception(null, __CLASS__ . '::query: Not connected');
		}
		if (is_string($query) && str_starts_with($query, '-- ')) {
			return true;
		}

		try {
			if ($query instanceof SQLite3Stmt) {
				$statement_sql = $options['statement_sql'] ?? '-no-statement-sql-';
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
		} catch (Exception $e) {
			$this->exception($e);
		}
		return $result;
	}

	/**
	 * Now SQL
	 * @return string
	 * @deprecated 2022 use ->sql()->now()
	 */
	public function sql_now(): string {
		return 'datetime(\'now\')';
	}

	/**
	 * Now SQL
	 * @return string
	 * @deprecated 2022 use ->sql()->now()
	 */
	public function sql_nowUTC(): string {
		return $this->sqlDialect()->nowUTC();
	}

	/**
	 * Now SQL
	 * @return string
	 * @deprecated 2022 use ->sql() functions
	 */
	public function sql_table_as($table, $name = '') {
		if (empty($name)) {
			return $table;
		}
		return "$table AS $name";
	}

	/**
	 * @param string $word
	 * @return bool
	 */
	public function isReservedWord(string $word): bool {
		static $reserved = null;
		if (!$reserved) {
			$path = $this->application->modules->path('sqlite3', 'etc/reserved.txt');

			try {
				$reserved = ArrayTools::changeValueCase(File::lines($path));
			} catch (FileNotFound) {
				$this->application->logger->critical('Unable to load reserved word list at {path}', ['path' => $path]);
				$reserved = [];
			}
		}
		$word = strtolower($word);
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
		$matches = [];
		$result = preg_match('/([a-z]+)\(([^)]*)\)( unsigned)?/', strtolower($sql_type), $matches);
		if (!$result) {
			$size = false;
		} else {
			$size = $matches[2];
			$sql_type = $matches[1];
		}
		return $sql_type;
	}

	public static function _basicType($t) {
		static $basicTypes = [
			'string' => ['char', 'varchar', 'varbinary', 'binary', 'text', ],
			'integer' => ['int', 'tinyint', 'mediumint', 'smallint', 'bigint', 'integer', ],
			'real' => ['float', 'double', 'decimal', ], 'date' => ['date', ], 'time' => ['time', ],
			'datetime' => ['datetime', 'timestamp', ], 'boolean' => ['enum', ],
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
	public function sqlParseBoolean(mixed $value): string {
		return $value ? '\'true\'' : '\'false\'';
	}

	public function sql_format_boolean($sql) {
		return $sql == 'true';
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
	public function sql_format_function(string $func, string $memberName, string $alias = ''): string {
		$funcs = [
			'min' => 'MIN',
			'max' => 'MAX',
			'sum' => 'SUM',
			'count' => 'COUNT',
			'average' => 'AVG',
			'stddev' => 'STDDEV',
			'year' => 'YEAR',
			'quarter' => 'QUARTER',
			'month' => 'MONTH',
			'day' => 'DAY',
			'hour' => 'HOUR',
			'minute' => 'MINUTE',
		];
		$func = strtolower(trim($func));
		if (!array_key_exists($func, $funcs)) {
			throw new KeyNotFound("No function $func");
		}
		$sqlFunc = $funcs[$func];
		return $this->sqlDialect()->table_as("$sqlFunc($memberName)", $alias);
	}

	/**
	 * @param string $lookup
	 * @return string
	 */
	protected function integer_size_type(string $lookup): string {
		return [
			'1' => 'tinyint', 'tiny' => 'tinyint', '2' => 'smallint', 'small' => 'smallint', '4' => 'integer',
			'default' => 'integer', 'big' => 'bigint', 'large' => 'bigint', '8' => 'bigint',
		][$lookup] ?? 'integer';
	}

	/**
	 * @return string
	 */
	private function _lock_path(): string {
		return $this->application->cachePath('sqlite3/locks/' . md5($this->databaseName()));
	}

	/**
	 * @return void
	 * @throws KeyNotFound
	 */
	public function release_all_locks(): void {
		foreach ($this->locks as $name => $file) {
			$this->releaseLock($name);
		}
	}

	/**
	 * @return string
	 */
	public function version(): string {
		$versionStruct = NativeSQLite3::version();
		return $versionStruct['versionString'] ?? '';
	}
}
