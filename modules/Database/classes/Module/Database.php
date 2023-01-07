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
class Module_Database extends Module {
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
	private array $scheme_to_class = [];

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize(): void {
		$application = $this->application;
		$application->registerRegistry('database', $this->app_database_registry(...));
		$application->registerFactory('database', $this->app_database_registry(...));
		$application->hooks->add(Hooks::HOOK_DATABASE_CONFIGURE, $this->_configured(...), ['first' => true]);
		$application->hooks->add('exit', $this->disconnectAll(...), ['last' => true]);
		$application->hooks->add('pcntl_fork-child', $this->reconnectAll(...));
	}

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
	 * @return string
	 */
	public function databaseDefault(): string {
		return $this->default;
	}

	/**
	 * Set database default code name to use
	 *
	 * @param string $set
	 * @return $this
	 */
	public function setDatabaseDefault(string $set): self {
		$this->default = strtolower($set);
		return $this;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	public function nameToURL(string $name): string {
		return $this->names[$name];
	}

	/**
	 * @return array
	 */
	public function names(): array {
		return array_keys($this->names);
	}

	/**
	 * @param string $name
	 * @return string
	 * @throws Exception_NotFound
	 */
	public function codeToURL(string $name): string {
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
	 * @param bool $is_default
	 * @return string
	 * @throws Exception_Syntax
	 */
	public function register(string $name, string $url, bool $is_default = false): string {
		try {
			$url = URL::normalize($url);
		} catch (Exception_Syntax $e) {
			throw new Exception_Syntax('{url} is not a valid database URL ({name})', [
				'name' => $name, 'url' => $url,
			], 0, $e);
		}
		if (array_key_exists($name, $this->databases) && $url !== $this->names[$name]) {
			$this->application->logger->debug('Changing database url {name} {url} (old is {old})', [
				'name' => $name, 'url' => $url, 'old' => $this->names[$name],
			]);
			$this->databases[$name]->changeURL($url);
		}
		$this->names[$name] = $url;
		if ($is_default) {
			$this->setDatabaseDefault($name);
		}
		return $name;
	}

	/**
	 *
	 * @param string $name
	 */
	public function unregister(string $name): self {
		$name = strtolower($name);
		$this->names[$name] = null;
		return $this;
	}

	/**
	 * @deprecated 2017-10
	 */
	private function _legacy_configured(): void {
		$application = $this->application;
		$config = $application->configuration;
		// 2017-10
		if ($config->has('table_prefix')) {
			zesk()->deprecated('Using table_prefix - no longer supported n 2017');
		}
		if ($config->has('db_url')) {
			zesk()->deprecated('Using DB_URL - no longer supported after 2016');
			$old_style = ArrayTools::keysRemovePrefix($application->configuration->toArray(), 'db_url', true);
			foreach ($old_style as $name => $url) {
				$name = empty($name) ? 'default' : StringTools::removePrefix($name, '_');

				try {
					$this->register($name, $url);
				} catch (Exception_Semantics $e) {
					$application->logger->critical($e->raw_message, $e->variables());
				}
			}
		}
		$config->deprecated('Database::database_names', __CLASS__ . '::names');
		$config->deprecated('Database::default', __CLASS__ . '::default');
		// 2018-01
		$config->deprecated(Database::class . '::database_names', __CLASS__ . '::names');
		$config->deprecated(Database::class . '::default', __CLASS__ . '::default');
		$config->deprecated(Database::class . '::names', __CLASS__ . '::names');
	}

	/**
	 * Internal function to load database settings from globals
	 */
	public function _configured(): void {
		$application = $this->application;
		$config = $application->configuration;

		$defaultDatabaseName = 'default';
		$database_default_config_path = [__CLASS__, 'default', ];
		if ($config->pathExists($database_default_config_path)) {
			$defaultDatabaseName = $config->getPath($database_default_config_path);
			$this->setDatabaseDefault($defaultDatabaseName);
		}

		$configPathDatabaseNames = [__CLASS__, 'names', ];
		$databases = toArray($config->path($configPathDatabaseNames));
		foreach ($databases as $name => $database) {
			$name = strtolower($name);
			if (!is_string($database)) {
				throw new Exception_Configuration($configPathDatabaseNames, 'Value for {name} is not a string: {databases}', [
					'databases' => JSON::encodePretty($databases), 'name' => $name,
				]);
			}

			try {
				$this->register($name, $database, $name === $defaultDatabaseName);
			} catch (Exception_Semantics $e) {
				$application->logger->critical($e->raw_message, $e->variables());
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
	 * Reconned databases on fork
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
	 * Reconned databases on fork
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
		if (array_key_exists($scheme, $this->scheme_to_class) && $this->scheme_to_class[$scheme] !== $classname) {
			$this->application->logger->warning('Registered {scheme} overrides {old_classname} with {classname}', [
				'scheme' => $scheme, 'classname' => $classname, 'old_classname' => $this->scheme_to_class[$scheme],
			]);
		}
		$this->scheme_to_class[$scheme] = $classname;
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
		if (array_key_exists($scheme, $this->scheme_to_class)) {
			return $this->scheme_to_class[$scheme];
		}

		throw new Exception_Key('No scheme registered');
	}

	/**
	 * Register or retrieve a class for a database scheme prefic
	 *
	 * @return array
	 */
	public function getRegisteredSchemes(): array {
		return array_keys($this->scheme_to_class);
	}

	/**
	 * Create a disconnected Database of scheme
	 *
	 * @param string $scheme
	 * @param array $options
	 * @return Database
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 */
	/**
	 * @param string $scheme
	 * @param array $options
	 * @return Database
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 */
	public function schemeFactory(string $scheme, array $options = []): Database {
		$class = $this->getRegisteredScheme($scheme);
		if (!$class) {
			throw new Exception_NotFound('Database scheme {scheme} does not have a registered handler. Available schemes: {schemes}', [
				'scheme' => $scheme, 'schemes' => $this->validSchemes(),
			]);
		}
		return $this->application->factory($class, $this->application, null, $options);
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
		return $this->databaseRegistry($mixed, $options);
	}

	/**
	 *
	 * Create or find a database
	 *
	 * @param string|null $mixed
	 * @param array $options
	 * @return Database
	 * @throws Database_Exception_Connect
	 * @throws Exception_Configuration
	 * @throws Exception_NotFound
	 */
	public function databaseRegistry(string $mixed = '', array $options = []): Database {
		$application = $this->application;
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
				throw new Exception_Configuration(__CLASS__ . '::names', 'No default database URL configured: "{default}" {id}', [
					'default' => $this->databaseDefault(), 'id' => spl_object_id($this),
				]);
			}
			$url = $this->codeToURL($mixed);
			$codename = $mixed;
		}

		try {
			$safe_url = URL::removePassword($url);
			$scheme = URL::scheme($url);
		} catch (Exception_Syntax) {
			/* Never happens as URL::valid passed above or it is a codeToURL which is also valid */
			$safe_url = '';
			$scheme = '';
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
			$class = $this->getRegisteredScheme($scheme);
		} catch (Exception_Key) {
			throw new Exception_NotFound('No database class for scheme \"{scheme}\"', [
				'scheme' => $scheme,
			]);
		}

		try {
			$db = $application->objects->factory($class, $application, $url, $options);
		} catch (Exception_Class_NotFound) {
			throw new Exception_NotFound('Unknown class {class} returned for scheme {scheme}', [
				'class' => $class, 'scheme' => $scheme,
			]);
		}
		$db->setCodeName($codename);
		$db->setOption('internal_name', $codename);
		$this->databases[$codename] = $db;
		if (toBool($options['connect'] ?? true)) {
			if (!$db->connect()) {
				$this->application->logger->warning("Failed to connect to database: $safe_url");

				throw new Database_Exception_Connect($url, 'Connection failed to {safeURL}', ['safeURL' => $safe_url]);
			}
			if ($db->optionBool('debug')) {
				$this->application->logger->debug("Connected to database: $safe_url");
			}
		}
		return $db;
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
				$url = $this->codeToURL($default);

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
		};
		$info['databases'] = ['value' => $dbs, 'title' => 'Database names', ];

		return ArrayTools::prefixKeys($info, __CLASS__ . '::');
	}

	/**
	 *
	 * @return array
	 */
	public function validSchemes(): array {
		return array_keys($this->scheme_to_class);
	}
}
