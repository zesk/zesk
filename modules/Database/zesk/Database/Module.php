<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Database;

use zesk\Application;
use zesk\ArrayTools;
use zesk\CaseArray;
use zesk\Database\Exception\Connect;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\Semantics;
use zesk\Exception\SyntaxException;
use zesk\JSON;
use zesk\Module as BaseModule;
use zesk\URL;
use zesk\Types as BaseTypes;

/**
 * @see Database
 * @author kent
 *
 */
class Module extends BaseModule implements ConfiguredInterface {
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
	 * Used as a boolean option in ->databaseRegistry
	 * Defaults to true, must pass explicit false.
	 *
	 * @see self::databaseRegistry()
	 */
	public const REGISTRY_REUSE = 'reuse';

	/**
	 * Used as a boolean option in ->databaseRegistry - connect to the database if true.
	 * Defaults to true, so must pass explicit false.
	 *
	 * @see self::databaseRegistry()
	 */
	public const REGISTRY_CONNECT = 'connect';

	/**
	 *
	 * @var string
	 */
	private string $default = '';

	/**
	 * Global database name => url mapping
	 *
	 * @var CaseArray
	 */
	private CaseArray $names;

	/**
	 * Global databases
	 *
	 * @var Base[]
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
	 * @throws Semantics
	 * @see Module::initialize
	 */
	public function initialize(): void {
		$this->names = new CaseArray();
		$application = $this->application;
		$application->registerRegistry('database', $this->app_databaseRegistry(...));
		$application->registerFactory('database', $this->app_databaseRegistry(...));
		$application->hooks->add('exit', $this->disconnectAll(...), ['last' => true]);
		$application->hooks->add('pcntl_fork-child', $this->reconnectAll(...));
	}

	/**
	 * A better hook.
	 *
	 * @return void
	 * @throws ConfigurationException
	 * @throws Semantics
	 * @throws SyntaxException
	 */
	public function hook_database_configure(): void {
		$this->_configured();
	}

	/**
	 * @param bool $set
	 * @return $this
	 */
	public function setDebug(bool $set): self {
		$this->application->configuration->setPath([Base::class, Base::OPTION_DEBUG], $set);
		return $this;
	}

	/**
	 * @return bool
	 */
	public function debug(): bool {
		return BaseTypes::toBool($this->application->configuration->getPath([Base::class, Base::OPTION_DEBUG]));
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
		return $this->names->keys();
	}

	/**
	 * Database name to URL
	 *
	 * @param string $name
	 * @return string
	 * @throws NotFoundException
	 */
	public function nameToURL(string $name): string {
		$name = strtolower($name);
		if (!isset($this->names[$name])) {
			throw new NotFoundException('No database code named {name}', ['name' => $name]);
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
	 * @throws Semantics
	 * @throws SyntaxException
	 */
	public function register(string $name, string $url, bool $isDefault = false): string {
		try {
			$url = URL::normalize($url);
		} catch (SyntaxException $e) {
			throw new SyntaxException('{url} is not a valid database URL ({name})', [
				'name' => $name, 'url' => $url,
			], 0, $e);
		}
		if (array_key_exists($name, $this->databases) && $url !== $this->names[$name]) {
			throw new Semantics('Register would change database url {name} {url} (old is {old})', [
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
	 * @return self
	 */
	public function unregister(string $name): self {
		$this->names[$name] = null;
		return $this;
	}

	/**
	 * Internal function to load database settings from globals
	 * @throws SyntaxException
	 * @throws ConfigurationException
	 * @throws Semantics
	 */
	public function _configured(): void {
		$application = $this->application;

		if ($this->hasOption(self::OPTION_DEFAULT)) {
			$this->setDatabaseDefault($this->optionString(self::OPTION_DEFAULT));
		}
		if ($this->hasOption(self::OPTION_NAMES)) {
			$names = $this->optionArray(self::OPTION_NAMES);

			foreach ($names as $name => $database) {
				$name = strtolower($name);
				if (!is_string($database)) {
					throw new ConfigurationException([
						__CLASS__, self::OPTION_NAMES,
					], 'Value for {name} is not a string: {names}', [
						'names' => JSON::encodePretty($names), 'name' => $name,
					]);
				}

				try {
					$this->register($name, $database);
				} catch (Semantics $e) {
					$application->logger->error($e->getRawMessage(), $e->variables());

					throw $e;
				}
			}
		}
	}

	/**
	 * Return all connected databases in the system
	 *
	 * @return Base[]
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
	 * @throws Connect
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
	 * @throws ClassNotFound
	 */
	public function registerScheme(string $scheme, string $classname): self {
		$scheme = strtolower($scheme);
		if (!class_exists($classname, false)) {
			throw new ClassNotFound($classname);
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
	 * @throws KeyNotFound
	 */
	public function getRegisteredScheme(string $scheme): string {
		$scheme = strtolower($scheme);
		if (array_key_exists($scheme, $this->schemeToClass)) {
			return $this->schemeToClass[$scheme];
		}

		throw new KeyNotFound('No scheme registered');
	}

	/**
	 * Register or retrieve a class for a database scheme prefix
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
	 * @return Base
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 * @throws NotFoundException
	 */
	public function schemeFactory(string $scheme, string $url = '', array $options = []): Base {
		$class = $this->getRegisteredScheme($scheme);
		if (!$class) {
			throw new NotFoundException('Database scheme {scheme} does not have a registered handler. Available schemes: {schemes}', [
				'scheme' => $scheme, 'schemes' => $this->validSchemes(),
			]);
		}
		$database = $this->application->factory($class, $this->application, $url, $options);
		assert($database instanceof Base);
		return $database;
	}

	/**
	 * Create a new database
	 *
	 * @param Application $application
	 * @param string $mixed Connection URL in the form
	 *            scheme://user:password@host/databaseName?option0=value0&option1=value1. Currently
	 *            MySQL and SQLite3 supported.
	 *            Or "named" database: "default", "stats", etc.
	 *
	 * @param array $options
	 * @return Base
	 * @throws Connect
	 * @throws NotFoundException
	 */
	public function app_databaseRegistry(Application $application, string $mixed = '', array $options = []): Base {
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
	 * @return Base
	 * @throws Connect
	 * @throws NotFoundException
	 */
	public function databaseRegistry(string $mixed = '', array $options = []): Base {
		if (empty($mixed)) {
			$mixed = $this->databaseDefault();
			if (empty($mixed)) {
				$mixed = self::DEFAULT_DATABASE_NAME;
			}
		}
		if (count($this->names) === 0) {
			throw new NotFoundException('No default database URL configured: "{default}" {id} {configuration}', [
				'default' => $this->databaseDefault(), 'id' => spl_object_id($this),
				'configuration' => self::class . '::' . self::OPTION_NAMES,
			]);
		}
		$url = $this->nameToURL($mixed);
		$codename = $mixed;

		if (BaseTypes::toBool($options[self::REGISTRY_REUSE] ?? true)) {
			if (array_key_exists($codename, $this->databases)) {
				return $this->databases[$codename];
			}
		}
		if (array_key_exists($codename, $this->databases)) {
			$codename .= '#' . count($this->databases);
		}

		try {
			$scheme = URL::scheme($url);
		} catch (SyntaxException) {
			/* Never happens as URL::valid */
			$scheme = '';
		}

		try {
			/* Remove local options from options */
			$objectOptions = ArrayTools::filterKeys($options, null, [
				self::REGISTRY_REUSE, self::REGISTRY_CONNECT,
			]);
			$database = $this->schemeFactory($scheme, $url, $objectOptions);
		} catch (ClassNotFound|KeyNotFound $e) {
			throw new NotFoundException(__CLASS__ . '::schemeFactory unable to create scheme {scheme} {exceptionClass} {message}', [
				'scheme' => $scheme,
			] + $e->variables());
		}

		$database->setCodeName($codename);
		$this->databases[$codename] = $database;
		if (BaseTypes::toBool($options[self::REGISTRY_CONNECT] ?? true)) {
			if (!$database->connect()) {
				$__ = ['safeURL' => $database->safeURL()];
				$this->application->logger->warning('Failed to connect to database: {safeURL}', $__);

				throw new Connect($url, 'Connection failed to {safeURL}', $__);
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
				} catch (SyntaxException) {
					$safe_url = '-url-parse-failed-syntax-';
				}
				$info['default'] = [
					'value' => $default, 'title' => 'Default database',
				];
				$info['default_url'] = [
					'value' => $safe_url, 'title' => 'Default URL (Safe)',
				];
			} catch (NotFoundException) {
				$info['default'] = [
					'value' => $default, 'error' => 'No database', 'valid_values' => $this->names->keys(),
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
