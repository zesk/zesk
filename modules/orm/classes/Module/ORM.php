<?php
/**
 * @copyright &copy; 2017 Market Acumen, Inc.
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
	public $orm_classes = array(Server::class, Settings::class, Meta::class, Domain::class, Lock::class, );

	/**
	 *
	 * @var ORM[]
	 */
	private $class_cache = array();

	/**
	 *
	 * @var ORM_Database_Adapter[string]
	 */
	private $database_adapters = array();

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize() {
		parent::initialize();
		/**
		 * @deprecated 2018-01-02
		 */
		$this->application->configuration->deprecated(ORM::class . '::fix_member_objects', ORM::class . "::fix_orm_members");

		/**
		 * $application->orm_factory(...)
		 */
		$this->application->register_factory("orm", array($this, "orm_factory", ));
		/**
		 * $application->orm_registry(...)
		 */
		$this->application->register_registry("orm", array($this, "orm_registry", ));
		/**
		 * $application->class_orm_registry(...)
		 */
		$this->application->register_registry("class_orm", array($this, "class_orm_registry", ));

		/**
		 * $application->settings_registry(...)
		 */
		$this->application->register_registry("settings", array($this, "settings_registry", ));

		/**
		 * Hook into database table
		 */
		$this->application->hooks->add(Database_Table::class . '::column_add', array($this, "database_table_add_column", ));

		$this->application->hooks->add('zesk\\Command_Daemon::daemon_hooks', array($this, "daemon_hooks", ));

		$self = $this;
		$this->application->hooks->add(ORM::class . "::router_derived_classes", function (ORM $object, array $classes) use ($self) {
			$class_object = $object->class_orm();
			if (!is_array($class_object->has_one) || !$class_object->id_column) {
				return $classes;
			}
			foreach ($class_object->has_one as $member => $class) {
				try {
					$member_object = $object->__get($member);
					if ($member_object !== null && !$member_object instanceof ORM) {
						$this->application->logger->error("Member {member} of object {class} should be an object of {expected_class}, returned {type} with value {value}", array("member" => $member, "class" => get_class($object), "expected_class" => $class, "type" => type($member_object), "value" => strval($member_object), ));

						continue;
					}
				} catch (Exception_ORM_NotFound $e) {
					$member_object = null;
				}
				if ($member_object) {
					$id = $member_object->id();
					foreach ($this->application->classes->hierarchy($member_object, "zesk\\ORM") as $class) {
						$classes[$class] = $id;
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
	 * @return string
	 */
	public function daemon_hooks(Command $daemon, array $daemon_hooks) {
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
	 * @param Application $application
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 * @return \zesk\ORM
	 */
	public function orm_factory(Application $application, $class = null, $mixed = null, $options = null) {
		return ORM::factory($application, $class, $mixed, to_array($options));
	}

	/**
	 *
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 */
	public function orm_registry(Application $application, $class = null, $mixed = null, $options = null) {
		if ($class === null) {
			return $this;
		}
		$class = $application->objects->resolve($class);
		if ($mixed === null && is_array($options) && count($options) > 0) {
			$result = $this->_class_cache($class, "object");
			if (!$result) {
				throw new Exception_Class_NotFound($class);
			}
			return $result;
		} else {
			return ORM::factory($application, $class, $mixed, (array) $options);
		}
	}

	/**
	 *
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 */
	public function class_orm_registry(Application $application, $class, $mixed = null, array $options = array()) {
		$result = $this->_class_cache($application->objects->resolve($class), "class");
		if (!$result) {
			throw new Exception_Class_NotFound($class);
		}
		return $result;
	}

	/**
	 * When zesk\Hooks::all_hook is called, this is called first to collect all objects
	 * in the system.
	 */
	public static function hooks(Application $application) {
		$application->hooks->add(ORM::class . '::register_all_hooks', __CLASS__ . "::object_register_all_hooks");
	}

	/**
	 *
	 * @param Application $app
	 */
	public static function object_register_all_hooks(Application $app) {
		$classes = $app->orm_module()->all_classes();
		$app->classes->register(ArrayTools::collapse($classes, "class"));
	}

	/**
	 *
	 * @var string[]
	 */
	private $cached_classes = null;

	/**
	 * Retrieve the list of classes associated with an application
	 *
	 * @param mixed $add
	 *            Class to add, or array of classes to add
	 * @return array
	 */
	private function _classes($add = null) {
		$classes = array();
		$model_classes = $this->application->call_hook_arguments("orm_classes", array(), array());
		$this->application->logger->debug("Classes from {class}->model_classes = {value}", array("class" => get_class($this), "value" => $model_classes, ));
		$classes = $classes + ArrayTools::flip_copy($model_classes, true);
		$all_classes = $this->call_hook_arguments('classes', array($classes, ), $classes);
		/* @var $module Module */
		foreach ($this->application->modules->all_modules() as $name => $module) {
			$module_classes = $module->model_classes();
			$this->application->logger->debug("Classes for module {name} = {value}", array("name" => $name, "value" => $module_classes, ));
			$all_classes = array_merge($all_classes, ArrayTools::flip_copy($module_classes, true));
		}
		$this->application->classes->register(array_values($all_classes));
		ksort($all_classes);
		return $all_classes;
	}

	/**
	 * Synchronzie the schema.
	 *
	 * @return string[]
	 */
	public function schema_synchronize(Database $db = null, array $classes = null, array $options = array()) {
		if (!$db) {
			$db = $this->application->database_registry();
		}
		if ($classes === null) {
			$classes = $this->orm_classes();
		} else {
			$options['follow'] = avalue($options, 'follow', false);
		}
		$logger = $this->application->logger;
		$logger->debug("{method}: Synchronizing classes: {classes}", array("method" => __METHOD__, "classes" => $classes, ));
		$results = array();
		$objects_by_class = array();
		$other_updates = array();
		$follow = avalue($options, 'follow', true);
		while (count($classes) > 0) {
			$class = array_shift($classes);
			if (stripos($class, 'user_role')) {
				$logger->debug("{method}: ORM map is: {map}", array("method" => __METHOD__, "map" => _dump($this->application->objects->map()), "class" => $class, ));
			}
			$resolved_class = $this->application->objects->resolve($class);
			if ($resolved_class !== $class) {
				$logger->debug("{resolved_class} resolved to {class}", array("resolved_class" => $resolved_class, "class" => $class, ));
			}
			$class = $resolved_class;
			$lowclass = strtolower($class);
			if (avalue($objects_by_class, $lowclass)) {
				continue;
			}
			$logger->debug("Parsing $class");
			$objects_by_class[$lowclass] = true;

			try {
				$object = $this->application->orm_registry($class);
				$object_db_name = $object->database()->code_name();
				$updates = ORM_Schema::update_object($object);
			} catch (Exception_Class_NotFound $e) {
				$logger->error("Unable to synchronize {class} because it can not be found", array("class" => $class, ));
				continue;
			} catch (Exception $e) {
				$logger->error("Unable to synchronize {class} because of {exception_class} {message}\nTRACE: {trace}", array("class" => $class, "message" => $e->getMessage(), "exception_class" => get_class($e), "trace" => $e->getTraceAsString(), "exception" => $e, ));

				throw $e;

				continue;
			}
			if (count($updates) > 0) {
				$updates = array_merge(array("-- Synchronizing schema for class: $class", ), $updates);
				if ($object_db_name !== $db->code_name()) {
					$other_updates[$object_db_name] = true;
					$updates = array();
					$logger->debug("Result of schema parse for {class}: {n} changes - Database {dbname}", array("class" => $class, "n" => count($updates), "updates" => $updates, "dbname" => $object_db_name, ));
				} else {
					$logger->debug("Result of schema parse for {class}: {n} updates", array("class" => $class, "n" => count($updates), "updates" => $updates, ));
				}
			}
			$results = array_merge($results, $updates);
			if ($follow) {
				$dependencies = $object->dependencies();
				$requires = avalue($dependencies, 'requires', array());
				foreach ($requires as $require) {
					if (avalue($objects_by_class, $require)) {
						continue;
					}
					$logger->debug("$class: Adding dependent class $require");
					$classes[] = $require;
				}
			}
		}

		$skip_others = isset($options['skip_others']) && boolval($options['skip_others']);
		if (count($other_updates) > 0 && !$skip_others) {
			$results[] = "-- Other database updates:\n" . ArrayTools::join_wrap(array_keys($other_updates), "-- zesk schema --name ", " --update;\n");
		}
		return $results;
	}

	/**
	 *
	 * @param unknown $add
	 */
	final public function orm_classes($add = null) {
		if ($this->cached_classes === null) {
			$this->cached_classes = $this->_classes();
		}
		if ($add !== null) {
			foreach (to_list($add) as $class) {
				$this->cached_classes[strtolower($class)] = $class;
			}
			return $this;
		}
		return array_values($this->cached_classes);
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
		$objects_by_class = array();
		$is_table = false;
		$rows = array();
		while (count($classes) > 0) {
			$class = array_shift($classes);
			$lowclass = strtolower($class);
			if (array_key_exists($lowclass, $objects_by_class)) {
				continue;
			}
			$result = array();
			$result['class'] = $class;

			try {
				$result['object'] = $object = $this->orm_factory($this->application, $class);
				if (!$object instanceof ORM) {
					$this->application->logger->warning("{method} {class} is not an instanceof {parent}", array("method" => __METHOD__, "class" => $class, "parent" => ORM::class, ));

					continue;
				}
				$result['database'] = $object->database_name();
				$result['table'] = $object->table();
				$result['class'] = get_class($object);
			} catch (\Exception $e) {
				$result['object'] = $object = null;
			}
			$objects_by_class[$lowclass] = $result;
			if ($object) {
				$dependencies = $object->dependencies();
				$requires = avalue($dependencies, 'requires', array());
				foreach ($requires as $require) {
					$require = strtolower($require);
					if (array_key_exists($require, $objects_by_class)) {
						continue;
					}
					$classes[] = $require;
				}
			}
		}
		return $objects_by_class;
	}

	public function clear_cache($class = null) {
		if ($class instanceof ORM) {
			$class = get_class($class);
		} elseif ($class instanceof Class_ORM) {
			$class = $class->class;
		}
		if ($class === null) {
			$this->class_cache = array();
			return $this;
		}
		if (!is_string($class)) {
			throw new Exception_Parameter("Invalid class passed to {method}: {value} ({type})", array("method" => __METHOD__, "type" => type($class), "value" => $class, ));
		}
		$lowclass = strtolower($class);
		if (array_key_exists($lowclass, $this->class_cache)) {
			unset($this->class_cache[$lowclass]);
		}
		return $this;
	}

	/**
	 * Retrieve object or classes from cache
	 *
	 * @param string $class
	 * @param string $component
	 *            Optional component to retrieve
	 * @return Ambigous <mixed, array>
	 * @throws Exception_Semantics
	 * @throws Exception_Parameter
	 */
	private function _class_cache($class, $component = "") {
		if (!is_string($class) && !is_integer($class)) {
			throw new Exception_Parameter("Requires a scalar key {type} {value}", ["type" => type($class), "value" => str($class)]);
		}
		$lowclass = strtolower($class);
		if (!array_key_exists($lowclass, $this->class_cache)) {
			$object = $this->model_factory($class, null, array("immutable" => true, ));
			if (!$object instanceof ORM) {
				throw new Exception_Semantics("$class is not an ORM");
			}
			$this->class_cache[$lowclass] = array('table' => $object->table(), 'dbname' => $object->database_name(), 'database_name' => $object->database_name(), 'object' => $object, 'class' => $object->class_orm(), 'id_column' => $object->id_column(), );
		}
		$result = $this->class_cache[$lowclass];
		return avalue($result, $component, $result);
	}

	/**
	 * While developing, check schema every minute
	 */
	public function cron_cluster_minute() {
		if ($this->application->development()) {
			$this->_schema_check();
		}
	}

	/**
	 * While an out-of-sync schema may cause issues, it often does not.
	 * Check hourly on production to avoid
	 * checking the database incessantly.
	 */
	public function cron_cluster_hour(Application $application) {
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
		if ($this->option_bool('schema_sync')) {
			$db = $this->application->database_registry();
			$logger->warning("The database schema was out of sync, updating: {sql}", array("sql" => implode(";\n", $results) . ";\n", ));
			$db->query($results);
		} else {
			$logger->warning("The database schema is out of sync, please update: {sql}", array("sql" => implode(";\n", $results) . ";\n", ));
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
	private $registry = array();

	/**
	 *
	 * @param Application $application
	 * @param string $class
	 * @return \zesk\Settings[string]
	 */
	public function settings_registry(Application $application, $class = null) {
		if ($class === null) {
			$class = $this->option("settings_class", Settings::class);
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
	public function database_table_add_column(Database_Table $table, Database_Column $column) {
		if ($column->has_sql_type()) {
			return;
		}
		$database = $table->database();
		$code = strtolower($database->type());
		if (!array_key_exists($code, $this->database_adapters)) {
			$this->application->logger->error("{method} {table} {column} - no adapter for database {code}", array("method" => __METHOD__, "table" => $table, "column" => $column, "code" => $code, ));
			return;
		}
		$adapter = $this->database_adapters[$code];
		$adapter->database_column_set_type($column);
	}
	
	/**
	 * Run beforehand.
	 */
	public function hook_cron_before() {
		$application = $this->application;
		$server = $application->orm_factory(Server::class);
		/* @var $server Server */
		$server->bury_dead_servers();
	}
}
