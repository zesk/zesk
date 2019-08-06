<?php
/**
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright &copy; 2018 Market Acumen, Inc.
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
	private $default = null;

	/**
	 * Global database name => url mapping
	 *
	 * @var array
	 */
	private $names = array();

	/**
	 * Global databases
	 *
	 * @var Database[string]
	 */
	private $databases = array();

	/**
	 * Mapping of scheme to class which handles it
	 *
	 * @var string[string]
	 */
	private $scheme_to_class = array();

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize() {
		$this->application->register_registry("database", array(
			$this,
			"app_database_registry",
		));
		$this->application->register_factory("database", array(
			$this,
			"app_database_registry",
		));
		$hooks = $this->application->hooks;
		$hooks->add(Hooks::HOOK_DATABASE_CONFIGURE, array(
			$this,
			"_configured",
		), "first");
		$hooks->add('exit', array(
			$this,
			"disconnect_all",
		), "last");
		$hooks->add('pcntl_fork-child', array(
			$this,
			"reconnect_all",
		));
	}

	/**
	 * Set or get the default internal database name
	 *
	 * @param string $set
	 * @return string
	 */
	public function database_default($set = null) {
		if ($set === null) {
			return $this->default;
		}
		$set = strtolower($set);
		$this->default = $set;
	}

	/**
	 * Register a database name, or get a database url
	 *
	 * @param unknown $name
	 * @param unknown $url
	 * @param string $is_default
	 */
	public function register($name = null, $url = null, $is_default = false) {
		if ($name === null) {
			return $this->names;
		}
		$name = strtolower($name);
		if ($url === null) {
			return avalue($this->names, $name);
		}
		if (!URL::valid($url)) {
			throw new Exception_Semantics("{url} is not a valid database URL ({name})", compact("name", "url"));
		}
		$url = URL::normalize($url);
		if (array_key_exists($name, $this->databases) && $url !== $this->names[$name]) {
			$this->application->logger->debug("Changing database url {name} {url} (old is {old})", array(
				"name" => $name,
				"url" => $url,
				"old" => $this->names[$name],
			));
			$this->databases[$name]->change_url($url);
		} else {
			//$zesk->logger->debug("Registering database $name $url");
		}
		$this->names[$name] = $url;
		if ($is_default) {
			$this->database_default($name);
		}
		return $name;
	}

	/**
	 *
	 * @param string $name
	 */
	public function unregister($name) {
		$name = strtolower($name);
		$this->names[$name] = null;
		return $this;
	}

	/**
	 * @deprecated 2017-10
	 * @param Application $application
	 */
	private function _legacy_configured() {
		$application = $this->application;
		$config = $application->configuration;
		// 2017-10
		if ($config->has("table_prefix")) {
			zesk()->deprecated("Using table_prefix - no longer supported n 2017");
		}
		if ($config->has("db_url")) {
			zesk()->deprecated("Using DB_URL - no longer supported after 2016");
			$old_style = ArrayTools::kunprefix($application->configuration->to_array(), "db_url", true);
			foreach ($old_style as $name => $url) {
				$name = empty($name) ? "default" : StringTools::unprefix($name, '_');

				try {
					$this->register($name, $url);
				} catch (Exception_Semantics $e) {
					$application->logger->critical($e->raw_message, $e->variables());
				}
			}
		}
		$config->deprecated("Database::database_names", __CLASS__ . "::names");
		$config->deprecated("Database::default", __CLASS__ . '::default');
		// 2018-01
		$config->deprecated(Database::class . "::database_names", __CLASS__ . "::names");
		$config->deprecated(Database::class . "::default", __CLASS__ . '::default');
		$config->deprecated(Database::class . "::names", __CLASS__ . '::names');
	}

	/**
	 * Internal function to load database settings from globals
	 */
	public function _configured() {
		$this->_legacy_configured();

		$application = $this->application;
		$config = $application->configuration;

		$databases = to_array($config->path(array(
			__CLASS__,
			'names',
		)));
		foreach ($databases as $name => $database) {
			$name = strtolower($name);

			try {
				$this->register($name, $database);
			} catch (Exception_Semantics $e) {
				$application->logger->critical($e->raw_message, $e->variables());
			}
		}
		$database_default_config_path = array(
			__CLASS__,
			"default",
		);
		if ($config->path_exists($database_default_config_path)) {
			$this->database_default($config->path_get($database_default_config_path));
		}
	}

	/**
	 * Return all connected databases in the system
	 *
	 * @return Database[]
	 */
	public function databases() {
		return $this->databases;
	}

	/**
	 * Reconned databases on fork
	 */
	public function disconnect_all() {
		foreach ($this->databases as $url => $database) {
			/* @var $database Database */
			$database->disconnect();
			unset($this->databases[$url]);
		}
	}

	/**
	 * Reconned databases on fork
	 */
	public function reconnect_all() {
		/* @var $database Database */
		foreach ($this->databases as $url => $database) {
			$this->application->logger->info("Reconnecting database: {url}", array(
				"url" => $database->safe_url(),
			));
			$database->reconnect();
		}
	}

	/**
	 *
	 * @return array
	 */
	public function valid_schemes() {
		return array_keys($this->scheme_to_class);
	}

	/**
	 * Register or retrieve a class for a database scheme prefic
	 *
	 * @param string $scheme
	 * @param string $classname
	 * @return string
	 */
	public function register_scheme($scheme, $classname = null) {
		$scheme = strtolower($scheme);
		$exists = array_key_exists($scheme, $this->scheme_to_class);
		if ($classname === null) {
			return $exists ? $this->scheme_to_class[$scheme] : null;
		}
		if ($exists) {
			$this->application->logger->warning("Registered {scheme} again for class {classname}", compact("scheme", "classname"));
		}
		$this->scheme_to_class[$scheme] = $classname;
		return $classname;
	}

	/**
	 * Create a disconnected Database of scheme
	 *
	 * @param string $scheme
	 * @param array $options
	 * @throws Exception_NotFound
	 * @return Database
	 */
	public function scheme_factory($scheme, array $options = array()) {
		$class = $this->register_scheme($scheme);
		if (!$class) {
			throw new Exception_NotFound("Database scheme {scheme} does not have a registered handler. Available schemes: {schemes}", array(
				"scheme" => $scheme,
				"schemes" => $this->valid_schemes(),
			));
		}
		return $this->application->factory($class, $this->application, null, $options);
	}

	/**
	 * Create a new database
	 *
	 * @param string $url
	 *        	Connection URL in the form
	 *        	dbtype://user:password@host/databasename?option0=value0&option1=value1. Currently
	 *        	MySQL and SQLite3 supported.
	 * @return Database
	 */
	public function app_database_registry(Application $application, $mixed = null, $options = array()) {
		return $this->database_registry($mixed, $options);
	}

	/**
	 *
	 * Create or find a database
	 *
	 * @param string $url
	 *        	Connection URL in the form
	 *        	dbtype://user:password@host/databasename?option0=value0&option1=value1. Currently
	 *        	MySQL and SQLite3 supported.
	 * @throws Exception_Configuration
	 * @throws Exception_NotFound
	 * @throws Database_Exception_Unknown_Schema
	 * @throws Exception
	 * @throws Exception_Unimplemented
	 * @return Database
	 */
	public function database_registry($mixed = null, $options = array()) {
		$options = to_array($options);
		$application = $this->application;
		$original = $mixed;
		if (URL::valid($mixed)) {
			$url = URL::normalize($mixed);
			$codename = avalue(array_flip($this->register()), $url, $url);
		} else {
			if (empty($mixed)) {
				$mixed = $this->database_default();
				if (empty($mixed)) {
					$mixed = "default";
				}
			}
			$url = $this->register($mixed);
			$codename = $mixed;
			if (count($this->names) === 0) {
				throw new Exception_Configuration(__CLASS__ . "::names", "No default database URL configured: \"{default}\"", array(
					"default" => $this->database_default(),
				));
			}
			if (!$url) {
				throw new Exception_NotFound("Database not found: \"{name}\" from databases: {databases}", array(
					"name" => $original,
					"databases" => JSON::encode(array_keys($this->register())),
				));
			}
		}
		$safe_url = URL::remove_password($url);
		if (to_bool(avalue($options, 'reuse', true))) {
			$db = avalue($this->databases, $codename);
			if ($db) {
				return $db;
			}
		} else {
			if (array_key_exists($codename, $this->databases)) {
				$codename .= "#" . count($this->databases);
			}
		}
		$scheme = URL::scheme($url);
		$class = $this->register_scheme($scheme);
		if (!$class) {
			throw new Database_Exception_Unknown_Schema("Database::factory({url}) {scheme} not registered. Valid schemes: {schemes}", array(
				"url" => $safe_url,
				"scheme" => $scheme,
				"schemes" => $this->valid_schemes(),
			));
		}

		try {
			$db = $application->objects->factory($class, $application, $url, $options);
		} catch (Exception $e) {
			$application->hooks->call("exception", $e);

			throw $e;
		}
		if (!$db instanceof Database) {
			throw new Exception_Unimplemented("Database::factory({url}) {scheme} did not return a Database", array(
				"url" => $safe_url,
				"scheme" => $scheme,
			));
		}
		$db->code_name($codename);
		$db->set_option("internal_name", $codename);
		$this->databases[$codename] = $db;
		if (avalue($options, 'connect', true)) {
			if (!$db->connect()) {
				$this->application->logger->warning("Failed to connect to database: $safe_url");
				return null;
			}
			if ($db->option_bool("debug")) {
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
		$default = $this->option("default");
		if (empty($default)) {
			$default = "";
		}
		$url = $this->register($default);
		if ($default) {
			$info[__CLASS__ . "::default"] = array(
				"value" => $default,
				"title" => "Default database",
			);
		}
		if ($url) {
			$info[__CLASS__ . "::default_url"] = array(
				"value" => URL::remove_password($url),
				"title" => "Default URL (Safe)",
			);
		}
		$dbs = array();
		foreach ($this->databases as $k => $item) {
			$dbs[$k] = $item->safe_url();
		}
		;
		$info[__CLASS__ . '::databases'] = array(
			"value" => $dbs,
			"title" => "Database names",
		);

		return $info;
	}
}
