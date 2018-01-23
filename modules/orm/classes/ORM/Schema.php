<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Database/Schema.php $
 * @package zesk
 * @subpackage database
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */
namespace zesk;

abstract class ORM_Schema extends Hookable {
	const type_id = Class_ORM::type_id;

	/**
	 * Plain old text data in the database
	 *
	 * @var string
	 */
	const type_text = Class_ORM::type_text;
	/**
	 *
	 * Plain old text data in the database
	 * @var string
	 */
	const type_string = Class_ORM::type_string;

	/**
	 *
	 * Binary data in the database
	 * @var string
	 */
	const type_binary = Class_ORM::type_binary;

	/**
	 * This column serves as text data for polymorphic objects
	 *
	 * On store, saves current object class polymorphic name
	 * On loading, creates into new object
	 *
	 * @var string
	 */
	const type_polymorph = Class_ORM::type_polymorph;

	/**
	 * Refers to a system object (usually by ID)
	 *
	 * @var string
	 */
	const type_object = Class_ORM::type_object;

	/**
	 * Upon initial save, set to current date
	 *
	 * @var string
	 */
	const type_created = Class_ORM::type_created;

	/**
	 * Upon all saves, updates to current date
	 *
	 * @var string
	 */
	const type_modified = Class_ORM::type_modified;

	/**
	 * String information called using serialize/unserialize
	 *
	 * @var string
	 */
	const type_serialize = Class_ORM::type_serialize;
	const type_integer = Class_ORM::type_integer;
	const type_real = Class_ORM::type_real;
	const type_float = Class_ORM::type_float;
	const type_double = Class_ORM::type_double;
	const type_boolean = Class_ORM::type_boolean;
	const type_timestamp = Class_ORM::type_timestamp;
	const type_date = Class_ORM::type_date;
	const type_time = Class_ORM::type_time;
	const type_ip = Class_ORM::type_ip;
	const type_ip4 = Class_ORM::type_ip4;
	const type_crc32 = Class_ORM::type_crc32;
	const type_hex32 = Class_ORM::type_hex32;
	const type_hex = Class_ORM::type_hex;

	/**
	 * Debugging information.
	 * Lots to find problems with implementation or your SQL code.
	 *
	 * @var boolean
	 */
	static $debug = false;

	/**
	 * Class object associated with this schema
	 *
	 * @var Class_ORM
	 */
	protected $class_object = null;

	/**
	 * ORM associated with this schema
	 *
	 * @var ORM
	 */
	protected $object = null;

	/**
	 *
	 * @var Database
	 */
	protected $db = null;

	/**
	 * Create a new database schema
	 *
	 * @param Class_ORM $class_object
	 * @param string $options
	 */
	function __construct(Class_ORM $class_object, ORM $object = null, array $options = array()) {
		parent::__construct($class_object->application, $options);
		$this->class_object = $class_object;
		$this->object = $object;
	}

	/**
	 *
	 * @param zesk\Kernel $application
	 */
	public static function hooks(Application $application) {
		$application->hooks->add("configured", __CLASS__ . "::configured");
		$application->configuration->path(__CLASS__);
	}

	/**
	 *
	 * @param zesk\Application $application
	 */
	public static function configured(Application $application) {
		if ($application->configuration->debug || $application->configuration->path_get(array(
			__CLASS__,
			"debug"
		))) {
			self::$debug = true;
		}
	}
	/**
	 *
	 * @return Database
	 */
	final function database() {
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
	 * @return multitype:string
	 */
	protected function schema_map() {
		$map = $this->object ? $this->object->schema_map() : array();
		$map += $this->class_object->schema_map();
		return $map;
	}

	/**
	 * Map using the schema map
	 *
	 * @param mixed $mixed
	 * @return mixed
	 */
	final function map($mixed) {
		if (is_array($mixed)) {
			$mixed = kmap($mixed, $this->schema_map());
		}
		return map($mixed, $this->schema_map());
	}

	/**
	 *
	 * @return string
	 */
	final function primary_table() {
		return $this->class_object->table();
	}

	/**
	 * Generate the array-syntax schema
	 *
	 * @return array
	 */
	abstract function schema();

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
			if (is_numeric($k) && $db->valid_column_name($v)) {
				continue;
			}
			if ((is_bool($v) || (is_numeric($v) && $v > 0)) && $db->valid_column_name($k)) {
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
	 * @param string $table
	 *        	The table name
	 * @param array $table_schema
	 * @throws Database_Exception_Schema
	 * @return Database_Table
	 */
	public static function schema_to_database_table(Database $db, $table_name, array $table_schema, $context = null) {
		$logger = $db->application->logger;

		$table = new Database_Table($db, $table_name, avalue($table_schema, 'engine'));
		$table->source(avalue($table_schema, 'source'));
		if (!array_key_exists('columns', $table_schema)) {
			throw new Exception_Syntax("No columns exist in table \"$table_name\" schema");
		}
		foreach ($table_schema['columns'] as $column_name => $column_spec) {
			if (!$db->valid_column_name($column_name)) {
				$logger->error("Invalid index name in schema found in {table_name} in {context}: Invalid column name {index}", compact("context", "table_name", "column_name"));
				continue;
			}
			try {
				$table->column_add(new Database_Column($table, $column_name, $column_spec));
			} catch (\Exception $e) {
				//dump($table_schema['columns']);
				throw $e;
			}
		}
		foreach (avalue($table_schema, 'indexes', array()) as $index => $columns) {
			if (!$db->valid_index_name($index)) {
				$logger->error("Invalid index name in schema found in {table_name} in {context}: Invalid index name {index}", compact("context", "table_name", "index"));
				continue;
			}
			if (!($error = self::validate_index_column_specification($db, $columns))) {
				$logger->error("Invalid index column spec {error} in schema found in {table_name} in {context}: Invalid index name {index}", compact("error", "context", "table_name", "index"));
				continue;
			}
			$table->index_add(new Database_Index($table, $index, $columns, Database_Index::Index));
		}
		foreach (avalue($table_schema, 'unique keys', array()) as $index => $columns) {
			if (!$db->valid_index_name($index)) {
				$logger->error("Invalid index name in schema found in {table_name} in {context}: Invalid index name {index}", compact("context", "table_name", "index"));
				continue;
			}
			if (!($error = self::validate_index_column_specification($db, $columns))) {
				$logger->error("Invalid index column spec {error} in schema found in {table_name} in {context}: Invalid index name {index}", compact("error", "context", "table_name", "index"));
				continue;
			}
			$table->index_add(new Database_Index($table, $index, $columns, Database_Index::Unique));
		}
		if (array_key_exists('primary key', $table_schema)) {
			throw new Exception_Syntax("Table definition for $table_name should contain key \"primary keys\" not \"primary key\"");
		}
		$primary_columns = avalue($table_schema, 'primary keys', null);
		if (is_array($primary_columns) && count($primary_columns) > 0) {
			if (!($error = self::validate_index_column_specification($db, $primary_columns))) {
				$logger->error("Invalid primary index column spec {error} in schema found in {table_name} in {context}: Invalid index name {index}", compact("error", "context", "table_name", "index"));
			} else {
				$table->index_add(new Database_Index($table, "primary", $primary_columns, Database_Index::Primary));
			}
		}
		if (array_key_exists("on create", $table_schema)) {
			if (is_array($table_schema['on create'])) {
				$table->on_action("create", $table_schema['on create']);
			} else if (is_string($table_schema['on create'])) {
				$table->on_action("create", array(
					$table_schema['on create']
				));
			}
		}
		return $table;
	}

	/**
	 * Return an array of tables associated with this schema
	 *
	 * @return array of Database_Table
	 */
	function tables() {
		$logger = $this->application->logger;
		$schema = $this->schema();
		$tables = array();
		$db = $this->database();
		if (is_array($schema)) {
			foreach ($schema as $table_name => $table_schema) {
				if (is_numeric($table_name)) {
					throw new Exception_Syntax(get_class($this) . " should contain an array of table schemas\n" . var_export($table_schema));
				}
				if (self::$debug) {
					$logger->debug("ORM_Schema: " . $this->class_object->class . " \"$table_name\"");
				}
				/* @var $table Database_Table */
				try {
					$tables[$table_name] = $table = self::schema_to_database_table($db, $this->map($table_name), $table_schema);
				} catch (Exception $e) {
					global $zesk;
					$zesk->hooks->call("exception", $e);
					$logger->debug("Error with object " . $this->class_object->class);
					throw $e;
				}

				if (array_key_exists('on create', $table_schema)) {
					$table->set_option_path('on.create', $table_schema['on create']);
				}
			}
		}
		return $tables;
	}

	/**
	 * Conver the whole 'ting into a string.
	 *
	 * @return string
	 * @see Options::__toString()
	 */
	function __toString() {
		$tables = $this->tables();

		$result = array();
		foreach ($tables as $table) {
			/* @var $table Database_Table */
			$create_sql = $table->create_sql();
			if (!is_array($create_sql)) {
				$create_sql = array(
					$create_sql
				);
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
	static function debug($set = null) {
		if (is_bool($set)) {
			self::$debug = $set;
		}
		return self::$debug;
	}

	/**
	 * Return an array of SQL to update an object's schema to its database
	 *
	 * @param ORM $object
	 * @throws Database_Exception
	 * @return multitype:
	 */
	static function update_object(ORM $object) {
		$logger = $object->application->logger;

		/* @var $db Database */
		$db = $object->database();
		if (!$db) {
			throw new Database_Exception(__("Can not connect to {0}", $db->safeURL()));
		}
		$object_class = get_class($object);
		$schema = $object->database_schema();
		if (!$schema instanceof ORM_Schema) {
			$logger->warning("{class} did not return a ORM_Schema ({type})", array(
				"class" => $object_class,
				"type" => type($schema)
			));
			return array();
		}
		return $schema->_update_object();
	}

	/**
	 * Internal function helper for update_object
	 *
	 * @see update_object
	 * @return array
	 */
	protected function _update_object() {
		$db = $this->database();
		$tables = $this->tables();
		$sql_results = array();
		foreach ($tables as $table_name => $table) {
			/* @var $table Database_Table */
			try {
				$actual_table = $db->database_table($table->name());
				$sql_results = array_merge($sql_results, self::update($db, $actual_table, $table));
				$results = $this->object->call_hook_arguments("schema_update_alter", array(
					$this,
					$actual_table,
					$sql_results
				), $sql_results);
				if (is_array($results)) {
					$sql_results = $results;
				}
			} catch (Database_Exception_Table_NotFound $e) {
				$sql_results = array_merge($sql_results, $table->create_sql());
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
	static function table_synchronize(Database $db, $create_sql, $change_permanently = true) {
		$table = $db->parse_create_table($create_sql, __METHOD__);
		return self::synchronize($db, $table, $change_permanently);
	}

	/**
	 *
	 * @param Database $db
	 * @param Database_Table $table
	 * @param boolean $change_permanently
	 * @return string[]
	 */
	static function synchronize(Database $db, Database_Table $table, $change_permanently = true) {
		$name = $table->name();
		if (!$db->table_exists($name)) {
			$result = $table->create_sql();
			// Not sure what format this returns as - should figure it out and clean up this code below
			if (is_array($result)) {
				return $result;
			}
			if (is_string($result)) {
				return array(
					$result
				);
			}
			return $result;
		}
		$old_table = $db->database_table($name);
		return self::update($db, $old_table, $table, $change_permanently);
	}

	/**
	 * The money
	 *
	 * @param Database $db
	 * @param Database_Table $db_table_old
	 * @param Database_Table $db_table_new
	 * @param boolean $change_permanently
	 *        	You may get some compatibility improvements by using this.
	 * @return array
	 */
	static function update(Database $db, Database_Table $db_table_old, Database_Table $db_table_new, $change_permanently = false) {
		$logger = $db->application->logger;

		$generator = $db->sql();

		if (self::$debug) {
			$logger->debug("ORM_Schema::debug is enabled");
		}
		$table = $db_table_old->name();
		if ($db_table_new->is_similar($db_table_old, self::$debug)) {
			if (self::$debug) {
				$logger->debug("Tables are similar: \"{table}\":\nDatabase: \n{dbOld}\nCode:\n{dbNew}", array(
					'table' => $table,
					'dbOld' => Text::indent($db_table_old->source()),
					'dbNew' => Text::indent($db_table_new->source())
				));
			}
			return array();
		}

		if (self::$debug) {
			$logger->debug("ORM_Schema::update: \"{table}\" tables differ:\nDatabase: \n{dbOld}\nCode:\n{dbNew}", array(
				'table' => $table,
				'dbOld' => Text::indent($db_table_old->source()),
				'dbNew' => Text::indent($db_table_new->source())
			));
		}

		$drops = array();
		$changes = $db_table_new->sql_alter($db_table_old);
		$adds = array();
		$indexes_old = array();
		$indexes_new = array();

		$columnsOld = $db_table_old->columns();
		$columnsNew = $db_table_new->columns();

		/* @var $dbColOld Database_Column */
		/* @var $dbColNew Database_Column */

		$columns = array_unique(array_merge(array_keys($columnsNew), array_keys($columnsOld)));
		$ignoreColumns = array();
		$ignoreIndexes = array();

		/*
		 * First do changed names, then everything else
		 */
		foreach ($columns as $column) {
			if (isset($ignoreColumns[$column])) {
				continue;
			}
			$dbColOld = avalue($columnsOld, $column);
			$dbColNew = avalue($columnsNew, $column);
			if (!$dbColOld && $dbColNew) {
				$previous_name = $dbColNew->previous_name();
				if (isset($columnsOld[$previous_name])) {
					$changes[$column] = $generator->alter_table_change_column($db_table_new, $columnsOld[$previous_name], $dbColNew);
					$ignoreColumns[$previous_name] = true;
					$ignoreColumns[$column] = true;
				}
			} else if ($dbColOld && $dbColNew && !$dbColOld->is_similar($db, $dbColNew, self::$debug)) {
				$previous_name = $dbColNew->previous_name();
				if (isset($columns[$previous_name])) {
					if ($previous_name) {
						// New column replaces an old
						$changes[$previous_name] = $generator->alter_table_column_drop($db_table_new, $columnsOld[$column]);
						$dbColOld->name($previous_name);
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
			$dbColOld = avalue($columnsOld, $column);
			$dbColNew = avalue($columnsNew, $column);
			// Pass a tip in the target column for alter_table_column_add and databases that support it
			if ($dbColNew) {
				$dbColNew->set_option("after_column", $last_column ? $last_column : null);
			}
			if ($dbColOld && $dbColNew) {
				if (!$dbColOld->is_similar($db, $dbColNew, self::$debug)) {
					$sql = $generator->alter_table_change_column($db_table_new, $dbColOld, $dbColNew);
					if ($sql) {
						$changes[$column] = $sql;
					}
					// TODO MySQL specific stuff should be factored out
					if ($dbColNew->is_increment() && $dbColNew->is_index(Database_Index::Primary)) {
						$name = $db_table_new->primary()->name();
						$ignoreIndexes[$name] = true;
					}
					$drops[$column] = $generator->alter_table_column_drop($db_table_new, $dbColOld);
					$adds[$column] = $generator->alter_table_column_add($db_table_new, $dbColNew);
				}
			} else if ($dbColOld) {
				/* Handle ALTER TABLE DROP COLUMN */
				$drops[$column] = $generator->alter_table_column_drop($db_table_new, $dbColOld);
				if ($db_table_old->has_option("remove_sql")) {
					$rm_cols = $table->option('remove_sql');
					if (array_key_exists($column, $rm_cols)) {
						$drops["-$column"] = $rm_cols[$column];
					}
				}
			} else if ($dbColNew) {
				/* Handle ALTER TABLE ADD COLUMN */
				$adds[$column] = $generator->alter_table_column_add($db_table_new, $dbColNew);
				if ($dbColNew->has_option("add_sql")) {
					$adds["+$column"] = $dbColNew->option('add_sql');
				}
			}
			$last_column = $column;
		}

		$indexes_old = $db_table_old->indexes();
		$indexes_new = $db_table_new->indexes();

		/* @var $indexOld Database_Index */
		/* @var $indexNew Database_Index */
		foreach ($indexes_old as $index_name => $index_old) {
			if (isset($ignoreIndexes[$index_name])) {
				continue;
			}
			if (array_key_exists($index_name, $indexes_new)) {
				$index_new = $indexes_new[$index_name];
				if (!$index_old->is_similar($index_new, self::$debug)) {
					$changes = array_merge(array(
						"index_" . $index_name => $index_old->sql_index_drop()
					), $changes);
					$adds["index_" . $index_name] = $index_new->sql_index_add();
				}
			} else {
				$changes = array_merge(array(
					"index_" . $index_name => $index_old->sql_index_drop()
				), $changes);
			}
		}
		foreach ($indexes_new as $index_name => $index_new) {
			if (isset($ignoreIndexes[$index_name])) {
				continue;
			}
			if (!array_key_exists($index_name, $indexes_old)) {
				$adds["index_" . $index_name] = $index_new->sql_index_add();
			}
		}

		/*
		 * Now, do it!
		 * Change tables we need. If any step fails, then do a drop/add below (this is added above, redundantly)
		 */
		$success = true;
		$sql_list = array();
		foreach ($changes as $column => $sql) {
			$sql_list = array_merge($sql_list, to_list($sql));
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
			$sql_list = array_merge($sql_list, to_list($sql));
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
			$sql_list = array_merge($sql_list, to_list($sql));
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
		$sql_list = $generator->call_hook_arguments("update_alter", array(
			$sql_list
		), $sql_list);
		return $sql_list;
	}
}

