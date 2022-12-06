<?php
declare(strict_types=1);

/**
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */

namespace MySQL;

use zesk\Exception;
use zesk\Exception_Command;
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
use zesk\ArrayTools;
use zesk\Exception_Syntax;
use zesk\Exception_Unsupported;
use zesk\Text;
use zesk\PHP;
use zesk\Timestamp;
use zesk\Exception_Parameter;
use zesk\Database_Exception;
use zesk\Directory;
use zesk\Database\QueryResult as DatabaseQueryResult;

/**
 *
 * @package zesk
 * @subpackage system
 */
class Database extends \zesk\Database {
	/**
	 * @var bool
	 */
	protected bool $is_connected = false;

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
	 * @var \mysqli
	 */
	protected \mysqli $Connection;

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
	public const attribute_version = 'version';

	/**
	 *
	 * @var string
	 */
	public const ATTRIBUTE_ENGINE = 'engine';

	// utf8mb4 is the future
	//
	// After MySQL 5.5.2 use:
	// ALTER DATABASE databasename CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
	// ALTER TABLE tablename CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
	//
	// Before or using MySQL 5.5.2 use:
	// ALTER DATABASE databasename CHARACTER SET utf8 COLLATE utf8_unicode_ci;
	// ALTER TABLE tablename CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;

	// For utf8
	//
	// After MySQL 5.5.2 use:
	// ALTER DATABASE databasename CHARACTER SET utf8 COLLATE utf8_unicode_ci;
	// ALTER TABLE tablename CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;
	//

	/**
	 *
	 * @var string
	 */
	public const defaultCharacterSet = 'utf8';

	/**
	 *
	 * @var string
	 */
	public const defaultCollation = 'utf8_unicode_ci';

	/**
	 *
	 * @var string
	 */
	public const defaultEngine = 'InnoDB';

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
	private static $mysql_variables = [
		self::ATTRIBUTE_ENGINE => '@@default_storage_engine',
		self::ATTRIBUTE_CHARACTER_SET => '@@character_set_database',
		self::ATTRIBUTE_COLLATION => '@@collation_database',
		self::attribute_version => '@@version',
	];

	/**
	 *
	 * @var array
	 */
	private static $mysql_default_attributes = [
		self::ATTRIBUTE_ENGINE => self::defaultEngine,
		self::ATTRIBUTE_CHARACTER_SET => self::defaultCharacterSet,
		self::ATTRIBUTE_COLLATION => self::defaultEngine,
	];

	/**
	 * @return void
	 */
	protected function initialize(): void {
		$this->Connection = new \mysqli();
	}

	/**
	 * Remove comments from a block of SQL statements
	 *
	 * @param string $sql
	 * @return string
	 */
	public function removeComments($sql) {
		$sql = Text::remove_line_comments($sql, '--');
		return $sql;
	}

	/**
	 * Retrieve a database setting and store it locally as an option
	 *
	 * @param string $attribute
	 * @return mixed|string|array|string
	 * @throws Exception_Semantics
	 */
	/**
	 * @param string $attribute
	 * @return string
	 * @throws Database_Exception_SQL
	 * @throws Exception_Semantics
	 * @throws \zesk\Exception_Key
	 */
	private function _fetchSetting(string $attribute): string {
		if ($this->hasOption($attribute)) {
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

	public function defaultEngine(): string {
		return $this->_fetchSetting(self::ATTRIBUTE_ENGINE);
	}

	public function defaultCharacterSet(): string {
		return $this->_fetchSetting(self::ATTRIBUTE_CHARACTER_SET);
	}

	public function defaultCollation(): string {
		return $this->_fetchSetting(self::ATTRIBUTE_COLLATION);
	}

	/**
	 * Retrieve additional column attributes which are supported by this database, in the form
	 * array("attribute1" => "default_value1")
	 *
	 * @return array
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
	 */
	public function columnDifferences(Database_Column $self, Database_Column $that): array {
		if ($self->isText()) {
			return $self->attributes_differences($this, $that, [
				self::ATTRIBUTE_CHARACTER_SET,
				self::ATTRIBUTE_COLLATION,
			]);
		}
		return [];
	}

	/**
	 * Retrieve additional table attributes which are supported by this database, in the form
	 * array("attribute1" => "default_value1")
	 *
	 * @return array
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
	 * @return Database
	 * @see Database::selectDatabase()
	 */
	public function selectDatabase(string $name): self {
		if ($name === null) {
			$name = $this->databaseName();
		}
		if ($this->current_database === $name) {
			return $this;
		}

		try {
			$this->query('USE ' . $this->sql()->quoteTable($name));
			$this->current_database = $name;
			return $this;
		} catch (\Exception $e) {
			throw $e;
		}
	}

	/**
	 * @param $sql
	 * @return array|mixed
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 */
	public function mixed_query(array|string $sql): mixed {
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

	/**
	 * Retrieve the table's column definitions
	 *
	 * @see Database::tableColumns()
	 */
	public function tableColumns(string $table): array {
		$columns = [];
		$table_object = new Database_Table($this, $table);
		$result = $this->queryArray('DESC ' . $this->quoteTable($table), 'Field');
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
	 * @see Database::dump()
	 */
	public function dump(string $filename, array $options = []): bool {
		$parts = $this->url_parts;

		$parts['port'] = toInteger($parts['port'] ?? null, 3306);
		$database = $this->databaseName();

		$tables = toList($options['tables'] ?? []);

		$cmd_options = [
			'--add-drop-table',
			'-c',
			'--host={host}',
			'--port={port}',
			'--password={pass}',
			'--user={user}',
		];
		$lock_first = $options['lock'] ?? false;
		if ($lock_first) {
			$cmd_options[] = '--lock-tables';
		}
		$cmd_options[] = $database;
		$cmd_options[] = implode(' ', $tables);
		$result = 0;
		$cmd = 'mysqldump ' . implode(' ', $cmd_options) . ' > {filename}';
		$this->application->process->executeArguments($cmd, $parts + [
			'filename' => $filename,
		]);
		return file_exists($filename);
	}

	public const PORT_DEFAULT = 3306;

	/**
	 * Restore a database from $path using mysql command-line tool
	 *
	 * @param string $filename
	 * @param array $options
	 * @throws Exception_File_NotFound|Exception_Command
	 * @see Database::restore()
	 */
	public function restore(string $filename, array $options = []): bool {
		if (!file_exists($filename)) {
			throw new Exception_File_NotFound($filename);
		}
		$parts = $this->url_parts;

		$parts['port'] = toInteger($parts['port'] ?? self::PORT_DEFAULT, self::PORT_DEFAULT);
		$database = $this->databaseName();

		$cmd_options = [
			'--host={host}',
			'--port={port}',
			'--password={pass}',
			'--user={user}',
		];
		$cmd_options[] = $database;
		$result = 0;
		$cmd = 'mysql ' . implode(' ', $cmd_options) . ' < {filename}';
		$this->application->process->executeArguments($cmd, $parts + [
			'filename' => $filename,
		]);
		return file_exists($filename);
	}

	/**
	 *
	 * @see Database::_connect()
	 */
	final public function _connect(): void {
		$parts = $this->url_parts;

		$server = avalue($parts, 'host');
		$port = avalue($parts, 'port', null);
		$user = avalue($parts, 'user');
		$password = avalue($parts, 'pass');
		$this->Database = $database = substr(avalue($parts, 'path'), 1);

		if (!$port) {
			$port = 3306;
		}
		$this->_mysql_connect($server, $user, $password, $database, $port);

		$this->setOption('Database', $this->Database);
		$this->setOption('User', $user);
		$this->setOption('Port', $port);
		$this->setOption('Server', $server);

		$character_set = $this->option(self::ATTRIBUTE_CHARACTER_SET, self::defaultCharacterSet);
		if ($character_set) {
			$sql = "SET NAMES '$character_set'";
			$collate = $this->option(self::ATTRIBUTE_COLLATION, self::defaultCollation);
			if ($collate) {
				$sql .= " COLLATE '$collate'";
			}
			$this->query($sql);
		}
		$this->version_settings();
	}

	private function version_settings(): void {
		$this->setOption(self::attribute_version, null);
		$this->_fetchSetting(self::attribute_version);

		$version = $this->version;
		if (preg_match('/([0-9]+)\.([0-9]+)\.([0-9]+)/', $version, $matches)) {
			[$v, $major, $minor, $patch] = $matches;
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
			$words['error'] = mysqli_error($this->Connection);
		}
		if (!array_key_exists('errno', $words)) {
			$words['errno'] = mysqli_errno($this->Connection);
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
	public function can(string $feature): bool {
		switch ($feature) {
			case self::FEATURE_LIST_TABLES:
			case self::FEATURE_TIME_ZONE_RELATIVE_TIMESTAMP:
			case self::FEATURE_CREATE_DATABASE:
				return true;
		}
		return false;
	}

	/**
	 * Get/set time zone
	 *
	 * @param string $set
	 *            Time zone to Settings
	 * @return self|string
	 */
	public function time_zone($set = null) {
		return $this->timeZone();
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
	 * @param string|\DateTimeZone $zone
	 * @return \zesk\Database
	 * @throws Exception_Unsupported
	 */
	public function setTimeZone(string|\DateTimeZone $zone): self {
		$this->query('SET time_zone=' . $this->quoteText(strval($zone)));
		return $this;
	}

	/**
	 * Create a database at URL
	 *
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
	 * @see Database::listTables()
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
	 * @param $table
	 * @param string|null $sql
	 * @return string
	 * @throws Database_Exception_Connect
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Parameter
	 */
	private function showCreateTable(string $table, string &$sql = null): string {
		$sql = 'SHOW CREATE TABLE ' . $this->quoteTable($table);
		$result = $this->query($sql);
		$row = $this->fetchArray($result);
		if (count($row) === 0) {
			throw new Database_Exception_Table_NotFound($this, $table);
		}
		$data = $row[1];
		$this->free($result);
		return $data;
	}

	/**
	 *
	 * @param string $table
	 * @return array
	 * @return array
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @todo Move into type\Database_Table
	 * @see Database::tableInformation
	 */
	final public function tableInformation(string $table): array {
		$result = $this->query("SHOW TABLE STATUS LIKE '$table'");
		$arr = $this->fetchAssoc($result);
		if (!$arr) {
			throw new Database_Exception_Table_NotFound($this, $table);
		}
		$this->free($result);
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
	 * @param string $table
	 * @return Database_Table
	 * @throws Database_Exception_Connect
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Parameter
	 */
	public function databaseTable(string $table): Database_Table {
		$source = '';
		$sql = $this->showCreateTable($table, $source);
		if (!$sql) {
			throw new Database_Exception_Table_NotFound($this, "$table");
		}
		return $this->parseCreateTable($sql, $source);
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
		static $map = [
			'float' => 'double',
			'integer' => 'double',
			'boolean' => 'integer',
		];
		return avalue($map, $t0, $t0) === avalue($map, $t1, $t1);
	}

	/*
	 * Boolean Type
	 */
	public function sqlParseBoolean(mixed $value): string {
		return $value ? '\'true\'' : '\'false\'';
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Database::defaultIndexStructure()
	 */
	public function defaultIndexStructure(string $table_type): string {
		switch (strtolower($table_type)) {
			case 'innodb':
				return 'BTREE';
			case 'memory':
			case 'heap':
				return 'HASH';
			default:
			case 'myisam':
				return 'BTREE';
		}
	}

	/**
	 * Figure out how many rows a query will hit
	 *
	 * @param string $sql Statement to estimate
	 * @return integer
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
	 * MySQL parse RENAME TABLE syntax
	 *
	 * Where is this used?
	 *
	 * @param string $sql
	 * @return NULL
	 */
	public function parse_rename_table($sql) {
		$sql = preg_replace('/\s+/', ' ', rtrim(trim($sql), ';'));
		if (preg_match('/RENAME TABLE (`[^`]+`|[^` ]+) TO (`[^`]+`|[^` ]+)/i', $sql, $matches)) {
			dump($matches);
			exit(1);
		}
		return null;
	}

	public function tableExists(string $table): bool {
		if (empty($table)) {
			return false;
		}
		$result = $this->queryArray('SHOW TABLES LIKE ' . $this->quoteText($table));
		return (count($result) !== 0);
	}

	/**
	 *
	 * @param string $user
	 * @param string $pass
	 * @return string
	 */
	private function credentialsFile(string $user, string $pass): string {
		$directory = $this->option('credentials_path', $this->application->paths->uid('mysql'));
		Directory::depend($directory, $this->option('credentials_path_mode', 0o700));
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
	 * (non-PHPdoc)
	 *
	 * @see zesk\Database::shellCommand()
	 */
	public function shellCommand(array $options = []): array {
		foreach ($options as $option_key => $option_value) {
			if (!array_key_exists($option_key, self::$shell_command_options)) {
				$this->application->logger->warning('Unknown option passed to {method}: {option_key}={option_value}', [
					'method' => __METHOD__,
					'option_key' => $option_key,
					'option_value' => _dump($option_value),
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
			$bin,
			$args,
		];
	}

	/**
	 * MySQL locking
	 *
	 * @param string $name
	 * @param int $wait_seconds
	 * @return boolean
	 */
	public function getLock(string $name, int $wait_seconds = 0): bool {
		$wait_seconds = intval($wait_seconds);
		$name = $this->quoteText($name);
		return $this->queryInteger("SELECT GET_LOCK($name, $wait_seconds)", 0) === 1;
	}

	/**
	 * MySQL Release lock
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function releaseLock(string $name): bool {
		$name = $this->quoteText($name);
		$result = $this->queryOne("SELECT RELEASE_LOCK($name)", 0);
		if (intval($result) !== 1) {
			$this->application->logger->error('Released lock {name} FAILED (raw_result={raw_result}): ', [
				'name' => $name,
				'backtrace' => debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT),
				'raw_result' => PHP::dump($result),
			]);
			return false;
		}
		return true;
	}

	/**
	 * @param string $word
	 * @return bool
	 */
	public function isReservedWord(string $word): bool {
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
			// WL #7395			"IGNORE",
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

	/**
	 * @param string $name
	 * @return string
	 */
	private function _variable(string $name): string {
		return $this->queryOne('SHOW VARIABLES LIKE ' . $this->quoteText($name), 0, '');
	}

	/**
	 * @param string $name
	 * @param string $set
	 * @return void
	 * @throws Database_Exception_Permission
	 */
	private function _setVariable(string $name, string $set): void {
		try {
			$this->query("SET GLOBAL $name=" . $this->quoteText($set));
		} catch (Database_Exception_SQL $e) {
			throw new Database_Exception_Permission('Unable to set global {name}', [
				'name' => $name,
			]);
		}
	}

	/**
	 * @param string $feature
	 * @return mixed
	 * @throws Exception_NotFound
	 * @see Database::feature
	 */
	public function feature(string $feature): mixed {
		switch ($feature) {
			case self::FEATURE_MAX_BLOB_SIZE:
				return toInteger($this->_variable('max_allowed_packet'));
			case self::FEATURE_MAX_BLOB_SIZE:
				return toInteger($this->_variable('max_allowed_packet'));
			case self::FEATURE_CROSS_DATABASE_QUERIES:
				return true;
		}

		throw new Exception_NotFound('Feature {feature} not available in database {name}', [
			'feature' => $feature,
			'name' => $this->type(),
		]);
	}

	/**
	 * @param string $feature
	 * @param mixed $set
	 * @return $this
	 * @throws Database_Exception_Permission
	 * @throws Exception_NotFound
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
			'feature' => $feature,
			'name' => $this->type(),
		]);
	}

	public function bytesUsed($table = null) {
		if ($table !== null) {
			if (!$this->tableExists($table)) {
				throw new Database_Exception_Table_NotFound($this, $table);
			}
			return $this->queryOne("SHOW TABLE STATUS LIKE '$table'", 'Data_length', 0);
		} else {
			$total = 0;
			foreach ($this->queryArray('SHOW TABLE STATUS', null, 'Data_length') as $data_length) {
				$total += $data_length;
			}
			return $total;
		}
	}

	/**
	 * @return mixed
	 */
	final public function connection(): mixed {
		return $this->Connection;
	}

	// 		MYSQLI_CLIENT_COMPRESS	    Use compression protocol
	// 		MYSQLI_CLIENT_FOUND_ROWS	return number of matched rows, not the number of affected rows
	// 		MYSQLI_CLIENT_IGNORE_SPACE	Allow spaces after function names. Makes all function names reserved words.
	// 		MYSQLI_CLIENT_INTERACTIVE	Allow interactive_timeout seconds (instead of wait_timeout seconds) of inactivity before closing the connection
	// 		MYSQLI_CLIENT_SSL           Use SSL
	private static $flag_map = [
		'compress' => MYSQLI_CLIENT_COMPRESS,
		'found rows' => MYSQLI_CLIENT_FOUND_ROWS,
		'ignore space' => MYSQLI_CLIENT_IGNORE_SPACE,
		'interactive' => MYSQLI_CLIENT_INTERACTIVE,
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
	 */
	final protected function _mysql_connect(string $server, string $user, string $password, string $database, int $port): void {
		$conn = $this->Connection; //@new mysqli($server, $user, $password, $database, $port);
		if ($this->optionBool('infile')) {
			mysqli_options($conn, MYSQLI_OPT_LOCAL_INFILE, true);
		}
		mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, $this->optionInt('connect_timeout', 5));
		$flags = $this->option('connect_flags', 0);
		if (is_numeric($flags)) {
			$flags = intval($flags);
		} elseif (is_string($flags) || is_array($flags)) {
			$flag_tokens = ArrayTools::changeValueCase(to_list($flags));
			$flags = 0;
			foreach ($flag_tokens as $token) {
				if (!array_key_exists($token, self::$flag_map)) {
					$this->application->logger->warning('Unknown flag {token} in {method}: possible flags are {flags}', [
						'method' => __METHOD__,
						'token' => $token,
						'flags' => array_keys(self::$flag_map),
					]);
				} else {
					$flags |= self::$flag_map[$token];
				}
			}
		} else {
			$this->application->logger->warning('Unknown connect_flags option value type passed to {method} {type}', [
				'method' => __METHOD__,
				'type' => gettype($flags),
			]);
			$flags = 0;
		}
		$args = [
			'server' => $server,
			'user' => $user,
			'database' => $database,
			'port' => $port,
		];
		if (!@mysqli_real_connect($conn, $server, $user, $password, $database, $port, null, $flags)) {
			$error = mysqli_connect_error();
			if ($error) {
				$this->_connection_error($args + [
					'error' => $error,
					'errno' => mysqli_connect_errno(),
				]);
			}

			throw new Database_Exception_Connect($this->url(), "Connection to database $user@$server:$port/$database FAILED, no connection error", $args);
		}
		$this->is_connected = true;
	}

	public function autoReconnect(): bool {
		return $this->auto_reconnect;
	}

	public function setAutoReconnect(bool $set): self {
		$this->auto_reconnect = toBool($set);
		return $this;
	}

	final public function disconnect(): void {
		parent::disconnect();
		mysqli_close($this->Connection);
		$this->Connection = mysqli_init();
		$this->is_connected = false;
	}

	/**
	 * @return bool
	 */
	public function connected(): bool {
		if (!$this->is_connected) {
			return false;
		}
		$info = @$this->Connection->get_server_info();
		if (empty($info)) {
			return false;
		}
		return true;
	}

	/**
	 * Main query entry point
	 *
	 * {@inheritdoc}
	 *
	 * @see Database::_query()
	 */
	final public function query(string $query, array $options = []): DatabaseQueryResult {
		if (empty($query)) {
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
			$query = $this->_queryBefore($query, $options);

			try {
				$result = mysqli_query($this->Connection, $query);
			} catch (\mysqli_sql_exception $exception) {
				$exception_code = $exception->getCode();
				$message = $exception->getMessage();
				if ($exception_code === 1062 || stripos($message, 'duplicate') !== false) {
					throw new Database_Exception_Duplicate($this, $query, $exception->getMessage(), [], $exception_code, $exception);
				}
				if ($exception_code === 1146) {
					throw new Database_Exception_Table_NotFound($this, $query);
				}

				throw new Database_Exception_SQL($this, $query, $exception->getMessage(), [
					'sql' => $query,
				] + Exception::exceptionVariables($exception), $exception->getCode(), $exception);
			}
			$this->_queryAfter($query, $options);
			if ($result) {
				return new QueryResult($this, $result);
			}
			$message = mysqli_error($this->Connection);
			$errno = mysqli_errno($this->Connection);
			if ($errno === 2006 && $this->auto_reconnect) /* CR_SERVER_GONE_ERROR */ {
				$this->application->logger->warning('Reconnecting to database {url}', [
					'url' => $this->safeURL(),
				]);
				$this->reconnect();
			} else {
				break;
			}
		} while (++$tries < 10);
		$this->_mysql_throw_error($query, $errno, $message);
	}

	/**
	 * @param DatabaseQueryResult $result
	 * @return int
	 * @see Database::affectedRows()
	 */
	final public function affectedRows(DatabaseQueryResult $result): int {
		if (!$this->is_connected) {
			throw new Exception_Semantics('Not connected');
		}
		$resource = $result->resource();
		if ($resource instanceof \mysqli_result) {
			throw new Exception_Semantics('Query has results');
		}
		return $this->Connection->affected_rows;
	}

	final public function free(DatabaseQueryResult $result): void {
		$result->free();
	}

	/**
	 * @return int
	 * @throws Exception_Semantics
	 * @see Database::insertID
	 */
	final public function insertID(DatabaseQueryResult $result): int {
		$id = mysqli_insert_id($this->Connection);
		if ($id === 0) {
			throw new Exception_Semantics('No insert ID');
		}
		return intval($id);
	}

	/**
	 * @param QueryResult $result
	 * @return array|null
	 * @throws Database_Exception
	 * @see Database::fetchAssoc
	 */
	final public function fetchAssoc(DatabaseQueryResult $result): ?array {
		$result = mysqli_fetch_assoc($result->resource());
		if ($result === false) {
			throw new Database_Exception($this, 'fetchAssoc failed');
		}
		return $result;
	}

	final public function fetchArray(DatabaseQueryResult $result): ?array {
		$result = mysqli_fetch_array($result->resource(), MYSQLI_NUM);
		if ($result === false) {
			throw new Database_Exception($this, 'fetchArray failed');
		}
		return $result;
	}

	/**
	 * @param string $text
	 * @return string
	 * @see Database::nativeQuoteText()
	 */
	final public function nativeQuoteText(string $text): string {
		return '\'' . mysqli_real_escape_string($this->Connection, $text) . '\'';
	}

	/**
	 * @return bool
	 */
	final public function has_innodb() {
		$ver = mysqli_get_server_info($this->Connection);
		if (str_contains($ver, '4.0')) {
			return true;
		}
		return false;
	}

	/**
	 * Begin a transaction in the database
	 *
	 * @return boolean
	 * @see Database::transactionStart()
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
	 * @return boolean
	 */
	public function transactionEnd(bool $success = true): void {
		$sql = $success ? 'COMMIT' : 'ROLLBACK';
		$this->query($sql);
	}

	/**
	 * @param $set
	 * @return $this|bool
	 * @deprecated 2022-05
	 */
	public function auto_reconnect($set = null) {
		$this->application->deprecated(__METHOD__);
		if ($set === null) {
			return $this->autoReconnect();
		}
		return $this->setAutoReconnect(toBool($set));
	}
}
