<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 * @see Database
 * @author kent
 *
 */
class Module_Database extends Module implements Interface_Configured {
	/**
	 *
	 */
	public const OPTION_DEFAULT = 'default';

	/**
	 *
	 * @var string
	 */
	public const DEFAULT_DATABASE_NAME = 'default';

	/**
	 *
	 */
	public const OPTION_NAMES = 'names';

	/**
	 *
	 * @var string
	 */
	private string $default = '';

	/**
	 * Global database name => url mapping
	 *
	 * @var array
	 */
	private array $names = [];

	/**
	 * Global databases
	 *
	 * @var Database[]
	 */
	private array $databases = [];

	/**
	 * Mapping of scheme to class which handles it
	 *
	 * @var string[]
	 */
	private array $schemeToClass = [];

	/**
	 *
	 * @see Module::initialize
	 */
	public function initialize(): void {
		$application = $this->application;
		$application->registerRegistry('database', $this->app_database_registry(...));
		$application->registerFactory('database', $this->app_database_registry(...));
		$application->hooks->add('exit', $this->disconnectAll(...), ['last' => true]);
		$application->hooks->add('pcntl_fork-child', $this->reconnectAll(...));
	}

	/**
	 * A better hook.
	 *
	 * @return void
	 * @throws Exception_Configuration
	 * @throws Exception_Semantics
	 * @throws Exception_Syntax
	 */
	public function hook_database_configure(): void {
		$this->_configured();
	}

	/**
	 * @param bool $set
	 * @return $this
	 */
	public function setDebug(bool $set): self {
		$this->application->configuration->setPath([Database::class, Database::OPTION_DEBUG], $set);
		return $this;
	}

	/**
	 * @return bool
	 */
	public function debug(): bool {
		return toBool($this->application->configuration->getPath([Database::class, Database::OPTION_DEBUG]));
	}

	/**
	 * The name of the default database.
	 *
	 * @return string
	 */
	public function databaseDefault(): string {
		return $this->default;
	}

	/**
	 * Set database default name to use. Blank and DEFAULT_DATABASE_NAME are considered synonyms.
	 *
	 * @param string $set
	 * @return $this
	 */
	public function setDatabaseDefault(string $set): self {
		if ($set === '') {
			$set = self::DEFAULT_DATABASE_NAME;
		}
		$this->default = strtolower($set);
		return $this;
	}

	/**
	 * Return the code names of databases
	 *
	 * @return array
	 */
	public function names(): array {
		return array_keys($this->names);
	}

	/**
	 * Database name to URL
	 *
	 * @param string $name
	 * @return string
	 * @throws Exception_NotFound
	 */
	public function nameToURL(string $name): string {
		$name = strtolower($name);
		if (!array_key_exists($name, $this->names)) {
			throw new Exception_NotFound('No database code named {name}', ['name' => $name]);
		}
		return $this->names[$name];
	}

	/**
	 * Register a database name, or get a database url
	 *
	 * @param string $name
	 * @param string $url
	 * @param bool $isDefault When true, changes default database (has side effects)
	 * @return string
	 * @throws Exception_Semantics
	 * @throws Exception_Syntax
	 */
	public function register(string $name, string $url, bool $isDefault = false): string {
		try {
			$url = URL::normalize($url);
		} catch (Exception_Syntax $e) {
			throw new Exception_Syntax('{url} is not a valid database URL ({name})', [
				'name' => $name, 'url' => $url,
			], 0, $e);
		}
		if (array_key_exists($name, $this->databases) && $url !== $this->names[$name]) {
			throw new Exception_Semantics('Register would change database url {name} {url} (old is {old})', [
				'name' => $name, 'url' => $url, 'old' => $this->names[$name],
			]);
		}
		$this->names[$name] = $url;
		if ($isDefault) {
			$this->setDatabaseDefault($name);
		}
		return $name;
	}

	/**
	 *
	 * @param string $name
	 * @return Module_Database
	 */
	public function unregister(string $name): self {
		$name = strtolower($name);
		$this->names[$name] = null;
		return $this;
	}

	/**
	 * Internal function to load database settings from globals
	 * @throws Exception_Syntax
	 * @throws Exception_Configuration
	 * @throws Exception_Semantics
	 */
	public function _configured(): void {
		$application = $this->application;
		$config = $application->configuration;

		$this->setDatabaseDefault($config->getPathString([
			__CLASS__, self::OPTION_DEFAULT,
		], self::DEFAULT_DATABASE_NAME));

		$configPathDatabaseNames = [__CLASS__, self::OPTION_NAMES, ];
		$databases = toArray($config->path($configPathDatabaseNames));
		foreach ($databases as $name => $database) {
			$name = strtolower($name);
			if (!is_string($database)) {
				throw new Exception_Configuration($configPathDatabaseNames, 'Value for {name} is not a string: {databases}', [
					'databases' => JSON::encodePretty($databases), 'name' => $name,
				]);
			}

			try {
				$this->register($name, $database);
			} catch (Exception_Semantics $e) {
				$application->logger->critical($e->raw_message, $e->variables());

				throw $e;
			}
		}
	}

	/**
	 * Return all connected databases in the system
	 *
	 * @return Database[]
	 */
	public function databases(): array {
		return $this->databases;
	}

	/**
	 * Disconnect all databases (on fork)
	 */
	public function disconnectAll(): void {
		$this->application->logger->debug(__METHOD__);
		foreach ($this->databases as $database) {
			$this->application->logger->debug('Disconnecting database: {url}', ['url' => $database->safeURL(), ]);
			$database->disconnect();
		}
		$this->databases = [];
	}

	/**
	 * Reconnect all databases (on fork)
	 * @throws Database_Exception_Connect
	 */
	public function reconnectAll(): void {
		foreach ($this->databases as $database) {
			$this->application->logger->info('Reconnecting database: {url}', ['url' => $database->safeURL(), ]);
			$database->reconnect();
		}
	}

	/**
	 * Register or retrieve a class for a database scheme prefix
	 *
	 * @param string $scheme
	 * @param string $classname
	 * @return $this
	 * @throws Exception_Class_NotFound
	 */
	public function registerScheme(string $scheme, string $classname): self {
		$scheme = strtolower($scheme);
		if (!class_exists($classname, false)) {
			throw new Exception_Class_NotFound($classname);
		}
		if (array_key_exists($scheme, $this->schemeToClass) && $this->schemeToClass[$scheme] !== $classname) {
			$this->application->logger->warning('Registered {scheme} overrides {old_classname} with {classname}', [
				'scheme' => $scheme, 'classname' => $classname, 'old_classname' => $this->schemeToClass[$scheme],
			]);
		}
		$this->schemeToClass[$scheme] = $classname;
		return $this;
	}

	/**
	 * Register or retrieve a class for a database scheme prefix.
	 * Returns empty string if no scheme registered
	 *
	 * @param string $scheme
	 * @return string
	 * @throws Exception_Key
	 */
	public function getRegisteredScheme(string $scheme): string {
		$scheme = strtolower($scheme);
		if (array_key_exists($scheme, $this->schemeToClass)) {
			return $this->schemeToClass[$scheme];
		}

		throw new Exception_Key('No scheme registered');
	}

	/**
	 * Register or retrieve a class for a database scheme prefic
	 *
	 * @return array
	 */
	public function getRegisteredSchemes(): array {
		return array_keys($this->schemeToClass);
	}

	/**
	 * Create a disconnected Database of scheme
	 *
	 * @param string $scheme
	 * @param string $url
	 * @param array $options
	 * @return Database
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 */
	public function schemeFactory(string $scheme, string $url = '', array $options = []): Database {
		$class = $this->getRegisteredScheme($scheme);
		if (!$class) {
			throw new Exception_NotFound('Database scheme {scheme} does not have a registered handler. Available schemes: {schemes}', [
				'scheme' => $scheme, 'schemes' => $this->validSchemes(),
			]);
		}
		$database = $this->application->factory($class, $this->application, $url, $options);
		assert($database instanceof Database);
		return $database;
	}

	/**
	 * Create a new database
	 *
	 * @param Application $application
	 * @param string $mixed Connection URL in the form
	 *            dbtype://user:password@host/databasename?option0=value0&option1=value1. Currently
	 *            MySQL and SQLite3 supported.
	 *            Or "named" database: "default", "stats", etc.
	 *
	 * @param array $options
	 * @return Database
	 * @throws Database_Exception_Connect
	 * @throws Exception_Configuration
	 * @throws Exception_NotFound
	 */
	public function app_database_registry(Application $application, string $mixed = '', array $options = []): Database {
		assert($application === $this->application);
		return $this->databaseRegistry($mixed, $options);
	}

	/**
	 * Create or find a database. Options are passed to the database creation call.
	 *
	 * Other options allowed are:
	 *
	 * - reuse: Boolean value defaults to true which means database connections are reused when connected the first
	 * time. Turn off by setting to "false" and use with caution as each registry call will result in a new database
	 * object.
	 * - connect: Boolean value which defaults to true. Connect the database after creating the first time.
	 *
	 * @param string $mixed
	 * @param array $options
	 * @return Database
	 * @throws Database_Exception_Connect
	 * @throws Exception_NotFound
	 */
	public function databaseRegistry(string $mixed = '', array $options = []): Database {
		if ($mixed !== null && URL::valid($mixed)) {
			try {
				$url = URL::normalize($mixed);
			} catch (Exception_Syntax $e) {
				throw new Exception_NotFound('Invalid URL {url}', $e->variables() + ['url' => $mixed], $e->getCode(), $e);
			}
			$codename = array_flip($this->names)[$url] ?? $url;
		} else {
			if (empty($mixed)) {
				$mixed = $this->databaseDefault();
				if (empty($mixed)) {
					$mixed = 'default';
				}
			}
			if (count($this->names) === 0) {
				throw new Exception_NotFound('No default database URL configured: "{default}" {id} {configuration}', [
					'default' => $this->databaseDefault(), 'id' => spl_object_id($this),
					'configuration' => self::class . '::' . self::OPTION_NAMES,
				]);
			}
			$url = $this->nameToURL($mixed);
			$codename = $mixed;
		}

		if (toBool($options['reuse'] ?? true)) {
			if (array_key_exists($codename, $this->databases)) {
				return $this->databases[$codename];
			}
		}
		if (array_key_exists($codename, $this->databases)) {
			$codename .= '#' . count($this->databases);
		}

		try {
			$scheme = URL::scheme($url);
		} catch (Exception_Syntax) {
			/* Never happens as URL::valid */
			$scheme = '';
		}

		try {
			/* Remove local options from options */
			$objectOptions = ArrayTools::filterKeys($options, null, [
				'reuse', 'connect',
			]);
			$database = $this->schemeFactory($scheme, $url, $objectOptions);
		} catch (Exception_Class_NotFound|Exception_Key $e) {
			throw new Exception_NotFound(__CLASS__ . '::schemeFactory unable to create scheme {scheme} {exceptionClass} {message}', [
				'scheme' => $scheme,
			] + $e->variables());
		}

		$database->setCodeName($codename);
		$this->databases[$codename] = $database;
		if (toBool($options['connect'] ?? true)) {
			if (!$database->connect()) {
				$__ = ['safeURL' => $database->safeURL()];
				$this->application->logger->warning('Failed to connect to database: {safeURL}', $__);

				throw new Database_Exception_Connect($url, 'Connection failed to {safeURL}', $__);
			}
		}
		return $database;
	}

	/**
	 *
	 * @param array $info
	 * @return array
	 */
	public function hook_info(array $info): array {
		$default = $this->option('default');
		if (empty($default)) {
			$default = '';
		}
		if ($default) {
			try {
				$url = $this->nameToURL($default);

				try {
					$safe_url = URL::removePassword($url);
				} catch (Exception_Syntax) {
					$safe_url = '-url-parse-failed-syntax-';
				}
				$info['default'] = [
					'value' => $default, 'title' => 'Default database',
				];
				$info['default_url'] = [
					'value' => $safe_url, 'title' => 'Default URL (Safe)',
				];
			} catch (Exception_NotFound) {
				$info['default'] = [
					'value' => $default, 'error' => 'No database', 'valid_values' => array_keys($this->names),
				];
			}
		}
		$dbs = [];
		foreach ($this->databases as $k => $item) {
			$dbs[$k] = $item->safeURL();
		}
		$info['databases'] = ['value' => $dbs, 'title' => 'Database names', ];

		return ArrayTools::prefixKeys($info, __CLASS__ . '::');
	}

	/**
	 *
	 * @return array
	 */
	public function validSchemes(): array {
		return array_keys($this->schemeToClass);
	}
}
