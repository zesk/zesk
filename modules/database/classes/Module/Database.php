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
		$application->registerRegistry('database', [$this, 'app_database_registry', ]);
		$application->registerFactory('database', [$this, 'app_database_registry', ]);
		$application->hooks->add(Hooks::HOOK_DATABASE_CONFIGURE, [$this, '_configured', ], ['first' => true]);
		$application->hooks->add('exit', [$this, 'disconnectAll', ], ['last' => true]);
		$application->hooks->add('pcntl_fork-child', [$this, 'reconnectAll', ]);
	}

	/**
	 * Set or get the default internal database name
	 *
	 * @param string $set
	 * @return string
	 * @deprecated 2022-04
	 */
	public function database_default($set = null): string {
		if ($set !== null) {
			$this->application->deprecated('Setter/getter deprecated ' . __METHOD__);
			$this->setDatabaseDefault($set);
		}
		return $this->databaseDefault();
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

	public function nameToURL(string $name) {
		return $this->names[$name];
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
	 * @throws Exception_Semantics
	 */
	public function register(string $name, string $url, bool $is_default = false): string {
		if (!URL::valid($url)) {
			throw new Exception_Semantics('{url} is not a valid database URL ({name})', compact('name', 'url'));
		}
		$url = URL::normalize($url);
		if (array_key_exists($name, $this->databases) && $url !== $this->names[$name]) {
			$this->application->logger->debug('Changing database url {name} {url} (old is {old})', [
				'name' => $name,
				'url' => $url,
				'old' => $this->names[$name],
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
				$name = empty($name) ? 'default' : StringTools::unprefix($name, '_');

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
		$id = spl_object_id($this);
		$this->_legacy_configured();

		$application = $this->application;
		$config = $application->configuration;

		$databases = toArray($config->path([__CLASS__, 'names', ]));
		foreach ($databases as $name => $database) {
			$name = strtolower($name);

			try {
				$this->register($name, $database);
			} catch (Exception_Semantics $e) {
				$application->logger->critical($e->raw_message, $e->variables());
			}
		}
		$database_default_config_path = [__CLASS__, 'default', ];
		if ($config->pathExists($database_default_config_path)) {
			$this->setDatabaseDefault($config->path_get($database_default_config_path));
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
				'scheme' => $scheme,
				'classname' => $classname,
				'old_classname' => $this->scheme_to_class[$scheme],
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
	public function schemeFactory(string $scheme, array $options = []): Database {
		$class = $this->getRegisteredScheme($scheme);
		if (!$class) {
			throw new Exception_NotFound('Database scheme {scheme} does not have a registered handler. Available schemes: {schemes}', [
				'scheme' => $scheme,
				'schemes' => $this->validSchemes(),
			]);
		}
		return $this->application->factory($class, $this->application, null, $options);
	}

	/**
	 * Create a new database
	 *
	 * @param string $url
	 *            Connection URL in the form
	 *            dbtype://user:password@host/databasename?option0=value0&option1=value1. Currently
	 *            MySQL and SQLite3 supported.
	 * @return Database
	 */
	public function app_database_registry(Application $application, $mixed = null, $options = []) {
		return $this->database_registry($mixed, $options);
	}

	/**
	 *
	 * Create or find a database
	 *
	 * @param string $url
	 *            Connection URL in the form
	 *            dbtype://user:password@host/databasename?option0=value0&option1=value1. Currently
	 *            MySQL and SQLite3 supported.
	 * @return Database
	 * @throws Exception_NotFound
	 * @throws Database_Exception_Unknown_Schema
	 * @throws Exception
	 * @throws Exception_Unimplemented
	 * @throws Exception_Configuration
	 */
	public function database_registry(string $mixed = null, array $options = []): Database {
		$options = toArray($options);
		$application = $this->application;
		$original = $mixed;
		if ($mixed !== null && URL::valid($mixed)) {
			$url = URL::normalize($mixed);
			$codename = array_flip($this->names)[$url] ?? $url;
		} else {
			if (empty($mixed)) {
				$mixed = $this->databaseDefault();
				if (empty($mixed)) {
					$mixed = 'default';
				}
			}
			$url = $this->codeToURL($mixed);
			$codename = $mixed;
			if (count($this->names) === 0) {
				throw new Exception_Configuration(__CLASS__ . '::names', 'No default database URL configured: "{default}" {id}', [
					'default' => $this->databaseDefault(),
					'id' => spl_object_id($this),
				]);
			}
			if (!$url) {
				throw new Exception_NotFound('Database not found: "{name}" from databases: {databases}', [
					'name' => $original,
					'databases' => array_keys($this->names),
				]);
			}
		}
		$safe_url = URL::removePassword($url);
		if (toBool($options['reuse'] ?? true)) {
			if (array_key_exists($codename, $this->databases)) {
				return $this->databases[$codename];
			}
		}
		if (array_key_exists($codename, $this->databases)) {
			$codename .= '#' . count($this->databases);
		}
		$scheme = URL::scheme($url);
		$class = $this->getRegisteredScheme($scheme);

		try {
			$db = $application->objects->factory($class, $application, $url, $options);
		} catch (Exception $e) {
			$application->hooks->call('exception', $e);

			throw $e;
		}
		if (!$db instanceof Database) {
			throw new Exception_Unimplemented('Database::factory({url}) {scheme} did not return a Database', [
				'url' => $safe_url,
				'scheme' => $scheme,
			]);
		}
		$db->setCodeName($codename);
		$db->setOption('internal_name', $codename);
		$this->databases[$codename] = $db;
		if (toBool($options['connect'] ?? true)) {
			if (!$db->connect()) {
				$this->application->logger->warning("Failed to connect to database: $safe_url");

				throw new Database_Exception_Connect('Connection failed to {safeURL}', ['safeURL' => $safe_url]);
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
	 * @return string
	 */
	public function hook_info(array $info) {
		$default = $this->option('default');
		if (empty($default)) {
			$default = '';
		}
		if ($default) {
			try {
				$url = $this->codeToURL($default);
				$info['default'] = [
					'value' => $default,
					'title' => 'Default database',
				];
				$info['default_url'] = [
					'value' => URL::removePassword($url),
					'title' => 'Default URL (Safe)',
				];
			} catch (Exception_NotFound) {
				$info['default'] = [
					'value' => $default,
					'error' => 'No database',
					'valid_values' => array_keys($this->names),
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
