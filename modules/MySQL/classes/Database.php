<?php
declare(strict_types=1);

/**
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */

namespace MySQL;

use DateTimeZone;
use mysqli;
use mysqli_result;
use mysqli_sql_exception;
use zesk\ArrayTools;
use zesk\Database\QueryResult as DatabaseQueryResult;
use zesk\Database_Column;
use zesk\Database_Exception_Connect;
use zesk\Database_Exception_Database_NotFound;
use zesk\Database_Exception_Duplicate;
use zesk\Database_Exception_NoResults;
use zesk\Database_Exception_Permission;
use zesk\Database_Exception_SQL;
use zesk\Database_Exception_Table_NotFound;
use zesk\Database_Table;
use zesk\Directory;
use zesk\Exception;
use zesk\Exception_Class_NotFound;
use zesk\Exception_Command;
use zesk\Exception_Convert;
use zesk\Exception_Directory_Create;
use zesk\Exception_Directory_Permission;
use zesk\Exception_File_NotFound;
use zesk\Exception_File_Permission;
use zesk\Exception_Key;
use zesk\Exception_NotFound;
use zesk\Exception_Parameter;
use zesk\Exception_Semantics;
use zesk\Exception_Syntax;
use zesk\Exception_Timeout;
use zesk\PHP;
use zesk\Timestamp;

/**
 *
 * @package zesk
 * @subpackage system
 */
class Database extends \zesk\Database {
	/**
	 * Default path to store MySQL credentials for CLI
	 */
	public const OPTION_CREDENTIALS_PATH = 'credentialsPath';

	/**
	 * Option for permissions for the directory
	 */
	public const OPTION_CREDENTIALS_PATH_PERMISSIONS = 'credentialsPathPermissions';

	/**
	 * Default permissions for directory when created
	 */
	public const DEFAULT_CREDENTIALS_PATH_PERMISSIONS = 0o700;

	/**
	 * @var bool
	 */
	protected bool $isConnected = false;

	/**
	 * List of options for shell command generation
	 *
	 * @var array
	 */
	private static array $shell_command_options = [
		'sql-dump-command' => 'boolean. Generate a command-line SQL dump command instead of a connection command',
		'tables' => 'string[]. Used in conjunction with sql-dump-command - an array of tables to dump',
		'non-blocking' => 'boolean. Used in conjunction with sql-dump-command - dump database in a non-blocking manner.',
	];

	/**
	 *
	 * @var string
	 */
	protected string $singleton_prefix = __CLASS__;

	/**
	 * Should we reconnect automatically if we are disconnected?
	 *
	 * @var boolean
	 */
	protected bool $auto_reconnect = false;

	/**
	 * Database connection
	 *
	 * @var mysqli
	 */
	protected mysqli $connection;

	/**
	 *
	 * @var string
	 */
	public const attribute_default_charset = 'default charset';

	/**
	 *
	 * @var string
	 */
	public const ATTRIBUTE_CHARACTER_SET = 'character set';

	/**
	 *
	 * @var string
	 */
	public const ATTRIBUTE_COLLATION = 'collate';

	/**
	 * Current MySQL version
	 *
	 * @var string
	 */
	public const ATTRIBUTE_VERSION = 'version';

	/**
	 *
	 * @var string
	 */
	public const ATTRIBUTE_ENGINE = 'engine';

	// utf8mb4 is the future
	//
	// After MySQL 5.5.2 use:
	// ALTER DATABASE databaseName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
	// ALTER TABLE tableName CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
	//
	// Before or using MySQL 5.5.2 use:
	// ALTER DATABASE databaseName CHARACTER SET utf8 COLLATE utf8_unicode_ci;
	// ALTER TABLE tableName CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;

	// For utf8
	//
	// After MySQL 5.5.2 use:
	// ALTER DATABASE databaseName CHARACTER SET utf8 COLLATE utf8_unicode_ci;
	// ALTER TABLE tableName CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
	//

	/**
	 *
	 * @var string
	 */
	public const DEFAULT_CHARACTER_SET = 'utf8';

	/**
	 *
	 * @var string
	 */
	public const defaultCollation = 'utf8_unicode_ci';

	/**
	 * Current selected database
	 *
	 * @var string
	 */
	private string $current_database = '';

	/**
	 *
	 * @var array
	 */
	private static array $mysql_variables = [
		self::ATTRIBUTE_ENGINE => '@@default_storage_engine',
		self::ATTRIBUTE_CHARACTER_SET => '@@character_set_database',
		self::ATTRIBUTE_COLLATION => '@@collation_database',
		self::ATTRIBUTE_VERSION => '@@version',
	];

	/**
	 * @return void
	 */
	protected function initialize(): void {
		$this->connection = new mysqli();
		$this->isConnected = false;
		$this->parser = new Database_Parser($this);
		$this->sql = new Database_SQL($this);
		$this->data_type = new Database_Type($this);
	}

	/**
	 * Retrieve a database setting and store it locally as an option
	 *
	 * @param string $attribute
	 * @param bool $force
	 * @return string
	 * @throws Database_Exception_SQL
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	private function _fetchSetting(string $attribute, bool $force = false): string {
		if (!$force && $this->hasOption($attribute)) {
			return $this->option($attribute);
		}
		if (!array_key_exists($attribute, self::$mysql_variables)) {
			throw new Exception_Semantics('No such MySQL variable for attribute {attribute}', compact('attribute'));
		}
		$variable = self::$mysql_variables[$attribute];
		$value = $this->queryOne("select $variable", 0);
		$this->setOption($attribute, $value);
		return $value;
	}

	/**
	 * @throws Database_Exception_SQL
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	public function defaultEngine(): string {
		return $this->_fetchSetting(self::ATTRIBUTE_ENGINE);
	}

	/**
	 * @throws Database_Exception_SQL
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	public function defaultCharacterSet(): string {
		return $this->_fetchSetting(self::ATTRIBUTE_CHARACTER_SET);
	}

	/**
	 * @throws Exception_Semantics
	 * @throws Exception_Key
	 * @throws Database_Exception_SQL
	 */
	public function defaultCollation(): string {
		return $this->_fetchSetting(self::ATTRIBUTE_COLLATION);
	}

	/**
	 * Retrieve additional column attributes which are supported by this database, in the form
	 * array("attribute1" => "default_value1")
	 *
	 * @param Database_Column $column
	 * @return array
	 * @throws Database_Exception_SQL
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	public function columnAttributes(Database_Column $column): array {
		$attributes = [];
		if ($column->sqlType() === 'timestamp') {
			if ($column->notNull()) {
				$attributes['default'] = 'CURRENT_TIMESTAMP';
			} else {
				$attributes['default'] = null;
			}
		}
		$sql_type = $column->sqlType();
		if (str_ends_with($sql_type, 'blob') || str_ends_with($sql_type, 'text')) {
			if ($column->null()) {
				$attributes['default'] = null;
			}
		}
		$attributes['extra'] = null;
		$table = $column->table();
		return $attributes + [
			self::ATTRIBUTE_CHARACTER_SET => $table->option(self::ATTRIBUTE_CHARACTER_SET, $this->defaultCharacterSet()),
			self::ATTRIBUTE_COLLATION => $table->option(self::ATTRIBUTE_COLLATION, $this->defaultCollation()),
		];
	}

	/**
	 * Handle database-specific differences between two columns
	 *
	 * @param Database_Column $self
	 * @param Database_Column $that
	 * @return array
	 */
	public function columnDifferences(Database_Column $self, Database_Column $that): array {
		if ($self->isText()) {
			return $self->attributes_differences($this, $that, [
				self::ATTRIBUTE_CHARACTER_SET, self::ATTRIBUTE_COLLATION,
			]);
		}
		return [];
	}

	/**
	 * Retrieve additional table attributes which are supported by this database, in the form
	 * array("attribute1" => "default_value1")
	 *
	 * @return array
	 * @throws Database_Exception_SQL
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	public function tableAttributes(): array {
		return [
			self::ATTRIBUTE_ENGINE => $this->option(self::ATTRIBUTE_ENGINE, $this->defaultEngine()),
			self::attribute_default_charset => $this->option(self::attribute_default_charset, $this->defaultCharacterSet()),
			self::ATTRIBUTE_COLLATION => $this->option(self::ATTRIBUTE_COLLATION, $this->defaultCollation()),
		];
	}

	/**
	 *
	 * @param string $name
	 * @return Database
	 * @throws Database_Exception_Connect
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Parameter
	 * @see \zesk\SQLite3\Database::selectDatabase()
	 */
	public function selectDatabase(string $name): self {
		if ($name === '') {
			$name = $this->databaseName();
		}
		if ($this->current_database === $name) {
			return $this;
		}

		$this->query('USE ' . $this->sql()->quoteTable($name));
		$this->current_database = $name;
		return $this;
	}

	/**
	 * Retrieve the table's column definitions
	 *
	 * @param string $tableName
	 * @return array
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @see \zesk\SQLite3\Database::tableColumns()
	 */
	public function tableColumns(string $tableName): array {
		$columns = [];
		$table_object = new Database_Table($this, $tableName);
		$result = $this->queryArray('DESC ' . $this->quoteTable($tableName), 'Field');
		foreach ($result as $name => $row) {
			$row = array_change_key_case($row);
			$columns[$name] = $col = new Database_Column($table_object, $name);
			if ($row['type'] ?? null) {
				$col->setSQLType($row['type']);
			}
			$extra = $row['extra'] ?? '';
			$col->setIncrement(str_contains($extra, 'auto_increment'));
			$col->setDefaultValue($row['default'] ?? null);
			$col->setNotNull(!toBool($row['null'] ?? null));
		}
		return $columns;
	}

	/**
	 * Dump a database to $path using mysqldump
	 *
	 * @param string $filename
	 * @param array $options
	 *            "lock" => boolean Lock tables before dumping (avoids inconsistent state - bad for
	 *            busy databases)
	 *            "tables" => list of tables Dump these tables
	 * @throws Exception_Command
	 * @throws Exception_File_Permission
	 * @see \zesk\SQLite3\Database::dump()
	 */
	public function dump(string $filename, array $options = []): void {
		$parts = $this->url_parts;

		$parts['port'] = toInteger($parts['port'] ?? null, 3306);
		$database = $this->databaseName();

		$tables = toList($options['tables'] ?? []);

		$cmd_options = [
			'--add-drop-table', '-c', '--host={host}', '--port={port}', '--password={pass}', '--user={user}',
		];
		$lock_first = $options['lock'] ?? false;
		if ($lock_first) {
			$cmd_options[] = '--lock-tables';
		}
		$cmd_options[] = $database;
		$cmd_options[] = implode(' ', $tables);
		$cmd = 'mysqldump ' . implode(' ', $cmd_options) . ' > {filename}';
		$this->application->process->executeArguments($cmd, $parts + [
			'filename' => $filename,
		]);
		if (!file_exists($filename)) {
			throw new Exception_File_Permission($filename, '{command} failed to generate {path}', [
				'command' => $cmd,
			]);
		}
	}

	public const PORT_DEFAULT = 3306;

	/**
	 * Restore a database from $path using mysql command-line tool
	 *
	 * @param string $filename
	 * @param array $options
	 * @throws Exception_File_NotFound|Exception_Command
	 * @see \zesk\SQLite3\Database::restore()
	 */
	public function restore(string $filename, array $options = []): void {
		if (!file_exists($filename)) {
			throw new Exception_File_NotFound($filename);
		}
		$parts = $this->url_parts;

		$parts['port'] = toInteger($parts['port'] ?? self::PORT_DEFAULT, self::PORT_DEFAULT);
		$database = $this->databaseName();

		$cmd_options = [
			'--host={host}', '--port={port}', '--password={pass}', '--user={user}',
		];
		$cmd_options[] = $database;
		$cmd = 'mysql ' . implode(' ', $cmd_options) . ' < {filename}';
		$this->application->process->executeArguments($cmd, $parts + [
			'filename' => $filename,
		]);
	}

	/**
	 * Default port
	 *
	 * @var int
	 */
	public const DEFAULT_PORT = 3306;

	/**
	 *
	 * @throws Database_Exception_Connect
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 * @see \zesk\SQLite3\Database::internalConnect()
	 */
	final public function internalConnect(): void {
		$parts = $this->url_parts;

		$server = $parts['host'] ?? null;
		$port = $parts['port'] ?? null;
		$user = $parts['user'] ?? null;
		$password = $parts['pass'] ?? null;
		$database = substr($parts['path'] ?? null, 1);

		if (!$port) {
			$port = self::DEFAULT_PORT;
		}
		$this->_mysql_connect($server, $user, $password, $database, $port);

		$this->setOption('Database', $database);
		$this->setOption('User', $user);
		$this->setOption('Port', $port);
		$this->setOption('Server', $server);

		$character_set = $this->option(self::ATTRIBUTE_CHARACTER_SET, self::DEFAULT_CHARACTER_SET);
		if ($character_set) {
			$sql = "SET NAMES '$character_set'";
			$collate = $this->option(self::ATTRIBUTE_COLLATION, self::defaultCollation);
			if ($collate) {
				$sql .= " COLLATE '$collate'";
			}
			$this->query($sql);
		}
		$this->_versionSettings();
	}

	/**
	 * @throws Exception_Semantics
	 * @throws Exception_Key
	 * @throws Database_Exception_SQL
	 */
	private function _versionSettings(): void {
		$this->_fetchSetting(self::ATTRIBUTE_VERSION, true);

		$version = $this->optionString('version');
		if (preg_match('/([0-9]+)\.([0-9]+)\.([0-9]+)/', $version, $matches)) {
			[$_, $major, $minor] = $matches;
			if ($major !== '5' && $major !== '8') {
				return;
			}
			if ($minor <= 6) {
				$this->setOption('invalid_dates_ok', true);
			}
		}
	}

	/**
	 * Connection error
	 *
	 * @param array $words
	 *            Tokens for error message
	 * @throws Database_Exception_Connect
	 */
	protected function _connection_error(array $words): void {
		if (!array_key_exists('error', $words)) {
			$words['error'] = mysqli_error($this->connection);
		}
		if (!array_key_exists('errno', $words)) {
			$words['errno'] = mysqli_errno($this->connection);
		}
		$errno = intval($words['errno']);
		$locale = $this->application->locale;
		if ($errno === 1049) {
			throw new Database_Exception_Database_NotFound($this->URL, $locale->__("mysql\Database:=Can not connect to {database} at {server}:{port} as {user} (MySQL Error: #{errno} {error})"), $words, $words['errno']);
		}

		throw new Database_Exception_Connect($this->URL, $locale->__("mysql\Database:=Can not connect to {database} at {server}:{port} as {user} (MySQL Error: #{errno} {error})"), $words, $words['errno']);
	}

	/**
	 * Throw a MySQL Error
	 *
	 * @param string $query
	 * @param int $errno
	 * @param string $message
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_Table_NotFound
	 */
	protected function _mysql_throw_error(string $query, int $errno, string $message): void {
		if ($errno == 1062) {
			$match = false;
			if (preg_match('/key ([0-9]+)/', $message, $match)) {
				$match = intval($match[1]);
			}
			if (empty($match)) {
				$match = -1;
			} else {
				$match -= 1;
			}

			throw new Database_Exception_Duplicate($this, $query, $message, $this->variables(), $errno);
		} elseif ($errno === 1146) {
			throw new Database_Exception_Table_NotFound($this, $query, $message, $this->variables(), $errno);
		} else {
			throw new Database_Exception_SQL($this, $query, $message, $this->variables(), $errno);
		}
	}

	/*
	 * Database capabilities
	 */
	public function can(string $permission): bool {
		return match ($permission) {
			self::FEATURE_LIST_TABLES, self::FEATURE_TIME_ZONE_RELATIVE_TIMESTAMP, self::FEATURE_CREATE_DATABASE => true,
			default => false,
		};
	}

	/**
	 * @return string
	 */
	public function timeZone(): string {
		try {
			return $this->queryOne('SELECT @@time_zone as tz', 0);
		} catch (Exception_Key|Database_Exception_SQL) {
			return 'UTC';
		}
	}

	/**
	 * Set time zone
	 *
	 * @param string|DateTimeZone $zone
	 * @return self
	 * @throws Database_Exception_Connect
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Parameter
	 */
	public function setTimeZone(string|DateTimeZone $zone): self {
		$this->query('SET time_zone=' . $this->quoteText(strval($zone)));
		return $this;
	}

	/**
	 * Create a database at URL
	 *
	 * @param string $url
	 * @param array $hosts
	 * @return bool
	 * @throws Database_Exception_Connect
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Syntax
	 * @see \zesk\Database::createDatabase()
	 */
	public function createDatabase(string $url, array $hosts): bool {
		$parts = parse_url($url);

		$server = $parts['host'] ?? null;
		$user = $parts['user'] ?? null;
		$password = $parts['pass'] ?? null;
		$database = ltrim($part['path'] ?? '', '/');
		if (!$database) {
			throw new Exception_Syntax('Invalid URL - no database name');
		}
		if (!$user) {
			throw new Exception_Syntax('Invalid URL - no user provided');
		}
		if (!$password) {
			throw new Exception_Syntax('Invalid URL - no password provided');
		}
		$query = "CREATE DATABASE IF NOT EXISTS $database;";
		if (!$this->query($query)) {
			return false;
		}
		$hosts[] = $server;
		$hosts = array_unique($hosts);
		foreach ($hosts as $host) {
			$query = "GRANT ALL PRIVILEGES ON `$database`.* TO `$user`@`$host` IDENTIFIED BY '" . addslashes($password) . '\' WITH GRANT OPTION;';
			if (!$this->query($query)) {
				return false;
			}
		}
		$query = 'FLUSH PRIVILEGES;';
		if (!$this->query($query)) {
			return false;
		}
		return true;
	}

	/**
	 * List tables
	 *
	 * @return array
	 * @throws Database_Exception_Connect
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Parameter
	 * @see \zesk\SQLite3\Database::listTables()
	 */
	public function listTables(): array {
		$result = $this->query('SHOW TABLES');
		$tables = [];
		$caseSensitive = $this->tablesCaseSensitive();
		if ($caseSensitive) {
			while (($arr = $this->fetchArray($result)) != false) {
				$tables[$arr[0]] = $arr[0];
			}
		} else {
			while (($arr = $this->fetchArray($result)) != false) {
				$tables[strtolower($arr[0])] = $arr[0];
			}
		}
		return $tables;
	}

	/**
	 * @param string $tableName
	 * @param string|null $sql
	 * @return string
	 * @throws Database_Exception_Connect
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Parameter
	 */
	private function showCreateTable(string $tableName, string &$sql = null): string {
		$sql = 'SHOW CREATE TABLE ' . $this->quoteTable($tableName);

		try {
			$result = $this->query($sql);
			$row = $this->fetchArray($result);
		} catch (Database_Exception_NoResults $e) {
			throw new Database_Exception_Table_NotFound($this, $tableName, $sql, [], 0, $e);
		}
		if (count($row) === 0) {
			throw new Database_Exception_Table_NotFound($this, $tableName);
		}
		$data = $row[1];
		$this->free($result);
		return $data;
	}

	/**
	 *
	 * @param string $tableName
	 * @return array
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Convert
	 * @see \zesk\SQLite3\Database::tableInformation
	 * @todo Move into type\Database_Table
	 */
	final public function tableInformation(string $tableName): array {
		try {
			$arr = $this->queryOne("SHOW TABLE STATUS LIKE '$tableName'");
		} catch (Database_Exception_Duplicate|Database_Exception_NoResults|Exception_Key $e) {
			throw new Database_Exception_Table_NotFound($this, $tableName, '', [], 0, $e);
		}
		return [
			self::TABLE_INFO_ENGINE => $arr['Engine'] ?? $arr['Type'] ?? null,
			self::TABLE_INFO_ROW_COUNT => $arr['Rows'],
			self::TABLE_INFO_DATA_SIZE => $arr['Data_length'],
			self::TABLE_INFO_INDEX_SIZE => $arr['Index_length'],
			self::TABLE_INFO_FREE_SIZE => $arr['Data_free'],
			self::TABLE_INFO_CREATED => $arr['Create_time'] ? Timestamp::factory($arr['Create_time']) : null,
			self::TABLE_INFO_UPDATED => $arr['Update_time'] ? Timestamp::factory($arr['Update_time']) : null,
		];
	}

	/**
	 * @param string $tableName
	 * @return Database_Table
	 * @throws Database_Exception_Connect
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_Parameter
	 */
	public function databaseTable(string $tableName): Database_Table {
		$source = '';
		$sql = $this->showCreateTable($tableName, $source);
		if (!$sql) {
			throw new Database_Exception_Table_NotFound($this, $tableName);
		}
		return $this->parseCreateTable($sql, $source);
	}

	/*
	 * Boolean Type
	 */
	public function sqlParseBoolean(mixed $value): string {
		return $value ? '\'true\'' : '\'false\'';
	}

	/**
	 * @see \zesk\Database::defaultIndexStructure()
	 * @see \zesk\SQLite3\Database::defaultIndexStructure()
	 */
	public function defaultIndexStructure(string $table_type): string {
		switch (strtolower($table_type)) {
			case 'memory':
			case 'heap':
				return 'HASH';
			default:
			case 'myisam':
			case 'innodb':
				return 'BTREE';
		}
	}

	/**
	 * Figure out how many rows a query will hit
	 *
	 * @param string $sql Statement to estimate
	 * @return int
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 */
	public function estimate_rows(string $sql): int {
		$rows = $this->queryArray("EXPLAIN $sql");
		$n = 1;
		foreach ($rows as $row) {
			$x = $row['rows'] ?? null;
			if (!empty($x)) {
				$n *= $x;
			}
		}
		return $n;
	}

	/**
	 * @param string $tableName
	 * @return bool
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 */
	public function tableExists(string $tableName): bool {
		if (empty($tableName)) {
			return false;
		}
		$result = $this->queryArray('SHOW TABLES LIKE ' . $this->quoteText($tableName));
		return (count($result) !== 0);
	}

	/**
	 *
	 * @param string $user
	 * @param string $pass
	 * @return string
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_Permission
	 */
	private function credentialsFile(string $user, string $pass): string {
		$directory = $this->option(self::OPTION_CREDENTIALS_PATH, $this->application->paths->userHome('mysql'));
		Directory::depend($directory, $this->option(self::OPTION_CREDENTIALS_PATH_PERMISSIONS, self::DEFAULT_CREDENTIALS_PATH_PERMISSIONS));
		$name = md5($user . ':' . $pass) . '.cnf';
		$full = path($directory, $name);
		if (is_readable($full)) {
			return $full;
		}
		file_put_contents($full, "[client]\nuser=$user\npassword=$pass\n");
		chmod($full, 0o400);
		return $full;
	}

	/**
	 * @param array $options
	 * @return array
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_Permission
	 */
	public function shellCommand(array $options = []): array {
		foreach ($options as $option_key => $option_value) {
			if (!array_key_exists($option_key, self::$shell_command_options)) {
				$this->application->logger->warning('Unknown option passed to {method}: {option_key}={option_value}', [
					'method' => __METHOD__, 'option_key' => $option_key, 'option_value' => _dump($option_value),
				]);
			}
		}

		$parts = $this->url_parts;
		$host = $parts['host'] ?? null;
		$user = $parts['user'] ?? null;
		$pass = $parts['pass'] ?? null;
		$path = $parts['path'] ?? '';
		$args = [];
		if ($user && $pass) {
			if ($this->optionBool('password-on-command-line')) {
				$args[] = '-u';
				$args[] = $user;
				$args[] = "-p$pass";
			} else {
				$args[] = '--defaults-extra-file=' . $this->credentialsFile($user, $pass);
			}
		}
		if ($host) {
			$args[] = '-h';
			$args[] = $host;
		}
		if (toBool($options['force'] ?? false)) {
			$args[] = '-f';
		}
		$path = substr($path, 1);
		$args[] = $path;

		$bin = 'mysql';
		if (toBool($options['sql-dump-command'] ?? false)) {
			$bin = 'mysqldump';
			if (isset($options['non-blocking']) && toBool($options['non-blocking'])) {
				$args = array_merge($args, [
					'--single-transaction=TRUE',
				]);
			}
			$tables = toList($options['tables'] ?? []);
			$args = array_merge($args, $tables);
		}
		return [
			$bin, $args,
		];
	}

	/**
	 * MySQL locking
	 *
	 * @param string $name
	 * @param int $wait_seconds
	 * @throws Database_Exception_SQL
	 * @throws Exception_Timeout
	 * @throws Exception_Key
	 */
	public function getLock(string $name, int $wait_seconds = 0): void {
		$name = $this->quoteText($name);
		if ($this->queryInteger("SELECT GET_LOCK($name, $wait_seconds)", 0) === 0) {
			throw new Exception_Timeout('After {seconds}', ['seconds' => $wait_seconds]);
		}
	}

	/**
	 * MySQL Release lock
	 *
	 * @param string $name
	 * @throws Database_Exception_SQL
	 * @throws Exception_Key
	 * @throws Exception_Semantics
	 */
	public function releaseLock(string $name): void {
		$name = $this->quoteText($name);
		$result = $this->queryOne("SELECT RELEASE_LOCK($name)", 0);
		if (intval($result) !== 1) {
			throw new Exception_Semantics('Released lock {name} FAILED (raw_result={raw_result}): ', [
				'name' => $name, 'backtrace' => debug_backtrace(),
				'raw_result' => PHP::dump($result),
			]);
		}
	}

	/**
	 * @param string $word
	 * @return bool
	 */
	public function isReservedWord(string $word): bool {
		// Updated 2004-10-19 from MySQL Website YEARLY-TODO
		static $reserved = [
			'ADD', 'ALL', 'ALTER', 'ANALYZE', 'AND', 'AS', 'ASC', 'ASENSITIVE', 'BEFORE', 'BETWEEN', 'BIGINT', 'BINARY',
			'BLOB', 'BOTH', 'BY', 'CALL', 'CASCADE', 'CASE', 'CHANGE', 'CHAR', 'CHARACTER', 'CHECK', 'COLLATE',
			'COLUMN', 'COLUMNS', 'CONDITION', 'CONNECTION', 'CONSTRAINT', 'CONTINUE', 'CONVERT', 'CREATE', 'CROSS',
			'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURRENT_USER', 'CURSOR', 'DATABASE', 'DATABASES',
			'DAY_HOUR', 'DAY_MICROSECOND', 'DAY_MINUTE', 'DAY_SECOND', 'DEC', 'DECIMAL', 'DECLARE', 'DEFAULT',
			'DELAYED', 'DELETE', 'DESC', 'DESCRIBE', 'DETERMINISTIC', 'DISTINCT', 'DISTINCTROW', 'DIV', 'DOUBLE',
			'DROP', 'DUAL', 'EACH', 'ELSE', 'ELSEIF', 'ENCLOSED', 'ESCAPED', 'EXISTS', 'EXIT', 'EXPLAIN', 'FALSE',
			'FETCH', 'FIELDS', 'FLOAT', 'FOR', 'FORCE', 'FOREIGN', 'FOUND', 'FROM', 'FULLTEXT', 'GOTO', 'GRANT',
			'GROUP', 'HAVING', 'HIGH_PRIORITY', 'HOUR_MICROSECOND', 'HOUR_MINUTE', 'HOUR_SECOND', 'IF',
			// WL #7395			"IGNORE",
			'IN', 'INDEX', 'INFILE', 'INNER', 'INOUT', 'INSENSITIVE', 'INSERT', 'INT', 'INTEGER', 'INTERVAL', 'INTO',
			'IS', 'ITERATE', 'JOIN', 'KEY', 'KEYS', 'KILL', 'LEADING', 'LEAVE', 'LEFT', 'LIKE', 'LIMIT', 'LINES',
			'LOAD', 'LOCALTIME', 'LOCALTIMESTAMP', 'LOCK', 'LONG', 'LONGBLOB', 'LONGTEXT', 'LOOP', 'LOW_PRIORITY',
			'MATCH', 'MEDIUMBLOB', 'MEDIUMINT', 'MEDIUMTEXT', 'MIDDLEINT', 'MINUTE_MICROSECOND', 'MINUTE_SECOND', 'MOD',
			'NATURAL', 'NOT', 'NO_WRITE_TO_BINLOG', 'NULL', 'NUMERIC', 'ON', 'OPTIMIZE', 'OPTION', 'OPTIONALLY', 'OR',
			'ORDER', 'OUT', 'OUTER', 'OUTFILE', 'PRECISION', 'PRIMARY', 'PRIVILEGES', 'PROCEDURE', 'PURGE', 'READ',
			'REAL', 'REFERENCES', 'REGEXP', 'RENAME', 'REPEAT', 'REPLACE', 'REQUIRE', 'RESTRICT', 'RETURN', 'REVOKE',
			'RIGHT', 'RLIKE', 'SCHEMA', 'SCHEMAS', 'SECOND_MICROSECOND', 'SELECT', 'SENSITIVE', 'SEPARATOR', 'SET',
			'SHOW', 'SMALLINT', 'SONAME', 'SPATIAL', 'SPECIFIC', 'SQL', 'SQLEXCEPTION', 'SQLSTATE', 'SQLWARNING',
			'SQL_BIG_RESULT', 'SQL_CALC_FOUND_ROWS', 'SQL_SMALL_RESULT', 'SSL', 'STARTING', 'STRAIGHT_JOIN', 'TABLE',
			'TABLES', 'TERMINATED', 'THEN', 'TINYBLOB', 'TINYINT', 'TINYTEXT', 'TO', 'TRAILING', 'TRIGGER', 'TRUE',
			'UNDO', 'UNION', 'UNIQUE', 'UNLOCK', 'UNSIGNED', 'UPDATE', 'USAGE', 'USE', 'USING', 'UTC_DATE', 'UTC_TIME',
			'UTC_TIMESTAMP', 'VALUES', 'VARBINARY', 'VARCHAR', 'VARCHARACTER', 'VARYING', 'WHEN', 'WHERE', 'WHILE',
			'WITH', 'WRITE', 'XOR', 'YEAR_MONTH', 'ZEROFILL',
		];
		$word = strtoupper($word);
		return in_array($word, $reserved);
	}

	/**
	 * @param string $name
	 * @return string
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 */
	private function _variable(string $name): string {
		return $this->queryOne('SHOW VARIABLES LIKE ' . $this->quoteText($name), 0);
	}

	/**
	 * @param string $name
	 * @param string $set
	 * @return void
	 * @throws Database_Exception_Connect
	 * @throws Database_Exception_Permission
	 * @throws Exception_Parameter
	 */
	private function _setVariable(string $name, string $set): void {
		try {
			$this->query("SET GLOBAL $name=" . $this->quoteText($set));
		} catch (Database_Exception_SQL $e) {
			throw new Database_Exception_Permission('Unable to set global {name}', [
				'name' => $name,
			], 0, $e);
		}
	}

	/**
	 * @return string
	 */
	public function version(): string {
		/* This is set up in version_settings() */
		return $this->optionString('version');
	}

	/**
	 * @param string $feature
	 * @return mixed
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_NoResults
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @see \zesk\SQLite3\Database::feature
	 */
	public function feature(string $feature): mixed {
		switch ($feature) {
			case self::FEATURE_MAX_BLOB_SIZE:
				return toInteger($this->_variable('max_allowed_packet'));
			case self::FEATURE_CROSS_DATABASE_QUERIES:
				return true;
		}

		throw new Exception_NotFound('Feature {feature} not available in database {name}', [
			'feature' => $feature, 'name' => $this->type(),
		]);
	}

	/**
	 * @param string $feature
	 * @param mixed $set
	 * @return $this
	 * @throws Database_Exception_Connect
	 * @throws Database_Exception_Permission
	 * @throws Exception_NotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Semantics
	 */
	public function setFeature(string $feature, mixed $set): self {
		switch ($feature) {
			case self::FEATURE_MAX_BLOB_SIZE:
				$this->_setVariable('max_allowed_packet', strval($set));
				return $this;
			case self::FEATURE_CROSS_DATABASE_QUERIES:
				throw new Exception_Semantics('Can not set {feature}', ['feature' => $feature]);
		}

		throw new Exception_NotFound('Feature {feature} not available in database {name}', [
			'feature' => $feature, 'name' => $this->type(),
		]);
	}

	/**
	 * @param string $tableName
	 * @return int
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Key
	 */
	public function bytesUsed(string $tableName = ''): int {
		if ($tableName !== '') {
			if (!$this->tableExists($tableName)) {
				throw new Database_Exception_Table_NotFound($this, $tableName);
			}
			return $this->queryOne("SHOW TABLE STATUS LIKE '$tableName'", 'Data_length');
		} else {
			$total = 0;
			foreach ($this->queryArray('SHOW TABLE STATUS', null, 'Data_length') as $data_length) {
				$total += $data_length;
			}
			return $total;
		}
	}

	/**
	 * @return mysqli
	 */
	final public function connection(): mysqli {
		return $this->connection;
	}

	// 		MYSQLI_CLIENT_COMPRESS	    Use compression protocol
	// 		MYSQLI_CLIENT_FOUND_ROWS	return number of matched rows, not the number of affected rows
	// 		MYSQLI_CLIENT_IGNORE_SPACE	Allow spaces after function names. Makes all function names reserved words.
	// 		MYSQLI_CLIENT_INTERACTIVE	Allow interactive_timeout seconds (instead of wait_timeout seconds) of inactivity before closing the connection
	// 		MYSQLI_CLIENT_SSL           Use SSL
	private static array $flag_map = [
		'compress' => MYSQLI_CLIENT_COMPRESS, 'found rows' => MYSQLI_CLIENT_FOUND_ROWS,
		'ignore space' => MYSQLI_CLIENT_IGNORE_SPACE, 'interactive' => MYSQLI_CLIENT_INTERACTIVE,
		'ssl' => MYSQLI_CLIENT_SSL,
	];

	/**
	 * Internal connection method
	 *
	 * @param string $server
	 * @param string $user
	 * @param string $password
	 * @param string $database
	 * @param int $port
	 * @return resource
	 * @throws Database_Exception_Connect
	 */
	final protected function _mysql_connect(string $server, string $user, string $password, string $database, int $port): void {
		$conn = $this->connection; //@new mysqli($server, $user, $password, $database, $port);
		if ($this->optionBool('infile')) {
			mysqli_options($conn, MYSQLI_OPT_LOCAL_INFILE, true);
		}
		mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, $this->optionInt('connect_timeout', 5));
		$flags = $this->option('connect_flags', 0);
		if (is_numeric($flags)) {
			$flags = intval($flags);
		} elseif (is_string($flags) || is_array($flags)) {
			$flag_tokens = ArrayTools::changeValueCase(toList($flags));
			$flags = 0;
			foreach ($flag_tokens as $token) {
				if (!array_key_exists($token, self::$flag_map)) {
					$this->application->logger->warning('Unknown flag {token} in {method}: possible flags are {flags}', [
						'method' => __METHOD__, 'token' => $token, 'flags' => array_keys(self::$flag_map),
					]);
				} else {
					$flags |= self::$flag_map[$token];
				}
			}
		} else {
			$this->application->logger->warning('Unknown connect_flags option value type passed to {method} {type}', [
				'method' => __METHOD__, 'type' => gettype($flags),
			]);
			$flags = 0;
		}
		$args = [
			'server' => $server, 'user' => $user, 'database' => $database, 'port' => $port,
		];
		if (!@mysqli_real_connect($conn, $server, $user, $password, $database, $port, null, $flags)) {
			$error = mysqli_connect_error();
			if ($error) {
				$this->_connection_error($args + [
					'error' => $error, 'errno' => mysqli_connect_errno(),
				]);
			}

			throw new Database_Exception_Connect($this->url(), "Connection to database $user@$server:$port/$database FAILED, no connection error", $args);
		}
		$this->isConnected = true;
	}

	public function autoReconnect(): bool {
		return $this->auto_reconnect;
	}

	public function setAutoReconnect(bool $set): self {
		$this->auto_reconnect = toBool($set);
		return $this;
	}

	final public function internalDisconnect(): void {
		if ($this->isConnected) {
			mysqli_close($this->connection);
			$this->connection = mysqli_init();
		}
		$this->isConnected = false;
	}

	/**
	 * @return bool
	 */
	public function connected(): bool {
		if (!$this->isConnected) {
			return false;
		}
		$info = @$this->connection->get_server_info();
		if (empty($info)) {
			return false;
		}
		return true;
	}

	/**
	 * Main query entry point
	 *
	 *
	 * @param string $sql
	 * @param array $options
	 * @return DatabaseQueryResult
	 * @throws Database_Exception_Connect
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Parameter
	 * @see \zesk\SQLite3\Database::_query()
	 */
	final public function query(string $sql, array $options = []): DatabaseQueryResult {
		if (empty($sql)) {
			throw new Exception_Parameter('Empty query');
		}
		if (!$this->connected()) {
			if ($options['auto_connect'] ?? $this->optionBool('auto_connect', true)) {
				$this->connect();
			} else {
				throw new Database_Exception_Connect($this->safe_url, 'Not connected and auto_connect disabled');
			}
		}
		$tries = 0;
		do {
			$sql = $this->_queryBefore($sql, $options);

			try {
				$result = mysqli_query($this->connection, $sql);
			} catch (mysqli_sql_exception $exception) {
				$exception_code = $exception->getCode();
				$message = $exception->getMessage();
				if ($exception_code === 1062 || stripos($message, 'duplicate') !== false) {
					throw new Database_Exception_Duplicate($this, $sql, $exception->getMessage(), [], $exception_code, $exception);
				}
				if ($exception_code === 1146) {
					throw new Database_Exception_Table_NotFound($this, $sql);
				}

				throw new Database_Exception_SQL($this, $sql, $exception->getMessage(), [
					'sql' => $sql,
				] + Exception::exceptionVariables($exception), $exception->getCode(), $exception);
			}
			$this->_queryAfter($sql, $options);
			if ($result) {
				return new QueryResult($this, $result);
			}
			$message = mysqli_error($this->connection);
			$errno = mysqli_errno($this->connection);
			if ($errno === 2006 && $this->auto_reconnect) { /* CR_SERVER_GONE_ERROR */
				$this->application->logger->warning('Reconnecting to database {url}', [
					'url' => $this->safeURL(),
				]);
				$this->reconnect();
			} else {
				break;
			}
		} while (++$tries < 10);
		$this->_mysql_throw_error($sql, $errno, $message);
	}

	/**
	 * @param DatabaseQueryResult $result
	 * @return int
	 * @throws Exception_Semantics
	 * @see \zesk\SQLite3\Database::affectedRows()
	 */
	final public function affectedRows(DatabaseQueryResult $result): int {
		if (!$this->isConnected) {
			throw new Exception_Semantics('Not connected');
		}
		$resource = $result->resource();
		if ($resource instanceof mysqli_result) {
			throw new Exception_Semantics('Query has results');
		}
		return $this->connection->affected_rows;
	}

	final public function free(DatabaseQueryResult $result): void {
		$result->free();
	}

	/**
	 * @param DatabaseQueryResult $result
	 * @return int
	 * @throws Exception_Semantics
	 * @see \zesk\SQLite3\Database::insertID
	 */
	final public function insertID(DatabaseQueryResult $result): int {
		$id = mysqli_insert_id($this->connection);
		if ($id === 0) {
			throw new Exception_Semantics('No insert ID');
		}
		return intval($id);
	}

	/**
	 * @param QueryResult $result
	 * @return array|null
	 * @throws Database_Exception_NoResults
	 * @see \zesk\SQLite3\Database::fetchAssoc
	 */
	final public function fetchAssoc(DatabaseQueryResult $result): ?array {
		$result = mysqli_fetch_assoc($result->resource());
		if ($result === false) {
			throw new Database_Exception_NoResults($this, 'fetchAssoc failed');
		}
		return $result;
	}

	/**
	 * @param DatabaseQueryResult $result
	 * @return array|null
	 * @throws Database_Exception_NoResults
	 */
	final public function fetchArray(DatabaseQueryResult $result): ?array {
		$result = mysqli_fetch_array($result->resource(), MYSQLI_NUM);
		if ($result === false) {
			throw new Database_Exception_NoResults($this, 'fetchArray failed');
		}
		return $result;
	}

	/**
	 * @param string $text
	 * @return string
	 * @throws Database_Exception_Connect
	 * @see \zesk\SQLite3\Database::nativeQuoteText()
	 */
	final public function nativeQuoteText(string $text): string {
		if (!$this->isConnected) {
			$this->connect();
		}
		return '\'' . $this->connection->real_escape_string($text) . '\'';
	}

	/**
	 * Begin a transaction in the database
	 *
	 * @return void
	 * @throws Database_Exception_Connect
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Parameter
	 * @see \zesk\SQLite3\Database::transactionStart()
	 */
	public function transactionStart(): void {
		// TODO: Ensure database is in auto-commit mode
		$this->query('START TRANSACTION');
	}

	/**
	 * Finish transaction in the database
	 *
	 * @param boolean $success
	 *            Whether to commit (true) or roll back (false)
	 * @return void
	 * @throws Database_Exception_Connect
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Parameter
	 */
	public function transactionEnd(bool $success = true): void {
		$sql = $success ? 'COMMIT' : 'ROLLBACK';
		$this->query($sql);
	}
}
