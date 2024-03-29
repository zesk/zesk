<?php
declare(strict_types=1);
/**
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */

namespace zesk\Database;

use DateTimeZone;
use zesk\Application;
use zesk\Database\Exception\Connect;
use zesk\Database\Exception\Duplicate;
use zesk\Database\Exception\NoResults;
use zesk\Database\Exception\SQLException;
use zesk\Database\Exception\TableNotFound;
use zesk\Exception\ClassNotFound;
use zesk\Exception\CommandFailed;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FileNotFound;
use zesk\Exception\FilePermission;
use zesk\Exception\KeyNotFound;
use zesk\Exception\Unimplemented;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\Semantics;
use zesk\Exception\SyntaxException;
use zesk\Exception\Unsupported;
use zesk\Hookable;
use zesk\Timer;
use zesk\URL;

/**
 *
 * @package zesk
 * @subpackage system
 */
abstract class Base extends Hookable implements DatabaseInterface {
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
	 * @var SQLParser
	 */
	protected SQLParser $_sqlParser;

	/**
	 * SQL Generation
	 *
	 * @var SQLDialect
	 */
	protected SQLDialect $_sqlDialect;

	/**
	 * Data Type
	 *
	 * @var Types
	 */
	protected Types $_types;

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
	 * Construct a new Database
	 *
	 * @param Application $application
	 * @param string $url
	 * @param array $options
	 * @throws KeyNotFound
	 * @throws SyntaxException
	 */
	public function __construct(Application $application, string $url = '', array $options = []) {
		parent::__construct($application, $options);
		$this->inheritConfiguration();
		if ($url) {
			$this->_initURL($url);
		}
		$this->initialize();
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
	 * @return SQLParser
	 */
	public function sqlParser(): SQLParser {
		return $this->_sqlParser;
	}

	/**
	 * Factory for native code generator
	 *
	 * @return SQLDialect
	 */
	public function sqlDialect(): SQLDialect {
		return $this->_sqlDialect;
	}

	/**
	 * Factory for native data type handler
	 *
	 * @return Types
	 */
	public function types(): Types {
		return $this->_types;
	}

	/**
	 * Retrieve additional column attributes which are supported by this database, in the form
	 * array("attribute1" => "default_value1")
	 *
	 * @param Column $column
	 * @return array
	 */
	public function columnAttributes(Column $column): array {
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
		return $this->sqlDialect()->now();
	}

	/**
	 * Generator utilities - native NOW string for database
	 *
	 * @return string
	 */
	final public function nowUTC(): string {
		return $this->sqlDialect()->nowUTC();
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
	 * @throws SQLException
	 * @throws KeyNotFound
	 * @throws Semantics
	 */
	public function selectOne(string $table, array $where, string|array $order_by = []): array {
		$sql = $this->sqlDialect()->select([
			'what' => '*', 'tables' => $table, 'where' => $where, 'order_by' => $order_by, 'limit' => 1, 'offset' => 0,
		]);
		return $this->queryOne($sql);
	}

	/**
	 * Change URL associated with this database and related settings
	 *
	 * @param string $url
	 * @throws KeyNotFound
	 * @throws SyntaxException
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
		$result = $this->sqlParser()->parseSQL($sql);
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
		return $this->sqlParser()->splitSQLStatements($sql);
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
	 * @throws KeyNotFound
	 */
	public function urlComponent(string $component): string {
		if (array_key_exists($component, $this->url_parts)) {
			return $this->url_parts[$component];
		}

		throw new KeyNotFound($component);
	}

	/**
	 * Database Type (specifically, the URI scheme)
	 *
	 * @return string
	 */
	final public function type(): string {
		try {
			return $this->urlComponent('scheme');
		} catch (KeyNotFound) {
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
		} catch (KeyNotFound) {
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
	 * @throws KeyNotFound
	 * @throws SyntaxException
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

		throw new KeyNotFound($component);
	}

	/**
	 * Change the URL for this database.
	 * Useful for pointing an existing Database instance to a slave for read-only operations, etc.
	 *
	 * @param string $url
	 * @return self
	 * @throws Connect
	 * @throws KeyNotFound
	 * @throws SyntaxException
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
	 * @throws Connect
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
	 * @throws Connect
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
	 * @throws KeyNotFound
	 */
	abstract public function feature(string $feature): mixed;

	/**
	 * @param string $feature
	 * @param string|bool $set
	 * @return $this
	 * @throws KeyNotFound
	 * @throws SyntaxException
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
	 * @throws CommandFailed
	 */
	abstract public function shellCommand(array $options = []): array;

	/**
	 * Reconnect the database
	 * @throws Connect
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
	 * @throws Unimplemented
	 */
	public function createDatabase(string $url, array $hosts): bool {
		throw new Unimplemented(get_class($this) . "::createDatabase($url)");
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
	 * @throws Unimplemented
	 */
	public function listTables(): array {
		throw new Unimplemented('{method} in {class}', [
			'method' => __METHOD__, 'class' => get_class($this),
		]);
	}

	/**
	 * Output a file which is a database dump of the database
	 *
	 * @param string $filename The path to where the database should be dumped
	 * @param array $options Options for dumping the database - dependent on database type
	 * @throws FilePermission
	 * @throws DirectoryPermission
	 * @throws Unsupported
	 * @throws CommandFailed
	 */
	abstract public function dump(string $filename, array $options = []): void;

	/**
	 * Given a database file, restore the database
	 *
	 * @param string $filename A file to restore the database from
	 * @param array $options Options for dumping the database - dependent on database type
	 * @throws FilePermission
	 * @throws FileNotFound
	 * @throws Unsupported
	 * @throws CommandFailed
	 */
	abstract public function restore(string $filename, array $options = []): void;

	/**
	 * Switches to another database in this connection.
	 *
	 * Not supported by all databases.
	 *
	 * @param string $name
	 * @return Base
	 */
	abstract public function selectDatabase(string $name): self;

	/**
	 * Create a Table object from the database's schema
	 *
	 * @param string $tableName
	 *            A database table name
	 * @return Table The database table parsed from the database's definition of a table
	 * @throws TableNotFound
	 */
	abstract public function databaseTable(string $tableName): Table;

	/**
	 * Create a Table object from a CREATE TABLE SQL statement
	 *
	 * @param string $sql
	 *            A CREATE TABLE sql command
	 * @param string $source
	 *            Debugging information as to where the SQL originated
	 * @return Table The database table parsed from the sql command
	 * @throws ClassNotFound
	 * @throws NotFoundException
	 * @throws ParameterException
	 */
	public function parseCreateTable(string $sql, string $source = ''): Table {
		$parser = SQLParser::parseFactory($this, $sql, $source);
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
	 * @throws Duplicate
	 * @throws TableNotFound
	 * @throws NoResults
	 */
	abstract public function query(string $sql, array $options = []): QueryResult;

	/**
	 * Execute multiple SQL statements with this database
	 *
	 * @param array $queries
	 * @param array $options Settings, options for all queries
	 *
	 * @return array
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
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
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
	 */
	public function replace(string $table, array $values, array $options = []): int {
		$sql = $this->sqlDialect()->insert($table, $values, ['verb' => 'REPLACE', ] + $options);
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
	 * @throws Duplicate
	 * @throws SQLException
	 * @throws TableNotFound
	 */
	public function insert(string $table, array $columns, array $options = []): int {
		$sql = $this->sqlDialect()->insert($table, $columns, $options);
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
	 * @see self::query
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
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
	 * @throws KeyNotFound
	 */
	final public function queryOne(string $sql, string|int $field = null, array $options = []): mixed {
		$res = $this->query($sql, $options);
		$row = is_numeric($field) ? $this->fetchArray($res) : $this->fetchAssoc($res);
		$this->free($res);
		if (!is_array($row)) {
			throw new NoResults($this, $sql, 'No results', ['field' => $field]);
		}
		if ($field === null) {
			return $row;
		}
		if (!array_key_exists($field, $row)) {
			throw new KeyNotFound('{field} missing in query row: {available}', [
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
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
	 * @throws KeyNotFound
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
	 * @throws Duplicate
	 * @throws SQLException
	 * @throws TableNotFound
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
	 * @throws Duplicate
	 * @throws SQLException
	 * @throws TableNotFound
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
	 * @throws Duplicate
	 * @throws SQLException
	 * @throws TableNotFound
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
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
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
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
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
	 * Factory method to allow subclasses of Database to create Table subclasses
	 *
	 * @param string $table
	 *            Name of the table
	 * @param string $type Type of table structure (e.g. MyISAM, InnoDB, etc.)
	 * @return Table Newly created Table
	 */
	public function newDatabaseTable(string $table, string $type = ''): Table {
		return new Table($this, $table, $type);
	}

	public const OPTION_TABLE_PREFIX = 'tablePrefix';

	/**
	 * Retrieve the database table prefix
	 *
	 * @return string A string which is pre-pended to some database table names
	 */
	public function tablePrefix(): string {
		return $this->option(self::OPTION_TABLE_PREFIX, '');
	}

	/**
	 * Set the database table prefix
	 *
	 * @param string $prefix
	 * @return $this
	 */
	public function setTablePrefix(string $prefix): self {
		return $this->setOption(self::OPTION_TABLE_PREFIX, $prefix);
	}

	/**
	 * Update a table
	 *
	 * @param string $table
	 * @param array $values
	 * @param array $where
	 * @param array $options
	 * @return QueryResult
	 * @throws Duplicate
	 * @throws TableNotFound
	 * @throws NoResults
	 */
	public function update(string $table, array $values, array $where = [], array $options = []): QueryResult {
		$sql = $this->sqlDialect()->update([
			'table' => $table,
			'values' => $values,
			'where' => $where,
		] + $options);
		return $this->query($sql);
	}

	/**
	 * Run a delete query
	 * @param string $table
	 * @param array $where
	 * @param array $options
	 * @return QueryResult
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
	 */
	public function delete(string $table, array $where = [], array $options = []): QueryResult {
		$sql = $this->sqlDialect()->delete($table, $where, $options);
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
		return $this->sqlDialect()->quoteColumn($text);
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function unquoteColumn(string $name): string {
		return $this->sqlDialect()->unquoteColumn($name);
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function quoteTable(string $text): string {
		return $this->sqlDialect()->quoteTable($text);
	}

	/**
	 * @param string $text
	 * @return string
	 */
	public function quoteText(string $text): string {
		return $this->sqlDialect()->quoteText($text);
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
		return $this->sqlDialect()->unquoteTable($text);
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
	 * @throws Unsupported
	 */
	public function tableColumns(string $tableName): array {
		throw new Unsupported();
	}

	/**
	 * Retrieve table column, if exists
	 *
	 * @param string $tableName
	 * @param string $column
	 * @return Column
	 * @throws KeyNotFound
	 * @throws Unsupported
	 */
	public function tableColumn(string $tableName, string $column): Column {
		$columns = $this->tableColumns($tableName);
		if (array_key_exists($column, $columns)) {
			return $columns[$column];
		}

		throw new KeyNotFound($column);
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
	 * Set time zone
	 *
	 * @param string|DateTimeZone $zone
	 * @return self
	 * @throws Unsupported
	 */
	public function setTimeZone(string|DateTimeZone $zone): self {
		throw new Unsupported('Database {class} does not support {feature}', [
			'class' => get_class($this), 'feature' => self::FEATURE_TIME_ZONE_RELATIVE_TIMESTAMP,
		]);
	}

	/**
	 * @return string
	 * @throws Unsupported
	 */
	public function timeZone(): string {
		throw new Unsupported('Database {class} does not support {feature}', [
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
	 * @param Column $self
	 *            Database column being compared
	 * @param Column $that
	 *            Database column being compared to
	 * @return array Any additional diffs
	 */
	abstract public function columnDifferences(Column $self, Column $that): array;

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
		} catch (KeyNotFound) {
			return false;
		}
	}
}
