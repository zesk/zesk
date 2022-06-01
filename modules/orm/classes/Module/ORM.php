<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

/**
 * @see Class_ORM
 * @see ORM
 * @author kent
 */
class Module_ORM extends Module {
	/**
	 * Your basic ORM classes.
	 *
	 * @var array
	 */
	public $orm_classes = [Server::class, Settings::class, Meta::class, Domain::class, Lock::class, ];

	/**
	 *
	 * @var ORM[]
	 */
	private $class_cache = [];

	/**
	 *
	 * @var ORM_Database_Adapter[string]
	 */
	private $database_adapters = [];

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize(): void {
		parent::initialize();
		/**
		 * @deprecated 2018-01-02
		 */
		$this->application->configuration->deprecated(ORM::class . '::fix_member_objects', ORM::class . '::fix_orm_members');

		/**
		 * $application->orm_factory(...)
		 */
		$this->application->registerFactory('orm', [$this, 'orm_factory', ]);
		/**
		 * $application->orm_registry(...)
		 */
		$this->application->registerRegistry('orm', [$this, 'orm_registry', ]);
		/**
		 * $application->class_orm_registry(...)
		 */
		$this->application->registerRegistry('class_orm', [$this, 'class_orm_registry', ]);

		/**
		 * $application->settings_registry(...)
		 */
		$this->application->registerRegistry('settings', [$this, 'settings_registry', ]);

		/**
		 * Hook into database table
		 */
		$this->application->hooks->add(Database_Table::class . '::column_add', [$this, 'database_table_add_column', ]);

		$this->application->hooks->add('zesk\\Command_Daemon::daemon_hooks', [$this, 'daemon_hooks', ]);

		$self = $this;
		$this->application->hooks->add(ORM::class . '::router_derived_classes', function (ORM $object, array $classes) use ($self) {
			$class_object = $object->class_orm();
			if (!is_array($class_object->has_one) || !$class_object->id_column) {
				return $classes;
			}
			foreach ($class_object->has_one as $member => $class) {
				try {
					$member_object = $object->__get($member);
					if ($member_object !== null && !$member_object instanceof ORM) {
						$this->application->logger->error('Member {member} of object {class} should be an object of {expected_class}, returned {type} with value {value}', [
							'member' => $member,
							'class' => get_class($object),
							'expected_class' => $class,
							'type' => type($member_object),
							'value' => strval($member_object),
						]);

						continue;
					}
				} catch (Exception_ORM_NotFound $e) {
					$member_object = null;
				}
				if ($member_object) {
					$id = $member_object->id();
					foreach ($this->application->classes->hierarchy($member_object, 'zesk\\ORM') as $orm_class) {
						$classes[$orm_class] = $id;
					}
				}
			}
			return $classes;
		});
		/**
		 * Support MySQL database adapter
		 */
		$this->database_adapters['mysql'] = $mysql = $this->application->factory(ORM_Database_Adapter_MySQL::class);
		$this->database_adapters['mysqli'] = $mysql;
	}

	/**
	 * Collect hooks used to invoke daemons
	 *
	 * @param array $daemon_hooks
	 * @return array
	 */
	public function daemon_hooks(Command $daemon, array $daemon_hooks): array {
		$daemon_hooks[] = ORM::class . '::daemon';
		return $daemon_hooks;
	}

	/**
	 * Getter/setter for database adapters
	 *
	 * @param string $code
	 * @param ORM_Database_Adapter $adapter
	 * @return \zesk\Module_ORM|\zesk\ORM_Database_Adapter|\zesk\ORM_Database_Adapter[string]
	 */
	public function database_adapter($code = null, ORM_Database_Adapter $adapter = null) {
		if ($code === null) {
			return $this->database_adapters;
		}
		if ($adapter === null) {
			return avalue($this->database_adapters, strtolower($code));
		}
		$this->database_adapter[strtolower($code)] = $adapter;
		return $this;
	}

	/**
	 *
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 */
	public function orm_registry(Application $application, string $class, mixed $mixed = null, array $options = []) {
		if ($class === null) {
			return $this;
		}
		$class = $application->objects->resolve($class);
		if ($mixed === null && is_array($options) && count($options) > 0) {
			$result = $this->_class_cache_component($class, 'object');
			if (!$result) {
				throw new Exception_Class_NotFound($class);
			}
			return $result;
		} else {
			return ORM::factory($application, $class, $mixed, $options);
		}
	}

	/**
	 *
	 * @param Application $application
	 * @param string $class
	 * @param mixed|null $mixed
	 * @param array $options
	 * @return ORM
	 * @throws Exception_Semantics
	 */
	public function orm_factory(Application $application, string $class, mixed $mixed = null, array $options = []): ORM {
		$class = $application->objects->resolve($class);
		return ORM::factory($application, $class, $mixed, $options);
	}

	/**
	 *
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 */
	public function class_orm_registry(Application $application, $class, $mixed = null, array $options = []) {
		$result = $this->_class_cache_component($application->objects->resolve($class), 'class');
		if (!$result) {
			throw new Exception_Class_NotFound($class);
		}
		return $result;
	}

	/**
	 * When zesk\Hooks::all_hook is called, this is called first to collect all objects
	 * in the system.
	 */
	public static function hooks(Application $application): void {
		$application->hooks->add(ORM::class . '::register_all_hooks', __CLASS__ . '::object_register_all_hooks');
	}

	/**
	 *
	 * @param Application $app
	 */
	public static function object_register_all_hooks(Application $app): void {
		$classes = $app->orm_module()->all_classes();
		$app->classes->register(ArrayTools::collapse($classes, 'class'));
	}

	/**
	 *
	 * @var string[]
	 */
	private ?array $cached_classes = null;

	/**
	 * Retrieve the list of classes associated with an application
	 *
	 * @return array
	 */
	private function _classes(): array {
		$classes = [];
		$model_classes = $this->application->call_hook_arguments('orm_classes', [], []);
		$this->application->logger->debug('Classes from {class}->model_classes = {value}', [
			'class' => get_class($this),
			'value' => $model_classes,
		]);
		$classes = $classes + ArrayTools::valuesFlipCopy($model_classes, true);
		$all_classes = $this->call_hook_arguments('classes', [$classes, ], $classes);
		/* @var $module Module */
		foreach ($this->application->modules->all_modules() as $name => $module) {
			$module_classes = $module->model_classes();
			$this->application->logger->debug('Classes for module {name} = {value}', [
				'name' => $name,
				'value' => $module_classes,
			]);
			$all_classes = array_merge($all_classes, ArrayTools::valuesFlipCopy($module_classes, true));
		}
		$this->application->classes->register(array_values($all_classes));
		ksort($all_classes);
		return $all_classes;
	}

	/**
	 * Synchronize the schema.
	 *
	 * @param Database|null $db
	 * @param array|null $classes
	 * @param array $options
	 * @return string[]
	 * @throws Database_Exception
	 */
	public function schema_synchronize(Database $db = null, array $classes = null, array $options = []): array {
		if (!$db) {
			$db = $this->application->database_registry();
		}
		if ($classes === null) {
			$classes = $this->ormClasses();
		} else {
			$options['follow'] = toBool($options['follow'] ?? false);
		}
		$logger = $this->application->logger;
		$logger->debug('{method}: Synchronizing classes: {classes}', ['method' => __METHOD__, 'classes' => $classes, ]);
		$results = [];
		$objects_by_class = [];
		$other_updates = [];
		$follow = $options['follow'] ?? true;
		while (count($classes) > 0) {
			$class = array_shift($classes);
			if (stripos($class, 'user_role')) {
				$logger->debug('{method}: ORM map is: {map}', [
					'method' => __METHOD__,
					'map' => _dump($this->application->objects->mapping()),
					'class' => $class,
				]);
			}
			$resolved_class = $this->application->objects->resolve($class);
			if ($resolved_class !== $class) {
				$logger->debug('{resolved_class} resolved to {class}', [
					'resolved_class' => $resolved_class,
					'class' => $class,
				]);
			}
			$class = $resolved_class;
			$low_class = strtolower($class);
			if (isset($objects_by_class[$low_class])) {
				continue;
			}
			$logger->debug("Parsing $class");
			$objects_by_class[$low_class] = true;

			try {
				$object = $this->application->orm_registry($class);
				$object_db_name = $object->database()->codeName();
				$updates = ORM_Schema::update_object($object);
			} catch (Exception_Class_NotFound $e) {
				$logger->error('Unable to synchronize {class} because it can not be found', ['class' => $class, ]);
				continue;
			} catch (Database_Exception $e) {
				$logger->error("Unable to synchronize {class} because of {exception_class} {message}\nTRACE: {trace}", [
					'class' => $class,
					'message' => $e->getMessage(),
					'exception_class' => get_class($e),
					'trace' => $e->getTraceAsString(),
					'exception' => $e,
				]);

				throw $e;
			}
			if (count($updates) > 0) {
				$updates = array_merge(["-- Synchronizing schema for class: $class", ], $updates);
				if ($object_db_name !== $db->codeName()) {
					$other_updates[$object_db_name] = true;
					$logger->debug('Result of schema parse for {class}: {n} changes - Database {dbname}', [
						'class' => $class,
						'n' => count($updates),
						'updates' => $updates,
						'dbname' => $object_db_name,
					]);
					$updates = [];
				} else {
					$logger->debug('Result of schema parse for {class}: {n} updates', [
						'class' => $class,
						'n' => count($updates),
						'updates' => $updates,
					]);
				}
			}
			$results = array_merge($results, $updates);
			if ($follow) {
				$dependencies = $object->dependencies();
				if (is_array($dependencies['requires'] ?? null)) {
					foreach ($dependencies['requires'] as $require) {
						if ($objects_by_class[$require] ?? null) {
							continue;
						}
						$logger->debug("$class: Adding dependent class $require");
						$classes[] = $require;
					}
				}
			}
		}

		$skip_others = isset($options['skip_others']) && $options['skip_others'];
		if (count($other_updates) > 0 && !$skip_others) {
			$results[] = "-- Other database updates:\n" . ArrayTools::joinWrap(array_keys($other_updates), '-- zesk schema --name ', " --update;\n");
		}
		return $results;
	}

	/**
	 * @return array
	 */
	final public function ormClasses(): array {
		if ($this->cached_classes === null) {
			$this->cached_classes = $this->_classes();
		}
		return array_values($this->cached_classes);
	}

	/**
	 *
	 * @param string|array $add List of classes to add
	 */
	final public function addORMClasses(string|array $add): self {
		if ($this->cached_classes === null) {
			$this->cached_classes = $this->_classes();
		}
		foreach (toList($add) as $class) {
			$this->cached_classes[strtolower($class)] = $class;
		}
		return $this;
	}

	/**
	 * Retrieve all classes with additional fields
	 *
	 * @return array
	 * @todo move ORM related to hooks
	 *
	 */
	final public function all_classes() {
		$classes = $this->orm_classes();
		$objects_by_class = [];
		$is_table = false;
		$rows = [];
		while (count($classes) > 0) {
			$class = array_shift($classes);
			$low_class = strtolower($class);
			if (array_key_exists($low_class, $objects_by_class)) {
				continue;
			}
			$result = [];
			$result['class'] = $class;

			try {
				$result['object'] = $object = $this->orm_factory($this->application, $class);
				$result['database'] = $object->databaseName();
				$result['table'] = $object->table();
				$result['class'] = get_class($object);
			} catch (\Exception $e) {
				$result['object'] = $object = null;
			}
			$objects_by_class[$low_class] = $result;
			if ($object) {
				$dependencies = $object->dependencies();
				if (is_array($dependencies['requires'] ?? null)) {
					foreach ($dependencies['requires'] as $require) {
						$require = strtolower($require);
						if (array_key_exists($require, $objects_by_class)) {
							continue;
						}
						$classes[] = $require;
					}
				}
			}
		}
		return $objects_by_class;
	}

	/**
	 * @return $this
	 */
	public function clearCache(): self {
		$this->class_cache = [];
		return $this;
	}

	/**
	 * @param string|ORM|Class_ORM $class
	 * @return $this
	 */
	public function clearNamedCache(string|ORM|Class_ORM $class): self {
		if ($class instanceof ORM) {
			$class = get_class($class);
		} elseif ($class instanceof Class_ORM) {
			$class = $class->class;
		}
		assert(is_string($class));
		$low_class = strtolower($class);
		if (array_key_exists($low_class, $this->class_cache)) {
			unset($this->class_cache[$low_class]);
		}
		return $this;
	}

	/**
	 * Retrieve object or classes from cache
	 *
	 * @param string $class
	 * @return array
	 * @throws Exception_Semantics
	 */
	private function _class_cache(string $class): array {
		$low_class = strtolower($class);
		if (!array_key_exists($low_class, $this->class_cache)) {
			$object = $this->modelFactory($class, null, ['immutable' => true, ]);
			if (!$object instanceof ORM) {
				throw new Exception_Semantics("$class is not an ORM");
			}
			$this->class_cache[$low_class] = [
				'table' => $object->table(),
				'dbname' => $object->databaseName(),
				'database_name' => $object->databaseName(),
				'object' => $object,
				'class' => $object->class_orm(),
				'id_column' => $object->idColumn(),
			];
		}
		return $this->class_cache[$low_class];
	}

	/**
	 * @param string $class
	 * @param string $component
	 * @return mixed
	 * @throws Exception_Semantics
	 */
	private function _class_cache_component(string $class, string $component): mixed {
		$result = $this->_class_cache($class);
		assert(array_key_exists($component, $result));
		return $result[$component];
	}

	/**
	 * While developing, check schema every minute
	 */
	public function cron_cluster_minute(): void {
		if ($this->application->development()) {
			$this->_schema_check();
		}
	}

	/**
	 * While an out-of-sync schema may cause issues, it often does not.
	 * Check hourly on production to avoid
	 * checking the database incessantly.
	 */
	public function cron_cluster_hour(Application $application): void {
		if (!$this->application->development()) {
			$this->_schema_check();
		}
	}

	/**
	 * Internal function - check the schema and notify someone
	 *
	 * @todo some sort of communication, a hook?
	 */
	protected function _schema_check() {
		/* @var $application Application */
		$results = $this->schema_synchronize();
		if (count($results) === 0) {
			return false;
		}
		$logger = $this->application->logger;
		if ($this->optionBool('schema_sync')) {
			$db = $this->application->database_registry();
			$logger->warning('The database schema was out of sync, updating: {sql}', ['sql' => implode(";\n", $results) . ";\n", ]);
			$db->queries($results);
		} else {
			$logger->warning('The database schema is out of sync, please update: {sql}', ['sql' => implode(";\n", $results) . ";\n", ]);
			//TODO How to communicate with main UI?
			// 				$router = $this->router();
			// 				$url = $router->get_route("schema_synchronize", $application);
			// 				$message = $url ? HTML::wrap(__("The database schema is out of sync, please [update it immediately.]"), HTML::a($url, '[]')) : __("The database schema is out of sync, please update it immediately.");
			// 				Response::instance($application)->redirect_message($message, array(
			// 					"url" => $url
			// 				));
		}
		return $results;
	}

	/**
	 * Registry of settings, currently
	 *
	 * @var Settings[string]
	 */
	private $registry = [];

	/**
	 *
	 * @param Application $application
	 * @param string $class
	 * @return \zesk\Settings[string]
	 */
	public function settings_registry(Application $application, string $class = ''): Settings {
		if ($class === '') {
			$class = $this->option('settings_class', Settings::class);
		}
		$low_class = strtolower($class);
		if (isset($this->registry[$low_class])) {
			return $this->registry[$low_class];
		}
		return $this->registry[$low_class] = $this->application->orm_factory($class);
	}

	/**
	 * Automatically set a SQL type for a database column if it just has a Class_ORM::type_FOO set
	 *
	 * @param Database_Table $table
	 * @param Database_Column $column
	 */
	public function database_table_add_column(Database_Table $table, Database_Column $column): void {
		if ($column->hasSQLType()) {
			return;
		}
		$database = $table->database();
		$code = strtolower($database->type());
		if (!array_key_exists($code, $this->database_adapters)) {
			$this->application->logger->error('{method} {table} {column} - no adapter for database {code}', [
				'method' => __METHOD__,
				'table' => $table,
				'column' => $column,
				'code' => $code,
			]);
			return;
		}
		$adapter = $this->database_adapters[$code];
		$adapter->database_column_set_type($column);
	}

	/**
	 * Run beforehand.
	 */
	public function hook_cron_before(): void {
		$application = $this->application;
		$server = $application->orm_factory(Server::class);
		/* @var $server Server */
		$server->bury_dead_servers();
	}

	/*---------------------------------------------------------------------------------------------------------*\
	  ---------------------------------------------------------------------------------------------------------
	  ---------------------------------------------------------------------------------------------------------
			 _                               _           _
		  __| | ___ _ __  _ __ ___  ___ __ _| |_ ___  __| |
		 / _` |/ _ \ '_ \| '__/ _ \/ __/ _` | __/ _ \/ _` |
		| (_| |  __/ |_) | | |  __/ (_| (_| | ||  __/ (_| |
		 \__,_|\___| .__/|_|  \___|\___\__,_|\__\___|\__,_|
				   |_|
	  ---------------------------------------------------------------------------------------------------------
	  ---------------------------------------------------------------------------------------------------------
	\*---------------------------------------------------------------------------------------------------------*/

	/**
	 * @param string|ORM|Class_ORM|null $class
	 * @return $this
	 * @deprecated 2022-05
	 */
	public function clear_cache(string|ORM|Class_ORM $class = null): self {
		return ($class === null) ? $this->clearCache() : $this->clearORMCache($class);
	}

	/**
	 * @param string|array $add List of classes to add
	 * @deprecated 2022-05
	 */
	final public function orm_classes(string|array $add = null): array {
		if ($add !== null) {
			return $this->addORMClasses($add);
		}
		return $this->ormClasses();
	}
}
