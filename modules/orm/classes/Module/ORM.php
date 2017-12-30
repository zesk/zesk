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
	public $orm_classes = array(
		Server::class,
		Settings::class,
		Meta::class,
		Domain::class,
		Lock::class
	);
	
	/**
	 *
	 * @var ORM[string]
	 */
	private $class_cache = array();
	
	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Module::initialize()
	 */
	public function initialize() {
		parent::initialize();
		$this->application->register_factory("orm", array(
			$this,
			"orm_factory"
		));
		$this->application->register_registry("orm", array(
			$this,
			"orm_registry"
		));
		$this->application->register_registry("class_orm", array(
			$this,
			"class_orm_registry"
		));
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
	 *
	 * @todo PHP7 Add Closure here to avoid global usage
	 */
	public static function hooks(Kernel $zesk) {
		$zesk->hooks->add(ORM::class . '::register_all_hooks', __CLASS__ . "::object_register_all_hooks");
	}
	
	/**
	 * 
	 * @param Application $app
	 */
	public static function object_register_all_hooks(Application $app) {
		$classes = $app->modules->object("orm")->all_classes();
		$app->classes->register(arr::collapse($classes, "class"));
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
	 *        	Class to add, or array of classes to add
	 * @return array
	 */
	private function _classes($add = null) {
		$classes = array();
		$model_classes = array_merge($this->model_classes, $this->model_classes);
		$this->application->logger->debug("Classes from {class}->model_classes = {value}", array(
			"class" => get_class($this),
			"value" => $model_classes
		));
		$classes = $classes + arr::flip_copy($model_classes, true);
		$all_classes = $this->call_hook_arguments('classes', array(
			$classes
		), $classes);
		/* @var $module Module */
		foreach ($this->application->modules->all_modules() as $name => $module) {
			$module_classes = $module->classes();
			$this->application->logger->debug("Classes for module {name} = {value}", array(
				"name" => $name,
				"value" => $module_classes
			));
			$all_classes = array_merge($all_classes, arr::flip_copy($module_classes, true));
		}
		$this->application->classes->register(array_values($all_classes));
		ksort($all_classes);
		return $all_classes;
	}
	
	/**
	 * Synchronzie the schema. 
	 *
	 * @return multitype:
	 */
	public function schema_synchronize(Database $db = null, array $classes = null, array $options = array()) {
		if ($this->application->objects !== $this->application->zesk->objects) {
			// KMD: I assume this must have happened once and should not ever happen again.
			// If it does it's a SNAFU
			$this->application->logger->emergency("App objects mismatch kernel {file}:{line}", array(
				"file" => __FILE__,
				"line" => __LINE__
			));
			exit(131);
		}
		if (!$db) {
			$db = $this->application->database_factory();
		}
		if ($classes === null) {
			$classes = $this->orm_classes();
		} else {
			$options['follow'] = avalue($options, 'follow', false);
		}
		$logger = $this->application->logger;
		$logger->debug("{method}: Synchronizing classes: {classes}", array(
			"method" => __METHOD__,
			"classes" => $classes
		));
		$results = array();
		$objects_by_class = array();
		$other_updates = array();
		$follow = avalue($options, 'follow', true);
		while (count($classes) > 0) {
			$class = array_shift($classes);
			if (stripos($class, 'user_role')) {
				$logger->debug("{method}: ORM map is: {map}", array(
					"method" => __METHOD__,
					"map" => _dump($this->objects->map()),
					"class" => $class
				));
			}
			$resolved_class = $this->application->objects->resolve($class);
			if ($resolved_class !== $class) {
				$logger->debug("{resolved_class} resolved to {class}", array(
					"resolved_class" => $resolved_class,
					"class" => $class
				));
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
			} catch (Exception $e) {
				$logger->error("Unable to synchronize {class} because of {exception_class} {message}", array(
					"class" => $class,
					"message" => $e->getMessage(),
					"exception_class" => get_class($e),
					"exception" => $e
				));
				throw $e;
				continue;
			}
			if (count($updates) > 0) {
				$updates = array_merge(array(
					"-- Synchronizing schema for class: $class"
				), $updates);
				if ($object_db_name !== $db->code_name()) {
					$other_updates[$object_db_name] = true;
					$updates = array();
					$logger->debug("Result of schema parse for {class}: {n} changes - Database {dbname}", array(
						"class" => $class,
						"n" => count($updates),
						"updates" => $updates,
						"dbname" => $object_db_name
					));
				} else {
					$logger->debug("Result of schema parse for {class}: {n} updates", array(
						"class" => $class,
						"n" => count($updates),
						"updates" => $updates
					));
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
		if (count($other_updates) > 0) {
			$results[] = "-- Other database updates:\n" . arr::join_wrap(array_keys($other_updates), "-- zesk database-schema --name ", " --update;\n");
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
	 * @todo move ORM related to hooks
	 *
	 * @return array
	 */
	final public function all_classes() {
		$classes = $this->orm_classes();
		$objects_by_class = array();
		$is_table = false;
		$rows = array();
		while (count($classes) > 0) {
			$class = array_shift($classes);
			if (!is_subclass_of($class, ORM::class)) {
				$this->logger->warning("{method} {class} is not a subclass of {parent}", array(
					"method" => __METHOD__,
					"class" => $class,
					"parent" => ORM::class
				));
				continue;
			}
			$lowclass = strtolower($class);
			if (array_key_exists($lowclass, $objects_by_class)) {
				continue;
			}
			$result = array();
			$result['class'] = $class;
			try {
				$result['object'] = $object = $this->orm_factory($this->application, $class);
				$result['database'] = $object->database_name();
				$result['table'] = $object->table();
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
		} else if ($class instanceof Class_ORM) {
			$class = $class->class;
		}
		if ($class === null) {
			$this->class_cache = array();
			return $this;
		}
		if (!is_string($class)) {
			throw new Exception_Parameter("Invalid class passed to {method}: {value} ({type})", array(
				"method" => __METHOD__,
				"type" => type($class),
				"value" => $class
			));
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
	 *        	Optional component to retrieve
	 * @throws Exception_Semantics
	 * @return Ambigous <mixed, array>
	 */
	private function _class_cache($class, $component = "") {
		if (!is_string($class) && !is_integer($class)) {
			var_dump($class);
			backtrace();
		}
		$lowclass = strtolower($class);
		if (!array_key_exists($lowclass, $this->class_cache)) {
			$object = $this->model_factory($class, null, array(
				"immutable" => true
			));
			if (!$object instanceof ORM) {
				throw new Exception_Semantics("$class is not an ORM");
			}
			$this->class_cache[$lowclass] = array(
				'table' => $object->table(),
				'dbname' => $object->database_name(),
				'database_name' => $object->database_name(),
				'object' => $object,
				'class' => $object->class_object(),
				'id_column' => $object->id_column()
			);
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
			$db = $this->application->database_factory();
			$logger->warning("The database schema was out of sync, updating: {sql}", array(
				"sql" => implode(";\n", $results) . ";\n"
			));
			$db->query($results);
		} else {
			$logger->warning("The database schema is out of sync, please update: {sql}", array(
				"sql" => implode(";\n", $results) . ";\n"
			));
			//TODO How to communicate with main UI?
			// 				$router = $this->router();
			// 				$url = $router->get_route("schema_synchronize", $application);
			// 				$message = $url ? _W(__("The database schema is out of sync, please [update it immediately.]"), HTML::a($url, '[]')) : __("The database schema is out of sync, please update it immediately.");
			// 				Response::instance($application)->redirect_message($message, array(
			// 					"url" => $url
			// 				));
		}
		return $results;
	}
}
