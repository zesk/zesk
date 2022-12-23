<?php
declare(strict_types=1);

/**
 * @package zesk
 * @subpackage database
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\ORM;

use RuntimeException;
use zesk\Database_Exception_Connect;
use zesk\Exception_NotFound;
use zesk\Text;
use zesk\Application;
use zesk\Hookable;
use zesk\Database;
use zesk\Database_Table;
use zesk\Database_Column;
use zesk\Database_Index;
use zesk\Database_Exception;
use zesk\Database_Exception_Schema;
use zesk\Database_Exception_Table_NotFound;
use zesk\Exception_Semantics;
use zesk\Exception_Syntax;

abstract class Schema extends Hookable {
	public const type_id = Class_Base::TYPE_ID;

	/**
	 *
	 * Plain old text data in the database
	 * @var string
	 */
	public const type_string = Class_Base::TYPE_STRING;

	/**
	 * Refers to a system object (usually by ID)
	 *
	 * @var string
	 */
	public const type_object = Class_Base::TYPE_OBJECT;

	/**
	 * Upon initial save, set to current date
	 *
	 * @var string
	 */
	public const type_created = Class_Base::type_created;

	/**
	 * Upon all saves, updates to current date
	 *
	 * @var string
	 */
	public const type_modified = Class_Base::TYPE_MODIFIED;

	/**
	 * String information called using serialize/unserialize
	 *
	 * @var string
	 */
	public const type_serialize = Class_Base::TYPE_SERIALIZE;

	public const type_integer = Class_Base::TYPE_INTEGER;

	public const type_double = Class_Base::TYPE_FLOAT;

	public const type_boolean = Class_Base::type_boolean;

	public const type_timestamp = Class_Base::TYPE_TIMESTAMP;

	public const type_hex = Class_Base::type_hex;

	/**
	 * Debugging information.
	 * Lots to find problems with implementation or your SQL code.
	 *
	 * @var boolean
	 */
	public static bool $debug = false;

	/**
	 * Class object associated with this schema
	 *
	 * @var Class_Base
	 */
	protected Class_Base $class_object;

	/**
	 * ORM associated with this schema
	 *
	 * @var null|ORMBase
	 */
	protected null|ORMBase $object = null;

	/**
	 *
	 * @var Database
	 */
	protected $db = null;

	/**
	 * Create a new database schema
	 *
	 * @param Class_Base $class_object
	 * @param string $options
	 */
	public function __construct(Class_Base $class_object, ORMBase $object = null, array $options = []) {
		parent::__construct($class_object->application, $options);
		$this->class_object = $class_object;
		$this->object = $object;
	}

	/**
	 *
	 * @param Application $application
	 */
	public static function hooks(Application $application): void {
		$application->hooks->add('configured', __CLASS__ . '::configured');
		$application->configuration->path(__CLASS__);
	}

	/**
	 *
	 * @param Application $application
	 */
	public static function configured(Application $application): void {
		if ($application->configuration->debug || $application->configuration->getPath([__CLASS__, 'debug', ])) {
			self::$debug = true;
		}
	}

	/**
	 * @return Database
	 * @throws Database_Exception_Connect
	 * @throws Exception_NotFound
	 */
	final public function database(): Database {
		if ($this->db) {
			return $this->db;
		}
		if ($this->object) {
			return $this->db = $this->object->database();
		}
		return $this->db = $this->class_object->database();
	}

	/**
	 * Default schema map - include variables in your schema definitions from configuration
	 * settings.
	 *
	 * @return array
	 */
	protected function schema_map() {
		$map = $this->object ? $this->object->schema_map() : [];
		$map += $this->class_object->schemaMap();
		return $map;
	}

	/**
	 * Map using the schema map
	 *
	 * @param mixed $mixed
	 * @return mixed
	 */
	final public function map(string|array $mixed): string|array {
		if (is_array($mixed)) {
			$mixed = mapKeys($mixed, $this->schema_map());
		}
		return map($mixed, $this->schema_map());
	}

	/**
	 *
	 * @return string
	 */
	final public function primary_table(): string {
		return $this->class_object->table();
	}

	/**
	 * Generate the array-syntax schema
	 *
	 * @return array
	 */
	abstract public function schema(): array;

	/**
	 * Validate structure of array-syntax for index columns
	 *
	 * Generally: array("a","b","c") or array("index" => 42, "ID" => true)
	 *
	 * @param Database $db
	 * @param array $columns
	 * @return string boolean
	 */
	private static function validate_index_column_specification(Database $db, array $columns) {
		foreach ($columns as $k => $v) {
			if (is_numeric($k) && $db->validColumnName($v)) {
				continue;
			}
			if ((is_bool($v) || (is_numeric($v) && $v > 0)) && $db->validColumnName($k)) {
				continue;
			}
			return "$k => $v";
		}
		return true;
	}

	/**
	 * Convert an array-based table schema to a Database_Table object
	 *
	 * @param Database $db
	 * @param string $table_name
	 * @param array $table_schema
	 * @param string|null $context
	 * @return Database_Table
	 * @throws Database_Exception_Schema
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 * @throws Exception_Syntax
	 */
	public static function schema_to_database_table(Database $db, string $table_name, array $table_schema, string $context = null): Database_Table {
		$logger = $db->application->logger;

		$table = new Database_Table($db, $table_name, $table_schema['engine'] ?? '');
		if (array_key_exists('source', $table_schema)) {
			$table->setSource($table_schema['source']);
		}
		if (!array_key_exists('columns', $table_schema)) {
			throw new Exception_Syntax("No columns exist in table \"$table_name\" schema");
		}
		foreach ($table_schema['columns'] as $column_name => $column_spec) {
			if (!$db->validColumnName($column_name)) {
				$logger->error('Invalid index name in schema found in {table_name} in {context}: Invalid column name {index}', compact('context', 'table_name', 'column_name'));

				continue;
			}
			$table->columnAdd(new Database_Column($table, $column_name, $column_spec));
		}
		foreach ([
			'indexes' => Database_Index::TYPE_INDEX, 'unique keys' => Database_Index::TYPE_UNIQUE,
		] as $key => $index_type) {
			if (!array_key_exists($key, $table_schema)) {
				continue;
			}
			foreach ($table_schema[$key] as $index => $columns) {
				$__ = ['context' => $context, 'table_name' => $table_name, 'index' => $index];
				if (!$db->validIndexName($index)) {
					$logger->error('Invalid {index_type} in schema found in {table_name} in {context}: Invalid index name {index}', $__);

					continue;
				}
				if (!($error = self::validate_index_column_specification($db, $columns))) {
					$logger->error('Invalid index column spec {error} in schema found in {table_name} in {context}: Invalid index name {index}', ['error' => $error] + $__);

					continue;
				}

				try {
					$index = new Database_Index($table, $index, $index_type);
					$index->addColumns($columns);
				} catch (Exception_Semantics $e) {
					throw new Database_Exception_Schema($db, 'index {index}', 'Duplicate index {index}', $__, 0, $e);
				}
			}
		}
		if (array_key_exists('primary key', $table_schema)) {
			throw new Exception_Syntax("Table definition for $table_name should contain key \"primary keys\" not \"primary key\"");
		}
		$primary_columns = $table_schema['primary keys'] ?? null;
		if (is_array($primary_columns) && count($primary_columns) > 0) {
			if (!($error = self::validate_index_column_specification($db, $primary_columns))) {
				$logger->error('Invalid primary index column spec {error} in schema found in {table_name} in {context}: Invalid index name {index}', compact('error', 'context', 'table_name', 'index'));
			} else {
				if (!$table->hasIndex(Database_Index::NAME_PRIMARY)) {
					try {
						$index = new Database_Index($table, Database_Index::NAME_PRIMARY, Database_Index::TYPE_PRIMARY);
						$index->addColumns($primary_columns);
					} catch (Exception_Semantics $e) {
						throw new RuntimeException('!hasIndex -> addIndex failed for ' . Database_Index::NAME_PRIMARY);
					}
				} else {
					$index = $table->index(Database_Index::NAME_PRIMARY);
					foreach ($primary_columns as $primary_column) {
						$index->addColumn($primary_column);
					}
				}
			}
		}
		if (array_key_exists('on create', $table_schema)) {
			if (is_array($table_schema['on create'])) {
				$table->addActionSQL('create', $table_schema['on create']);
			} elseif (is_string($table_schema['on create'])) {
				$table->addActionSQL('create', [$table_schema['on create'], ]);
			}
		}
		return $table;
	}

	/**
	 * Return an array of tables associated with this schema
	 *
	 */
	/**
	 * @return Database_Table[]
	 * @throws Database_Exception_Schema
	 * @throws Exception
	 * @throws Exception_Semantics
	 * @throws Exception_Syntax
	 */
	public function tables(): array {
		$logger = $this->application->logger;
		$schema = $this->schema();
		$tables = [];
		$db = $this->database();
		if (is_array($schema)) {
			foreach ($schema as $table_name => $table_schema) {
				if (is_numeric($table_name)) {
					throw new Exception_Syntax(get_class($this) . " should contain an array of table schemas\n" . var_export($table_schema));
				}
				if (self::$debug) {
					$logger->debug('ORM_Schema: ' . $this->class_object->class . " \"$table_name\"");
				}
				/* @var $table Database_Table */
				try {
					$tables[$table_name] = $table = self::schema_to_database_table($db, $this->map($table_name), $table_schema);
				} catch (Exception $e) {
					$this->application->hooks->call('exception', $e);
					$logger->debug('Error with object ' . $this->class_object->class);

					throw $e;
				}

				if (array_key_exists('on create', $table_schema)) {
					$table->setOptionPath(['on', 'create'], $table_schema['on create']);
				}
			}
		}
		return $tables;
	}

	/**
	 * Conver the whole thing into a string.
	 *
	 * @return string
	 * @see Options::__toString()
	 */
	public function __toString() {
		$tables = $this->tables();

		$result = [];
		foreach ($tables as $table) {
			/* @var $table Database_Table */
			$create_sql = $table->sqlCreate();
			if (!is_array($create_sql)) {
				$create_sql = [$create_sql, ];
			}
			$result = array_merge($result, $create_sql);
		}
		return implode("\n", $result);
	}

	/**
	 * Turn on/off or get debug setting
	 *
	 * @param string $set
	 * @return boolean
	 */
	public static function debug($set = null) {
		if (is_bool($set)) {
			self::$debug = $set;
		}
		return self::$debug;
	}

	/**
	 * Return an array of SQL to update an object's schema to its database
	 * @param ORMBase $object
	 * @return array
	 * @throws Database_Exception
	 */
	public static function update_object(ORMBase $object): array {
		$logger = $object->application->logger;

		/* @var $db Database */
		$db = $object->database();
		if (!$db) {
			throw new Database_Exception($object->database(), 'Can not connect to {url}', [
				'url' => $db->safeURL(),
			]);
		}
		$object_class = $object::class;
		$schema = $object->database_schema();
		if (!$schema instanceof Schema) {
			$logger->warning('{class} did not return a ORM_Schema ({type})', [
				'class' => $object_class, 'type' => type($schema),
			]);
			return [];
		}
		return $schema->_update_object();
	}

	/**
	 * Internal function helper for update_object
	 *
	 * @return array
	 * @see update_object
	 */
	protected function _update_object(): array {
		$db = $this->database();

		try {
			$tables = $this->tables();
		} catch (Exception_Semantics $e) {
			throw $e;
		}
		$sql_results = [];
		foreach ($tables as $table) {
			/* @var $table Database_Table */
			try {
				$actual_table = $db->databaseTable($table->name());
				$sql_results = array_merge($sql_results, self::update($db, $actual_table, $table));
				$results = $this->object->callHookArguments('schema_update_alter', [
					$this, $actual_table, $sql_results,
				], $sql_results);
				if (is_array($results)) {
					$sql_results = $results;
				}
			} catch (Database_Exception_Table_NotFound) {
				$sql_results = array_merge($sql_results, $table->sqlCreate());
			}
		}
		return $sql_results;
	}

	/**
	 * Given a database table definition, synchronize it with given definition
	 *
	 * @param Database $db
	 * @param string $create_sql
	 * @param boolean $change_permanently
	 * @return array of sql commands
	 */
	public static function tableSynchronize(Database $db, $create_sql, $change_permanently = true) {
		$table = $db->parseCreateTable($create_sql, __METHOD__);
		return self::synchronize($db, $table, $change_permanently);
	}

	/**
	 *
	 * @param Database $db
	 * @param Database_Table $table
	 * @param boolean $change_permanently
	 * @return string[]
	 */
	public static function synchronize(Database $db, Database_Table $table, bool $change_permanently = true): array {
		$name = $table->name();

		try {
			$old_table = $db->databaseTable($name);
			return self::update($db, $old_table, $table, $change_permanently);
		} catch (Database_Exception_Table_NotFound) {
			return $table->sqlCreate();
		}
	}

	/**
	 * The money
	 *
	 * @param Database $db
	 * @param Database_Table $db_table_old
	 * @param Database_Table $db_table_new
	 * @param boolean $change_permanently
	 *            You may get some compatibility improvements by using this.
	 * @return array
	 */
	public static function update(Database $db, Database_Table $db_table_old, Database_Table $db_table_new, bool $change_permanently = false): array {
		$logger = $db->application->logger;

		$generator = $db->sql();

		if (self::$debug) {
			$logger->debug('ORM_Schema::debug is enabled');
		}
		$table = $db_table_old->name();
		if ($db_table_new->isSimilar($db_table_old, self::$debug)) {
			if (self::$debug) {
				$logger->debug("Tables are similar: \"{table}\":\nDatabase: \n{dbOld}\nCode:\n{dbNew}", [
					'table' => $table, 'dbOld' => Text::indent($db_table_old->source()),
					'dbNew' => Text::indent($db_table_new->source()),
				]);
			}
			return [];
		}

		if (self::$debug) {
			$logger->debug("ORM_Schema::update: \"{table}\" tables differ:\nDatabase: \n{dbOld}\nCode:\n{dbNew}", [
				'table' => $table, 'dbOld' => Text::indent($db_table_old->source()),
				'dbNew' => Text::indent($db_table_new->source()),
			]);
		}

		$drops = [];
		$changes = $db_table_new->sql_alter($db_table_old);
		$adds = [];

		$columnsOld = $db_table_old->columns();
		$columnsNew = $db_table_new->columns();

		/* @var $dbColOld Database_Column */
		/* @var $dbColNew Database_Column */

		$columns = array_unique(array_merge(array_keys($columnsNew), array_keys($columnsOld)));
		$ignoreColumns = [];
		$ignoreIndexes = [];

		/*
		 * First do changed names, then everything else
		 */
		foreach ($columns as $column) {
			if (isset($ignoreColumns[$column])) {
				continue;
			}
			$dbColOld = $columnsOld[$column] ?? null;
			$dbColNew = $columnsNew[$column] ?? null;
			if (!$dbColOld && $dbColNew) {
				$previous_name = $dbColNew->previousName();
				if (isset($columnsOld[$previous_name])) {
					$changes[$column] = $generator->alter_table_change_column($db_table_new, $columnsOld[$previous_name], $dbColNew);
					$ignoreColumns[$previous_name] = true;
					$ignoreColumns[$column] = true;
				}
			} elseif ($dbColOld && $dbColNew && !$dbColOld->isSimilar($dbColNew, self::$debug)) {
				$previous_name = $dbColNew->previousName();
				if (isset($columns[$previous_name])) {
					if ($previous_name) {
						// New column replaces an old
						$changes[$previous_name] = $generator->alter_table_column_drop($db_table_new, $columnsOld[$column]);
						$dbColOld->setName($previous_name);
						$changes[$column] = $generator->alter_table_change_column($db_table_new, $dbColOld, $dbColNew);
						$ignoreColumns[$previous_name] = true;
						$ignoreColumns[$column] = true;
					}
				}
			}
		}

		$last_column = false;
		foreach ($columns as $column) {
			if (isset($ignoreColumns[$column])) {
				continue;
			}
			$dbColOld = $columnsOld[$column] ?? null;
			$dbColNew = $columnsNew[$column] ?? null;
			// Pass a tip in the target column for alter_table_column_add and databases that support it
			if ($dbColNew) {
				$dbColNew->setOption('after_column', $last_column ?: null);
			}
			if ($dbColOld && $dbColNew) {
				if (!$dbColOld->isSimilar($dbColNew, self::$debug)) {
					$sql = $generator->alter_table_change_column($db_table_new, $dbColOld, $dbColNew);
					if ($sql) {
						$changes[$column] = $sql;
					}
					// TODO MySQL specific stuff should be factored out
					if ($dbColNew->isIncrement() && $dbColNew->isIndex(Database_Index::TYPE_PRIMARY)) {
						$name = $db_table_new->primary()->name();
						$ignoreIndexes[$name] = true;
					}
					$drops[$column] = $generator->alter_table_column_drop($db_table_new, $dbColOld);
					$adds[$column] = $generator->alter_table_column_add($db_table_new, $dbColNew);
				}
			} elseif ($dbColOld) {
				/* Handle ALTER TABLE DROP COLUMN */
				$drops[$column] = $generator->alter_table_column_drop($db_table_new, $dbColOld);
				if ($db_table_old->hasOption('remove_sql')) {
					$rm_cols = $db_table_old->option('remove_sql');
					if (array_key_exists($column, $rm_cols)) {
						$drops["-$column"] = $rm_cols[$column];
					}
				}
			} elseif ($dbColNew) {
				/* Handle ALTER TABLE ADD COLUMN */
				$adds[$column] = $generator->alter_table_column_add($db_table_new, $dbColNew);
				if ($dbColNew->hasOption('add_sql')) {
					$adds["+$column"] = $dbColNew->option('add_sql');
				}
			}
			$last_column = $column;
		}

		$indexes_old = $db_table_old->indexes();
		$indexes_new = $db_table_new->indexes();

		/* @var $indexOld Database_Index */
		/* @var $indexNew Database_Index */
		foreach ($indexes_old as $index_old) {
			$index_name = $index_old->name();
			if (isset($ignoreIndexes[$index_name])) {
				continue;
			}
			if (array_key_exists($index_name, $indexes_new)) {
				$index_new = $indexes_new[$index_name];
				if (!$index_old->isSimilar($index_new, self::$debug)) {
					$changes = array_merge(['index_' . $index_name => $index_old->sql_index_drop(), ], $changes);
					$adds['index_' . $index_name] = $index_new->sql_index_add();
				}
			} else {
				$changes = array_merge(['index_' . $index_name => $index_old->sql_index_drop(), ], $changes);
			}
		}
		foreach ($indexes_new as $index_new) {
			$index_name = $index_new->name();
			if (isset($ignoreIndexes[$index_name])) {
				continue;
			}
			if (!array_key_exists($index_name, $indexes_old)) {
				$adds['index_' . $index_name] = $index_new->sql_index_add();
			}
		}

		/*
		 * Now, do it!
		 * Change tables we need. If any step fails, then do a drop/add below (this is added above, redundantly)
		 */
		$sql_list = [];
		foreach ($changes as $column => $sql) {
			$sql_list = array_merge($sql_list, toList($sql));

			try {
				if ($change_permanently) {
					$result = $db->query($sql);
				} else {
					$result = true;
				}
			} catch (Database_Exception $e) {
				$result = false;
			}
			if (is_array($result)) {
				$success = !in_array(false, $result);
			} else {
				$success = $result;
			}
			if ($success) {
				unset($adds[$column]);
				unset($drops[$column]);
			}
		}

		/*
		 * Drop, then add to handle column names which may be identical, but types are different
		 * Data loss will occur here!
		 */
		foreach ($drops as $sql) {
			$sql_list = array_merge($sql_list, toList($sql));

			try {
				if ($change_permanently) {
					$result = $db->query($sql);
				} else {
					$result = true;
				}
			} catch (Database_Exception $e) {
				$result = false;
			}
		}
		foreach ($adds as $sql) {
			$sql_list = array_merge($sql_list, toList($sql));

			try {
				if ($change_permanently) {
					$result = $db->query($sql);
				} else {
					$result = true;
				}
			} catch (Database_Exception $e) {
				$e = intval($e);
				$result = false;
			}
		}
		$sql_list = $generator->callHookArguments('update_alter', [$sql_list, ], $sql_list);
		return $sql_list;
	}
}
