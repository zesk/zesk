<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\ORM;

use Throwable;
use zesk\Application;
use zesk\ArrayTools;
use zesk\Command;
use zesk\Database\Base;
use zesk\Database\Column;
use zesk\Database\Exception\SchemaException;
use zesk\Database\Table;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\Semantics;
use zesk\Exception\SyntaxException;
use zesk\Exception\TimeoutExpired;
use zesk\Exception\Unsupported;
use zesk\Interface\SettingsInterface;
use zesk\Module as BaseModule;
use zesk\ORM\Database\MySQLAdapter;
use zesk\ORM\Exception\ORMNotFound;
use zesk\Types;

/**
 * @see Class_Base
 * @see Model
 * @author kent
 */
class Module extends BaseModule {
	/**
	 * Your basic ORM classes.
	 *
	 * @var array
	 */
	public array $modelClasses = [Server::class, Settings::class, Meta::class, Domain::class, Lock::class, ];

	/**
	 *
	 * @var ORMBase[]
	 */
	private array $class_cache = [];

	/**
	 *
	 * @var array
	 */
	private array $databaseAdapters = [];

	/**
	 * Registry of settings, currently
	 *
	 * @var Settings[]
	 */
	private array $registry = [];

	/**
	 *
	 * @return void
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws Semantics
	 * @throws Unsupported
	 */
	public function initialize(): void {
		parent::initialize();
		$this->application->setOption(Application::OPTION_USER_CLASS, User::class, false);
		/**
		 * $application->ormFactory(...)
		 */
		$this->application->registerFactory('orm', $this->ormFactory(...));
		/**
		 * $application->ormRegistry(...)
		 */
		$this->application->registerRegistry('orm', $this->ormRegistry(...));
		/**
		 * $application->classORMRegistry(...)
		 */
		$this->application->registerRegistry('classORM', $this->classORMRegistry(...));
		/** Deprecated 2022-05 */
		$this->application->registerRegistry('class_orm', $this->classORMRegistry(...));

		/**
		 * $application->settingsRegistry(...)
		 */
		$this->application->registerRegistry('settings', $this->settingsRegistry(...));

		/**
		 * Hook into database table
		 */
		$this->application->hooks->add(Table::class . '::column_add', [$this, 'database_table_add_column', ]);

		$this->application->hooks->add('zesk\\Command_Daemon::daemon_hooks', [$this, 'daemon_hooks', ]);

		$this->application->hooks->add(ORMBase::class . '::router_derived_classes', function (ORMBase $object, array $classes) {
			$class_object = $object->class_orm();
			if (!$class_object->id_column) {
				return $classes;
			}
			foreach ($class_object->has_one as $member => $class) {
				try {
					$member_object = $object->__get($member);
					if ($member_object !== null && !$member_object instanceof ORMBase) {
						$this->application->logger->error('Member {member} of object {class} should be an object of {expected_class}, returned {type} with value {value}', [
							'member' => $member, 'class' => $object::class, 'expected_class' => $class,
							'type' => Types::type($member_object), 'value' => strval($member_object),
						]);

						continue;
					}
				} catch (ORMNotFound) {
					$member_object = null;
				}
				if ($member_object) {
					$id = $member_object->id();
					foreach ($this->application->classes->hierarchy($member_object, 'zesk\\ORMBase') as $orm_class) {
						$classes[$orm_class] = $id;
					}
				}
			}
			return $classes;
		});
		/**
		 * Support MySQL database adapter
		 */
		$this->databaseAdapters['mysql'] = $mysql = $this->application->factory(MySQLAdapter::class);
		$this->databaseAdapters['mysqli'] = $mysql;
	}

	/**
	 * Collect hooks used to invoke daemons
	 *
	 * @param Command $daemon
	 * @param array $daemon_hooks
	 * @return array
	 */
	public function daemon_hooks(Command $daemon, array $daemon_hooks): array {
		$daemon_hooks[] = ORMBase::class . '::daemon';
		return $daemon_hooks;
	}

	/**
	 *
	 * @param string $class
	 * @param mixed|null $mixed
	 * @param array $options
	 * @return ORMBase
	 * @throws ClassNotFound
	 */
	public function ormRegistry(string $class, mixed $mixed = null, array $options = []): ORMBase {
		$class = $this->application->objects->resolve($class);
		if ($mixed === null && is_array($options) && count($options) > 0) {
			$result = $this->_classCacheComponent($class, $mixed, $options, 'object');
			if (!$result) {
				throw new ClassNotFound($class);
			}
			return $result;
		} else {
			$model = ORMBase::factory($this->application, $class, $mixed, $options);
			assert($model instanceof ORMBase);
			return $model;
		}
	}

	/**
	 * @param string $class
	 * @return SettingsInterface
	 * @throws ClassNotFound
	 */
	public function settingsRegistry(string $class = ''): SettingsInterface {
		if ($class === '') {
			$class = $this->option('settings_class', Settings::class);
		}
		if (isset($this->registry[$class])) {
			return $this->registry[$class];
		}
		$result = $this->registry[$class] = $this->ormFactory($this->application, $class);
		assert($result instanceof Settings);
		return $result;
	}

	/**
	 *
	 * @param Application $application
	 * @param string $class
	 * @param mixed|null $mixed
	 * @param array $options
	 * @return ORMBase
	 * @throws ClassNotFound
	 */
	public function ormFactory(string $class, mixed $mixed = null, array $options = []): ORMBase {
		// $class is resolved deeper
		$orm = ORMBase::factory($this->application, $class, $mixed, $options);
		assert($orm instanceof ORMBase);
		return $orm;
	}

	/**
	 *
	 * @param Application $application
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 * @return Class_Base
	 * @throws ClassNotFound
	 */
	public function classORMRegistry(string $class, mixed $mixed = null, array $options = []): Class_Base {
		$class = $this->application->objects->resolve($class);
		$result = $this->_classCacheComponent($class, $mixed, $options, 'class');
		if (!$result) {
			throw new ClassNotFound($class);
		}
		assert($result instanceof Class_Base);
		return $result;
	}

	/**
	 * When zesk\Hooks::all_hook is called, this is called first to collect all objects
	 * in the system.
	 */
	public static function hooks(Application $application): void {
		$application->hooks->add(ORMBase::class . '::register_all_hooks', self::object_register_all_hooks(...));
	}

	/**
	 *
	 * @param Application $app
	 */
	public static function object_register_all_hooks(Application $app): void {
		$classes = $app->ormModule()->allClasses();
		$app->classes->register(ArrayTools::collapse($classes, 'class'));
	}

	/**
	 *
	 * @var string[]
	 */
	private ?array $_cachedClasses = null;

	/**
	 * Retrieve the list of classes associated with an application
	 *
	 * @return array
	 */
	private function _classes(): array {
		$classes = [];
		$model_classes = $this->application->callHookArguments('orm_classes', [], []);
		$this->application->logger->debug('Classes from {class}->model_classes = {value}', [
			'class' => get_class($this), 'value' => $model_classes,
		]);
		$classes = $classes + ArrayTools::valuesFlipCopy($model_classes);
		$all_classes = $this->callHookArguments('classes', [$classes, ], $classes);
		/* @var $module Module */
		foreach ($this->application->modules->all() as $name => $module) {
			$module_classes = $module->modelClasses();
			$this->application->logger->debug('Classes for module {name} = {value}', [
				'name' => $name, 'value' => $module_classes,
			]);
			$all_classes = array_merge($all_classes, ArrayTools::valuesFlipCopy($module_classes));
		}
		$this->application->classes->register(array_values($all_classes));
		ksort($all_classes);
		return $all_classes;
	}

	/**
	 * Synchronize the schema.
	 *
	 * @param Base|null $db
	 * @param array|null $classes
	 * @param array $options
	 * @return string[]
	 * @throws NotFoundException
	 * @throws SchemaException
	 * @throws Semantics
	 * @throws SyntaxException
	 * @throws ParameterException
	 */
	public function schemaSynchronize(Base $db = null, array $classes = null, array $options = []): array {
		if (!$db) {
			$db = $this->application->databaseRegistry();
		}
		if ($classes === null) {
			$classes = $this->ormClasses();
		} else {
			$options['follow'] = Types::toBool($options['follow'] ?? false);
		}
		$logger = $this->application->logger;
		$logger->debug('{method}: Synchronizing classes: {classes}', ['method' => __METHOD__, 'classes' => $classes, ]);
		$results = [];
		$objects_by_class = [];
		$other_updates = [];
		$follow = $options['follow'] ?? true;
		while (count($classes) > 0) {
			$class = array_shift($classes);
			$resolved_class = $this->application->objects->resolve($class);
			if ($resolved_class !== $class) {
				$logger->debug('{resolved_class} resolved to {class}', [
					'resolved_class' => $resolved_class, 'class' => $class,
				]);
			}
			$class = $resolved_class;
			if (isset($objects_by_class[$class])) {
				continue;
			}
			$logger->debug("Parsing $class");
			$objects_by_class[$class] = true;

			try {
				$object = $this->application->ormRegistry($class);
				$object_db_name = $object->database()->codeName();
				$updates = Schema::update_object($object);
			} catch (ClassNotFound) {
				$logger->error('Unable to synchronize {class} because it can not be found', ['class' => $class, ]);
				continue;
			} catch (Exception $e) {
				$message = 'Unable to synchronize {class} because of {throwableClass} {message}\nTRACE: {backtrace}';

				throw new SchemaException($db, '', $message, Exception::exceptionVariables($e), $e->getCode(), $e);
			}
			if (count($updates) > 0) {
				$updates = array_merge(["-- Synchronizing schema for class: $class", ], $updates);
				if ($object_db_name !== $db->codeName()) {
					$other_updates[$object_db_name] = true;
					$logger->debug('Result of schema parse for {class}: {n} changes - Database {dbname}', [
						'class' => $class, 'n' => count($updates), 'updates' => $updates, 'dbname' => $object_db_name,
					]);
					$updates = [];
				} else {
					$logger->debug('Result of schema parse for {class}: {n} updates', [
						'class' => $class, 'n' => count($updates), 'updates' => $updates,
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
		if ($this->_cachedClasses === null) {
			$this->_cachedClasses = $this->_classes();
		}
		return array_values($this->_cachedClasses);
	}

	/**
	 *
	 * @param string|array $add List of classes to add
	 */
	final public function addORMClasses(string|array $add): self {
		if ($this->_cachedClasses === null) {
			$this->_cachedClasses = $this->_classes();
		}
		foreach (Types::toList($add) as $class) {
			$this->_cachedClasses[$class] = $class;
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
	final public function allClasses(): array {
		$classes = $this->ormClasses();
		$objects_by_class = [];
		while (count($classes) > 0) {
			$class = array_shift($classes);
			if (array_key_exists($class, $objects_by_class)) {
				continue;
			}
			$result = [];
			$result['class'] = $class;

			try {
				$result['object'] = $object = $this->ormFactory($this->application, $class);
				$result['database'] = $object->databaseName();
				$result['table'] = $object->table();
				$result['class'] = $object::class;
			} catch (Throwable) {
				$result['object'] = $object = null;
			}
			$objects_by_class[$class] = $result;
			if ($object) {
				$dependencies = $object->dependencies();
				if (is_array($dependencies['requires'] ?? null)) {
					foreach ($dependencies['requires'] as $require) {
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
	 * @param string|ORMBase|Class_Base $class
	 * @return $this
	 */
	public function clearNamedCache(string|ORMBase|Class_Base $class): self {
		if ($class instanceof ORMBase) {
			$class = $class::class;
		} elseif ($class instanceof Class_Base) {
			$class = $class->class;
		}
		assert(is_string($class));
		if (array_key_exists($class, $this->class_cache)) {
			unset($this->class_cache[$class]);
		}
		return $this;
	}

	/**
	 * Retrieve object or classes from cache
	 *
	 * @param string $class
	 * @param mixed|null $mixed
	 * @param array $options
	 * @return array
	 * @throws ClassNotFound
	 */
	private function _classCache(string $class, mixed $mixed = null, array $options = []): array {
		if (!array_key_exists($class, $this->class_cache)) {
			$object = $this->modelFactory($class, $mixed, ['immutable' => true, ] + $options);
			assert($object instanceof ORMBase);
			$extras = ['keyed' => $object->hasPrimaryKeys(), 'generic' => count($options) === 0 && empty($mixed)];
			$this->class_cache[$class] = [
				'table' => $object->table(), 'dbname' => $object->databaseName(),
				'database_name' => $object->databaseName(), 'object' => $object, 'class' => $object->class_orm(),
				'id_column' => $object->idColumn(),
			] + $extras;
		}
		return $this->class_cache[$class];
	}

	/**
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 * @param string $component
	 * @return mixed
	 * @throws ClassNotFound
	 */
	private function _classCacheComponent(string $class, mixed $mixed, array $options, string $component): mixed {
		$result = $this->_classCache($class, $mixed, $options);
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
		if (!$application->development()) {
			$this->_schema_check();
		}
	}

	/**
	 * Internal function - check the schema and notify someone
	 *
	 * @todo some sort of communication, a hook?
	 */
	protected function _schema_check(): array {
		/* @var $application Application */
		$results = $this->schemaSynchronize();
		if (count($results) === 0) {
			return [];
		}
		$logger = $this->application->logger;
		if ($this->optionBool('schemaSynchronizeAutomatically')) {
			$db = $this->application->databaseRegistry();
			$logger->warning('The database schema was out of sync, updating: {sql}', ['sql' => implode(";\n", $results) . ";\n", ]);
			$db->queries($results);
		} else {
			$logger->warning('The database schema is out of sync, please update: {sql}', ['sql' => implode(";\n", $results) . ";\n", ]);
			//TODO How to communicate with main UI?
			// 				$router = $this->router();
			// 				$url = $router->getRoute("schema_synchronize", $application);
			// 				$message = $url ? HTML::wrap(__("The database schema is out of sync, please [update it immediately.]"), HTML::a($url, '[]')) : __("The database schema is out of sync, please update it immediately.");
			// 				Response::instance($application)->redirect_message($message, array(
			// 					"url" => $url
			// 				));
		}
		return $results;
	}

	/**
	 * Automatically set a SQL type for a database column if it just has a Class_Base::type_FOO set
	 *
	 * @param Table $table
	 * @param Column $column
	 */
	public function database_table_add_column(Table $table, Column $column): void {
		if ($column->hasSQLType()) {
			return;
		}
		$database = $table->database();
		$code = strtolower($database->type());
		if (!array_key_exists($code, $this->databaseAdapters)) {
			$this->application->logger->error('{method} {table} {column} - no adapter for database {code}', [
				'method' => __METHOD__, 'table' => $table, 'column' => $column, 'code' => $code,
			]);
			return;
		}
		$adapter = $this->databaseAdapters[$code];
		$adapter->database_column_set_type($column);
	}

	/**
	 * Run beforehand.
	 */
	public function hook_cron_before(): void {
		$application = $this->application;
		$server = $application->ormFactory(Server::class);
		/* @var $server Server */
		try {
			$server->buryDeadServers();
		} catch (TimeoutExpired $e) {
		}
	}
}
