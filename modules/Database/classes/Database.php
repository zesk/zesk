<?php
declare(strict_types=1);
/**
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */

namespace zesk;

use DateTimeZone;
use zesk\Database\QueryResult;

/**
 *
 * @package zesk
 * @subpackage system
 */
abstract class Database extends Hookable implements Database_Interface {
	/**
	 *
	 * @var string
	 */
	public const TABLE_INFO_ENGINE = 'engine';

	/**
	 *
	 * @var string
	 */
	public const TABLE_INFO_ROW_COUNT = 'row_count';

	/**
	 *
	 * @var string
	 */
	public const TABLE_INFO_DATA_SIZE = 'data_size';

	/**
	 *
	 * @var string
	 */
	public const TABLE_INFO_FREE_SIZE = 'free_size';

	/**
	 *
	 * @var string
	 */
	public const TABLE_INFO_INDEX_SIZE = 'index_size';

	/**
	 *
	 * @var string
	 */
	public const TABLE_INFO_CREATED = 'created';

	/**
	 *
	 * @var string
	 */
	public const TABLE_INFO_UPDATED = 'updated';

	/**
	 * Setting this option on a database will convert all SQL to automatically set the table names
	 * from class names
	 *
	 * @var string
	 */
	public const OPTION_AUTO_TABLE_NAMES = 'auto_table_names';

	/**
	 * Boolean value to enable database query logging
	 */
	public const OPTION_LOG_ENABLED = 'log';

	/**
	 * When logging is enabled, log queries which take longer than this long as errors.
	 */
	public const OPTION_SLOW_QUERY_SECONDS = 'slow_query_seconds';

	/**
	 * When logging is enabled, log queries which take longer than this long as errors.
	 */
	public const DEFAULT_OPTION_SLOW_QUERY_SECONDS = 1.0;

	/**
	 * Does this database support creation of other databases?
	 *
	 * @var string
	 */
	public const FEATURE_CREATE_DATABASE = 'createDatabase';

	/**
	 * Does this database support listing of tables?
	 *
	 * @var string
	 */
	public const FEATURE_LIST_TABLES = 'listTables';

	/**
	 *
	 * @var string
	 */
	public const FEATURE_MAX_BLOB_SIZE = 'max_blob_size';

	/**
	 * Can this database perform queries across databases on the same connection?
	 *
	 * @var string
	 */
	public const FEATURE_CROSS_DATABASE_QUERIES = 'cross_database_queries';

	/**
	 * Does the database support timestamps which are relative to a session or global time zone
	 * setting?
	 *
	 * @var string
	 */
	public const FEATURE_TIME_ZONE_RELATIVE_TIMESTAMP = 'time_zone_relative_timestamp';

	/**
	 *
	 * @var Database_Parser
	 */
	protected Database_Parser $parser;

	/**
	 * SQL Generation
	 *
	 * @var Database_SQL
	 */
	protected Database_SQL $sql;

	/**
	 * Data Type
	 *
	 * @var Database_Data_Type
	 */
	protected Database_Data_Type $data_type;

	/**
	 *
	 * @var string
	 */
	private string $internal_name = '';

	/**
	 * Internal query timer
	 *
	 * @var Timer
	 */
	protected Timer $timer;

	/**
	 * URL for the current connection
	 *
	 * @var string
	 */
	protected string $URL = '';

	/**
	 * Parsed URL
	 */
	protected array $url_parts = [];

	/**
	 * URL without password
	 *
	 * @var null|string
	 */
	protected ?string $safe_url = null;

	/**
	 * Class to use for singleton creation
	 *
	 * @var string
	 */
	protected string $singleton_prefix;

	/**
	 * For auto table, cache of class name -> table name
	 *
	 * @var array of string => string
	 */
	private array $tableNameCache = [];

	/**
	 * Options to be passed to new objects when generating table names.
	 *
	 * @var array
	 */
	private array $auto_table_names_options = [];

	/**
	 * Construct a new Database
	 *
	 * @param Application $application
	 * @param string $url
	 * @param array $options
	 * @throws Exception_Key
	 * @throws Exception_Syntax
	 */
	public function __construct(Application $application, string $url = '', array $options = []) {
		parent::__construct($application, $options);
		$this->inheritConfiguration();
		$this->initialize();
		if ($url) {
			$this->_initURL($url);
		}
	}

	/**
	 * @return void
	 */
	protected function initialize(): void {
		// pass
	}

	/**
	 * Factory for native database code parser
	 *
	 * @return Database_Parser
	 */
	public function parser(): Database_Parser {
		return $this->parser;
	}

	/**
	 * Factory for native code generator
	 *
	 * @return Database_SQL
	 */
	public function sql(): Database_SQL {
		return $this->sql;
	}

	/**
	 * Factory for native data type handler
	 *
	 * @return Database_Data_Type
	 */
	public function data_type(): Database_Data_Type {
		return $this->data_type;
	}

	/**
	 * Retrieve additional column attributes which are supported by this database, in the form
	 * array("attribute1" => "default_value1")
	 *
	 * @param Database_Column $column
	 * @return array
	 */
	public function columnAttributes(Database_Column $column): array {
		return [];
	}

	/**
	 * Retrieve additional table attributes which are supported by this database, in the form
	 * array("attribute1" => "default_value1")
	 *
	 * @return array
	 */
	public function tableAttributes(): array {
		return [];
	}

	/**
	 * Convert attribute keys into standard form and fix values as well.
	 *
	 * @param array $attributes
	 * @return array[]
	 */
	public function normalizeAttributes(array $attributes): array {
		$new_attributes = [];
		foreach ($attributes as $k => $v) {
			$k = preg_replace('/[-_]/', ' ', strtolower($k));
			$new_attributes[$k] = $v;
		}
		return $new_attributes;
	}

	/**
	 * Generator utilities - native NOW string for database
	 *
	 * @return string
	 */
	final public function now(): string {
		return $this->sql()->now();
	}

	/**
	 * Generator utilities - native NOW string for database
	 *
	 * @return string
	 */
	final public function nowUTC(): string {
		return $this->sql()->nowUTC();
	}

	/**
	 * Are table names case-sensitive?
	 *
	 * @return bool
	 */
	public function tablesCaseSensitive(): bool {
		return $this->optionBool('tables_case_sensitive', true);
	}

	/**
	 * Select a single row from a table
	 *
	 * @param string $table
	 * @param array $where
	 * @param string|array $order_by
	 * @return array
	 * @throws Database_Exception_SQL
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	public function selectOne(string $table, array $where, string|array $order_by = []): array {
		$sql = $this->sql()->select([
			'what' => '*', 'tables' => $table, 'where' => $where, 'order_by' => $order_by, 'limit' => 1, 'offset' => 0,
		]);
		return $this->queryOne($sql);
	}

	/**
	 * Change URL associated with this database and related settings
	 *
	 * @param string $url
	 * @throws Exception_Key
	 * @throws Exception_Syntax
	 */
	private function _initURL(string $url): void {
		$this->url_parts = $parts = self::urlParse($url);
		$this->setOptions($parts);
		$this->setOptions(URL::queryParse($parts['query'] ?? ''));
		$this->URL = $url;
		$this->safe_url = URL::removePassword($url);
	}

	/**
	 * Parse SQL to determine type of command
	 *
	 * @param string $sql SQL to parse
	 * @param string $field Optional desired field.
	 * @return string|array
	 */
	public function parseSQL(string $sql, string $field = ''): string|array {
		$result = $this->parser()->parseSQL($sql);
		return $field !== '' ? $result[$field] ?? '' : $result;
	}

	/**
	 * Retrieve just the command from a SQL statement
	 *
	 * @param string $sql
	 * @return string
	 */
	public function parseSQLCommand(string $sql): string {
		return $this->parseSQL($sql, 'command');
	}

	/**
	 * Given a list of SQL commands separated by ;, extract individual statements
	 *
	 * @param string $sql
	 * @return array
	 */
	public function splitSQLStatements(string $sql): array {
		return $this->parser()->splitSQLStatements($sql);
	}

	/**
	 * Convert database to string representation
	 *
	 * @see Options::__toString()
	 */
	public function __toString() {
		return $this->safe_url;
	}

	/**
	 * Internal name of Database
	 *
	 * @return string
	 */
	public function codeName(): string {
		return $this->internal_name;
	}

	/**
	 * Internal name of Database
	 *
	 * @param string $set
	 * @return $this
	 */
	public function setCodeName(string $set): self {
		$this->internal_name = $set;
		return $this;
	}

	/**
	 * Retrieve URL or url component
	 *
	 * @return string
	 */
	public function url(): string {
		return $this->URL;
	}

	/**
	 * Retrieve URL or url component
	 *
	 * @param string $component
	 * @return string
	 * @throws Exception_Key
	 */
	public function urlComponent(string $component): string {
		if (array_key_exists($component, $this->url_parts)) {
			return $this->url_parts[$component];
		}

		throw new Exception_Key($component);
	}

	/**
	 * Database Type (specifically, the URI scheme)
	 *
	 * @return string
	 */
	final public function type(): string {
		try {
			return $this->urlComponent('scheme');
		} catch (Exception_Key) {
			/* not reachable */
			return '';
		}
	}

	/**
	 * Name of the database
	 *
	 * @return string
	 */
	public function databaseName(): string {
		try {
			return ltrim($this->urlComponent('path'), '/');
		} catch (Exception_Key) {
			/* not reachable */
			return '';
		}
	}

	/**
	 * Parse a Database URL into components
	 *
	 * @param string $url Database URL
	 * @param string|null $component Component to fetch from the result
	 * @return array|string
	 * @throws Exception_Key
	 * @throws Exception_Syntax
	 */
	public static function urlParse(string $url, string $component = null): array|string {
		$parts = URL::parse($url);
		if (!$parts) {
			return $parts;
		}
		$parts['name'] = trim($parts['path'] ?? '', '/ ');
		if ($component === null) {
			return $parts;
		}
		if (array_key_exists($component, $parts)) {
			return $parts[$component];
		}

		throw new Exception_Key($component);
	}

	/**
	 * Change the URL for this database.
	 * Useful for pointing an existing Database instance to a slave for read-only operations, etc.
	 *
	 * @param string $url
	 * @return self
	 * @throws Database_Exception_Connect
	 * @throws Exception_Key
	 * @throws Exception_Syntax
	 */
	public function changeURL(string $url): self {
		$connected = $this->connected();
		if ($connected) {
			$this->disconnect();
		}
		$this->_initURL($url);
		if ($connected) {
			$this->connect();
		}
		return $this;
	}

	/**
	 * Returns the connection URL with the password removed
	 *
	 * @param string $filler
	 *            To put garbage in place of the password, pass in what should appear instead (e.g.
	 *            "*****")
	 * @return string
	 */
	final public function safeURL(string $filler = ''): string {
		$parts = $this->url_parts;
		if ($filler) {
			$parts['pass'] = $filler;
		} else {
			unset($parts['pass']);
		}
		return URL::stringify($parts);
	}

	/**
	 * Connect to the database
	 *
	 * @return self
	 * @throws Database_Exception_Connect
	 */
	final public function connect(): self {
		$this->internalConnect();
		$this->callHook('connect');
		if ($this->optionBool('debug')) {
			$this->application->logger->debug('Connected to database: {safeURL}', ['safeURL' => $this->safeURL()]);
		}
		return $this;
	}

	/**
	 * @return bool
	 */
	public function connected(): bool {
		return $this->connection() !== null;
	}

	/**
	 * Connect to the database
	 *
	 * @return void
	 * @throws Database_Exception_Connect
	 */
	abstract public function internalConnect(): void;

	/**
	 * Disconnect from the database
	 *
	 * @return void
	 */
	abstract public function internalDisconnect(): void;

	/**
	 * Get or set a feature of the database.
	 * See const feature_foo defined above.
	 *
	 * Can use custom database strings.
	 *
	 * @param string $feature
	 * @return mixed Feature settings
	 * @throws Exception_Key
	 */
	abstract public function feature(string $feature): mixed;

	/**
	 * @param string $feature
	 * @param string|bool $set
	 * @return $this
	 * @throws Exception_Key
	 * @throws Exception_Invalid
	 * @throws Exception_Syntax
	 */
	abstract public function setFeature(string $feature, string|bool $set): self;

	public const OPTION_DEBUG = 'debug';

	public const HOOK_DISCONNECT = 'disconnect';

	/**
	 * Disconnect from database
	 */
	public function disconnect(): void {
		if ($this->optionBool(self::OPTION_DEBUG)) {
			$this->application->logger->debug('Disconnecting from database {url}', ['url' => $this->safeURL(), ]);
		}
		$this->callHook(self::HOOK_DISCONNECT);
		$this->internalDisconnect();
	}

	/**
	 * Retrieve raw database connection.
	 * Return null if not connected.
	 *
	 * @return mixed|null
	 */
	abstract public function connection(): mixed;

	/**
	 * Run a database shell command to perform various actions. Valid options are:
	 *
	 * "force" boolean
	 * "sql-dump-command" boolean
	 * "tables" array
	 *
	 * @param array $options
	 * @return array Output lines if successful
	 * @throws Exception_Command
	 */
	abstract public function shellCommand(array $options = []): array;

	/**
	 * Reconnect the database
	 * @throws Database_Exception_Connect
	 */
	public function reconnect(): self {
		$this->disconnect();
		return $this->connect();
	}

	/**
	 * Can I create another database in the current connection?
	 *
	 */
	public function can(string $permission): bool {
		return false;
	}

	/**
	 * Create a new database with the current connection
	 *
	 * @param string $url
	 * @param array $hosts
	 * @return bool
	 * @throws Exception_Unimplemented
	 */
	public function createDatabase(string $url, array $hosts): bool {
		throw new Exception_Unimplemented(get_class($this) . "::createDatabase($url)");
	}

	/**
	 * Does this table exist?
	 *
	 */
	abstract public function tableExists(string $tableName): bool;

	/**
	 * Retrieve a list of tables from the database
	 *
	 * @return array
	 * @throws Exception_Unimplemented
	 */
	public function listTables(): array {
		throw new Exception_Unimplemented('{method} in {class}', [
			'method' => __METHOD__, 'class' => get_class($this),
		]);
	}

	/**
	 * Output a file which is a database dump of the database
	 *
	 * @param string $filename The path to where the database should be dumped
	 * @param array $options Options for dumping the database - dependent on database type
	 * @throws Exception_File_Permission
	 * @throws Exception_Directory_Permission
	 * @throws Exception_Unsupported
	 * @throws Exception_Command
	 */
	abstract public function dump(string $filename, array $options = []): void;

	/**
	 * Given a database file, restore the database
	 *
	 * @param string $filename A file to restore the database from
	 * @param array $options Options for dumping the database - dependent on database type
	 * @throws Exception_File_Permission
	 * @throws Exception_File_NotFound
	 * @throws Exception_Unsupported
	 * @throws Exception_Command
	 */
	abstract public function restore(string $filename, array $options = []): void;

	/**
	 * Switches to another database in this connection.
	 *
	 * Not supported by all databases.
	 *
	 * @param string $name
	 * @return Database
	 */
	abstract public function selectDatabase(string $name): self;

	/**
	 * Create a Database_Table object from the database's schema
	 *
	 * @param string $tableName
	 *            A database table name
	 * @return Database_Table The database table parsed from the database's definition of a table
	 * @throws Database_Exception_Table_NotFound
	 */
	abstract public function databaseTable(string $tableName): Database_Table;

	/**
	 * Create a Database_Table object from a CREATE TABLE SQL statement
	 *
	 * @param string $sql
	 *            A CREATE TABLE sql command
	 * @param string $source
	 *            Debugging information as to where the SQL originated
	 * @return Database_Table The database table parsed from the sql command
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_Parameter
	 */
	public function parseCreateTable(string $sql, string $source = ''): Database_Table {
		$parser = Database_Parser::parseFactory($this, $sql, $source);
		return $parser->createTable($sql);
	}

	/**
	 * Execute a SQL statement with this database
	 *
	 * @param string $sql
	 *            A SQL statement
	 * @param array $options
	 *            Settings, options for this query
	 *
	 * @return QueryResult A resource or boolean value which represents the result of the query
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_Table_NotFound
	 * @throws Database_Exception_NoResults
	 */
	abstract public function query(string $sql, array $options = []): QueryResult;

	/**
	 * Execute multiple SQL statements with this database
	 *
	 * @param array $queries
	 * @param array $options Settings, options for all queries
	 *
	 * @return array
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_Table_NotFound
	 */
	final public function queries(array $queries, array $options = []): array {
		$result = [];
		foreach ($queries as $index => $query) {
			$result[$index] = $this->query($query, $options);
		}
		return $result;
	}

	/**
	 * Replace functionality
	 *
	 * @param string $table
	 * @param array $values
	 * @param array $options
	 *            Database-specific options
	 *
	 * @return integer
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_Table_NotFound
	 */
	public function replace(string $table, array $values, array $options = []): int {
		$sql = $this->sql()->insert($table, $values, ['verb' => 'REPLACE', ] + $options);
		$result = $this->query($sql);
		$id = $this->insertID($result);
		$this->free($result);
		return $id;
	}

	/**
	 * Execute an INSERT SQL statement
	 *
	 * @param string $table
	 * @param array $columns
	 * @param array $options
	 * @return int Returns -1 if insertion successful but no ID fetched
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 */
	public function insert(string $table, array $columns, array $options = []): int {
		$sql = $this->sql()->insert($table, $columns, $options);
		$result = $this->query($sql);
		$id = ($options['id'] ?? true) ? $this->insertID($result) : -1;
		$result->free();
		return $id;
	}

	/**
	 * Clean up any loose data from a database query.
	 * Frees any resources from the query.
	 *
	 * @param mixed $result
	 *            The result of a query command.
	 * @return void
	 * @see SQLite3\Database::query
	 */
	abstract public function free(QueryResult $result): void;

	/**
	 * After an insert statement, retrieves the most recent statement's insertion ID
	 *
	 * @param QueryResult $result
	 * @return int
	 */
	abstract public function insertID(QueryResult $result): int;

	/**
	 * Given a database select result, fetch a row as a 0-indexed array
	 *
	 * @param mixed $result
	 * @return ?array
	 */
	abstract public function fetchArray(QueryResult $result): ?array;

	/**
	 * Given a database select result, fetch a row as a name/value array
	 *
	 * @param mixed $result
	 * @return ?array
	 */
	abstract public function fetchAssoc(QueryResult $result): ?array;

	/**
	 * Retrieve a single field or fields from the database
	 *
	 * @param string $sql
	 * @param string|int|null $field
	 *            A named field, or an integer index to retrieve
	 * @param array $options
	 * @return string
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 */
	final public function queryOne(string $sql, string|int $field = null, array $options = []): mixed {
		$res = $this->query($sql, $options);
		$row = is_numeric($field) ? $this->fetchArray($res) : $this->fetchAssoc($res);
		$this->free($res);
		if (!is_array($row)) {
			throw new Database_Exception_NoResults($this, $sql, 'No results', ['field' => $field]);
		}
		if ($field === null) {
			return $row;
		}
		if (!array_key_exists($field, $row)) {
			throw new Exception_Key('{field} missing in query row: {available}', [
				'field' => $field, 'available' => array_keys($row),
			]);
		}
		return $row[$field];
	}

	/**
	 * Retrieve a single row which should contain an integer
	 *
	 * @param string $sql
	 * @param int|string|null $field
	 * @return int
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 */
	final public function queryInteger(string $sql, int|string $field = null): int {
		$result = $this->queryOne($sql, $field);
		return intval($result);
	}

	/**
	 * Internal implementation of queryArray and queryArrayIndex
	 *
	 * @param string $method
	 *            Fetch method
	 * @param mixed $sql
	 *            Query to execute
	 * @param string $k
	 *            Use this column as a result key in the resulting array
	 * @param string $v
	 *            Use this column as the value in the resulting array
	 * @return array mixed
	 */
	/**
	 * @param string $method
	 * @param string $sql
	 * @param string|int|null $k
	 * @param string|int|null $v
	 * @return array
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 */
	private function _queryArray(string $method, string $sql, string|int $k = null, string|int $v = null): array {
		$res = $this->query($sql);
		$result = [];
		if ($k === null) {
			while (is_array($row = $this->$method($res))) {
				$result[] = ($v === null) ? $row : ($row[$v] ?? null);
			}
		} else {
			$rowindex = 0;
			while (is_array($row = $this->$method($res))) {
				$result[$row[$k] ?? $rowindex] = $v === null ? $row : ($row[$v] ?? null);
				$rowindex++;
			}
		}
		$this->free($res);
		return $result;
	}

	/**
	 * Retrieve rows as name-based array and index keys or values
	 *
	 * @param mixed $sql
	 *            Query to execute
	 * @param string|int|null $k
	 *            Use this column as a result key in the resulting array
	 * @param string|int|null $v
	 *            Use this column as the value in the resulting array
	 * @return array mixed
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 */
	final public function queryArray(string $sql, string|int $k = null, string|int $v = null): array {
		return $this->_queryArray('fetchAssoc', $sql, $k, $v);
	}

	/**
	 * Retrieve rows as order-based array and index keys or values
	 *
	 * @param mixed $sql
	 *            Query to execute
	 * @param string|int|null $k
	 *            Use this column as a result key in the resulting array
	 * @param string|int|null $v
	 *            Use this column as the value in the resulting array
	 * @return array mixed
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 */
	final public function queryArrayIndex(string $sql, string|int $k = null, string|int $v = null): array {
		return $this->_queryArray('fetchArray', $sql, $k, $v);
	}

	/**
	 * Enter description here...
	 *
	 * @param string $word
	 * @return bool
	 */
	public function isReservedWord(string $word): bool {
		return false;
	}

	/**
	 * Enter description here...
	 *
	 * @param bool $value
	 * @return string
	 * @todo is-this-used
	 */
	public function sqlParseBoolean(mixed $value): string {
		return $value ? '1' : '0';
	}

	/**
	 * Begin a transaction in the database
	 *
	 * @return void
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_Table_NotFound
	 */
	public function transactionStart(): void {
		// TODO: Ensure database is in auto-commit mode
		// TODO Move to subclasses
		$this->query('START TRANSACTION');
	}

	/**
	 * Finish transaction in the database
	 *
	 * @param boolean $success
	 *            Whether to commit (true) or roll back (false)
	 * @return void
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_Table_NotFound
	 */
	public function transactionEnd(bool $success = true): void {
		// TODO Move to subclasses
		$sql = $success ? 'COMMIT' : 'ROLLBACK';
		$this->query($sql);
	}

	public function defaultEngine() {
		return $this->option('table_type_default');
	}

	public function defaultIndexStructure(string $table_type): string {
		return $this->option('index_structure_default');
	}

	/**
	 * Factory method to allow subclasses of Database to create Database_Table subclasses
	 *
	 * @param string $table
	 *            Name of the table
	 * @param string $type Type of table structure (e.g. MyISAM, InnoDB, etc.)
	 * @return Database_Table Newly created Database_Table
	 */
	public function newDatabaseTable(string $table, string $type = ''): Database_Table {
		return new Database_Table($this, $table, $type);
	}

	/**
	 * Retrieve the database table prefix
	 *
	 * @return string A string which is pre-pended to some database table names
	 */
	public function tablePrefix(): string {
		return $this->option('table_prefix', '');
	}

	/**
	 * Update a table
	 *
	 * @param string $table
	 * @param array $values
	 * @param array $where
	 * @param array $options
	 * @return QueryResult
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_Table_NotFound
	 * @throws Database_Exception_NoResults
	 */
	public function update(string $table, array $values, array $where = [], array $options = []): QueryResult {
		$sql = $this->sql()->update(['table' => $table, 'values' => $values, 'where' => $where, ] + $options);
		return $this->query($sql);
	}

	/**
	 * Run a delete query
	 * @param string $table
	 * @param array $where
	 * @param array $options
	 * @return QueryResult
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_Table_NotFound
	 */
	public function delete(string $table, array $where = [], array $options = []): QueryResult {
		$sql = $this->sql()->delete($table, $where, $options);
		return $this->query($sql);
	}

	/**
	 * @param QueryResult $result
	 * @return int
	 */
	abstract public function affectedRows(QueryResult $result): int;

	/**
	 * @param string $text
	 * @return string
	 */
	public function quoteName(string $text): string {
		return $this->sql()->quoteColumn($text);
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function unquoteColumn(string $name): string {
		return $this->sql()->unquoteColumn($name);
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function quoteTable(string $text): string {
		return $this->sql()->quoteTable($text);
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function quoteText(string $text): string {
		return $this->sql()->quoteText($text);
	}

	/**
	 * Quote text
	 *
	 * @param string $text
	 * @return string
	 */
	abstract public function nativeQuoteText(string $text): string;

	/**
	 * Utility function to unquote a table
	 *
	 * @param string $text
	 * @return string
	 */
	public function unquoteTable(string $text): string {
		return $this->sql()->unquoteTable($text);
	}

	private function _validSQLName(string $name): bool {
		return preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $name) !== 0;
	}

	public function validIndexName(string $name): bool {
		return self::_validSQLName($name);
	}

	public function validColumnName(string $name): bool {
		return self::_validSQLName($name);
	}

	/**
	 * Retrieve table columns
	 *
	 * @param string $tableName
	 * @return array
	 * @throws Exception_Unsupported
	 */
	public function tableColumns(string $tableName): array {
		throw new Exception_Unsupported();
	}

	/**
	 * Retrieve table column, if exists
	 *
	 * @param string $tableName
	 * @param string $column
	 * @return Database_Column
	 * @throws Exception_Key
	 * @throws Exception_Unsupported
	 */
	public function tableColumn(string $tableName, string $column): Database_Column {
		$columns = $this->tableColumns($tableName);
		if (array_key_exists($column, $columns)) {
			return $columns[$column];
		}

		throw new Exception_Key($column);
	}

	/**
	 * Should be called before running queries in subclasses
	 *
	 * @param string $query
	 *            The query
	 * @param array $options
	 *            Various options
	 * @return mixed
	 */
	final protected function _queryBefore(string $query, array $options): string {
		$do_log = ($options['log'] ?? false) || $this->optionBool('log');
		$debug = ($options['debug'] ?? false) || $this->optionBool('debug');
		if ($debug && $do_log) {
			$this->application->logger->debug($query);
		}
		if (($options['auto_table_names'] ?? false) || $this->optionBool('auto_table_names')) {
			$query = $this->autoTableNamesReplace($query);
		}
		if ($do_log) {
			$this->timer = new Timer();
		}
		return $query;
	}

	/**
	 * Should be called after running queries in subclasses
	 *
	 * @param string $query
	 * @param array $options
	 */
	final protected function _queryAfter(string $query, array $options): void {
		$do_log = ($options[self::OPTION_LOG_ENABLED] ?? false) || $this->optionBool(self::OPTION_LOG_ENABLED);
		if ($do_log) {
			$elapsed = $this->timer->elapsed();
			$level = ($elapsed > $this->optionFloat(self::OPTION_SLOW_QUERY_SECONDS, self::DEFAULT_OPTION_SLOW_QUERY_SECONDS)) ? 'warning' : 'debug';
			$this->application->logger->log($level, 'Elapsed: {elapsed}, SQL: {sql}', [
				'elapsed' => $elapsed, 'sql' => str_replace("\n", ' ', $query),
			]);
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
	 *            A return value to save undo information
	 * @return string SQL with strings removed
	 */
	public static function removeStringTokens(string $sql, mixed &$state): string {
		$unstrung = strtr($sql, ['\\\'' => chr(1), ]);
		$matches = null;
		if (!preg_match_all('/\'[^\']*\'/', $unstrung, $matches, PREG_PATTERN_ORDER)) {
			$state = null;
			return $sql;
		}
		$state = [];
		// When $replace is a long string, say, 29000 characters or more, can not do array_flip
		// PHP has a limit on the key size
		foreach ($matches[0] as $index => $match) {
			$search = "#\$%$index%\$#";
			$replace = strtr($match, [chr(1) => '\\\'', ]);
			$state[$search] = $replace;
			$sql = str_replace($replace, $search, $sql);
		}
		return $sql;
	}

	/**
	 * Undo the "removeStringTokens" step, exactly
	 *
	 * @param string $sql
	 * @param mixed $state
	 * @return string SQL after strings are put back in
	 */
	public static function replaceStringTokens(string $sql, mixed $state): string {
		if (!is_array($state)) {
			return $sql;
		}
		return strtr($sql, $state);
	}

	/**
	 * Getter for whether SQL is converted to use table names from class names in {} in SQL
	 *
	 * @return bool
	 */
	public function autoTableNames(): bool {
		return $this->optionBool(self::OPTION_AUTO_TABLE_NAMES);
	}

	/**
	 * Setter for whether SQL is converted to use table names from class names in {} in SQL
	 *
	 * @param bool $set
	 * @return self
	 */
	public function setAutoTableNames(bool $set): self {
		return $this->setOption(self::OPTION_AUTO_TABLE_NAMES, $set);
	}

	/**
	 * @return array
	 */
	public function autoTableNamesOptions(): array {
		return $this->auto_table_names_options;
	}

	/**
	 * Getter/setter for auto_table_names options, passed to object creation for ALL tables for
	 * table
	 *
	 * @param array $set
	 * @return Database
	 */
	public function setAutoTableNamesOptions(array $set): self {
		$this->auto_table_names_options = $set;
		return $this;
	}

	public function autoTableRenameIterable(iterable $iter, array $options = []): iterable {
		$result = [];
		foreach ($iter as $sql) {
			if (is_string($sql) || (is_object($sql) && method_exists($sql, '__toString'))) {
				$result[] = self::autoTableRename(strval($sql), $options);
			}
		}
		return $result;
	}

	public function autoTableRename(string $sql, array $options = []): string {
		$matches = [];
		$state = null;
		$sql = self::removeStringTokens($sql, $state);
		$sql = map($sql, $this->tableNameCache, true);
		if (!preg_match_all('/\{([A-Za-z][A-Za-z0-9_]*)(\*?)}/', $sql, $matches, PREG_SET_ORDER)) {
			return self::replaceStringTokens($sql, $state);
		}
		$options = $options + $this->auto_table_names_options;
		$map = $this->tableNameCache;
		foreach ($matches as $match) {
			[$full_match, $class, $no_cache] = $match;
			// Possible bug: How do we NOT cache table name replacements which are parameterized?, e.g Site_5343 - table {Site} should not cache this result, right?
			// TODO
			$table = $this->application->ormRegistry($class, null, $options)->table();
			if (count($options) === 0 && $no_cache !== '*') {
				$this->tableNameCache[$full_match] = $table;
			}
			$map[$full_match] = $this->quoteTable($table);
		}
		$sql = strtr($sql, $map);
		return self::replaceStringTokens($sql, $state);
	}

	/**
	 * Convert SQL and replace table names magically.
	 *
	 * @param iterable|string $sql
	 * @param array $options
	 * @return iterable|string
	 * @todo Move this to a module using hooks in Module_Database
	 */
	public function autoTableNamesReplace(iterable|string $sql, array $options = []): iterable|string {
		if (is_array($sql)) {
			return $this->autoTableRenameIterable($sql, $options);
		}
		return $this->autoTableRename($sql, $options);
	}

	/**
	 * Set time zone
	 *
	 * @param string|DateTimeZone $zone
	 * @return self
	 * @throws Exception_Unsupported
	 */
	public function setTimeZone(string|DateTimeZone $zone): self {
		throw new Exception_Unsupported('Database {class} does not support {feature}', [
			'class' => get_class($this), 'feature' => self::FEATURE_TIME_ZONE_RELATIVE_TIMESTAMP,
		]);
	}

	/**
	 * @return string
	 * @throws Exception_Unsupported
	 */
	public function timeZone(): string {
		throw new Exception_Unsupported('Database {class} does not support {feature}', [
			'class' => get_class($this), 'feature' => self::FEATURE_TIME_ZONE_RELATIVE_TIMESTAMP,
		]);
	}

	/**
	 * Return variables related to the Database object
	 *
	 * @return array
	 */
	public function variables(): array {
		return $this->url_parts + [
			'type' => $this->type(), 'url' => $this->URL, 'safeURL' => $this->safe_url, 'code' => $this->codeName(),
			'code_name' => $this->codeName(),
		];
	}

	/**
	 * Handle database-specific differences between two columns
	 *
	 * @param Database_Column $self
	 *            Database column being compared
	 * @param Database_Column $that
	 *            Database column being compared to
	 * @return array Any additional diffs
	 */
	abstract public function columnDifferences(Database_Column $self, Database_Column $that): array;

	/**
	 * Returns an array of TABLE_INFO constants, or null if not found
	 *
	 * @param string $tableName
	 * @return array
	 */
	abstract public function tableInformation(string $tableName): array;

	/**
	 * Does this database support URL schemes as passed in?
	 *
	 * @param string $scheme
	 * @return boolean
	 */
	public function supportsScheme(string $scheme): bool {
		try {
			$class = $this->application->databaseModule()->getRegisteredScheme($scheme);
			return $this instanceof $class;
		} catch (Exception_Key) {
			return false;
		}
	}
}
