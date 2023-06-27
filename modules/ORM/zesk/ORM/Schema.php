<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\ORM;

use RuntimeException;
use Throwable;
use zesk\Application;
use zesk\ArrayTools;
use zesk\Database\Base;
use zesk\Database\Column;
use zesk\Database\Exception\Duplicate;
use zesk\Database\Exception\NoResults;
use zesk\Database\Exception\SchemaException;
use zesk\Database\Exception\TableNotFound;
use zesk\Database\Index;
use zesk\Database\Table;
use zesk\Exception\ClassNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\Semantics;
use zesk\Exception\SyntaxException;
use zesk\Hookable;
use zesk\ORM\Exception\ORMNotFound;
use zesk\PHP;
use zesk\Text;
use zesk\Types;

abstract class Schema extends Hookable
{
	/**
	 * Boolean value
	 */
	public const OPTION_DEBUG = 'debug';

	/**
	 * IDs auto increment (sometimes) and are unique
	 *
	 * @var string
	 */
	public const TYPE_ID = Class_Base::TYPE_ID;

	/**
	 *
	 * Plain old text data in the database
	 * @var string
	 */
	public const TYPE_STRING = Class_Base::TYPE_STRING;

	/**
	 * Refers to a system object (usually by ID)
	 *
	 * @var string
	 */
	public const TYPE_OBJECT = Class_Base::TYPE_OBJECT;

	/**
	 * Upon initial save, set to current date
	 *
	 * @var string
	 */
	public const TYPE_CREATED = Class_Base::TYPE_CREATED;

	/**
	 * Upon all saves, updates to current date
	 *
	 * @var string
	 */
	public const TYPE_MODIFIED = Class_Base::TYPE_MODIFIED;

	/**
	 * String information called using serialize/unserialize
	 *
	 * @var string
	 */
	public const TYPE_SERIALIZE = Class_Base::TYPE_SERIALIZE;

	public const TYPE_INTEGER = Class_Base::TYPE_INTEGER;

	public const TYPE_DOUBLE = Class_Base::TYPE_DOUBLE;

	public const TYPE_BOOL = Class_Base::TYPE_BOOL;

	public const TYPE_TIMESTAMP = Class_Base::TYPE_TIMESTAMP;

	public const TYPE_HEX = Class_Base::TYPE_HEX;

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
	 * @var Base
	 */
	protected Base $db;

	/**
	 * Create a new database schema
	 *
	 * @param Class_Base $class_object
	 * @param ORMBase|null $object
	 * @param array $options
	 */
	public function __construct(Class_Base $class_object, ORMBase $object = null, array $options = [])
	{
		parent::__construct($class_object->application, $options);
		$this->class_object = $class_object;
		$this->object = $object;
		$this->db = $object ? $object->database() : $class_object->database();
	}

	/**
	 *
	 * @param Application $application
	 * @throws Semantics
	 */
	public static function hooks(Application $application): void
	{
		$application->hooks->add('configured', __CLASS__ . '::configured');
		$application->configuration->path(__CLASS__);
	}

	/**
	 *
	 * @return bool
	 */
	public function debug(): bool
	{
		return $this->optionBool(self::OPTION_DEBUG);
	}

	/**
	 * Set debug setting
	 *
	 * @param bool $set
	 * @return self
	 */
	public function setDebug(bool $set): self
	{
		$this->setOption(self::OPTION_DEBUG, $set);
		return $this;
	}

	/**
	 * @param Application $application
	 * @return bool
	 */
	public static function schemaDebugging(Application $application): bool
	{
		return $application->configuration->path(self::class)->getBool(self::OPTION_DEBUG);
	}

	/**
	 * @param Application $application
	 * @param bool $set
	 * @return void
	 */
	public static function setSchemaDebugging(Application $application, bool $set): void
	{
		$application->configuration->path(self::class)->set(self::OPTION_DEBUG, $set);
	}

	/**
	 * @return Base
	 */
	final public function database(): Base
	{
		return $this->db;
	}

	/**
	 * Values used to transform schema definition - include variables in your schema definitions from configuration
	 * settings.
	 *
	 * @return array
	 */
	protected function schemaVariables(): array
	{
		$map = $this->object ? $this->object->schema_map() : [];
		$map += $this->class_object->schemaMap();
		return $map;
	}

	/**
	 * Map using the schema variables
	 *
	 * @param string|array $mixed
	 * @return string|array
	 */
	final public function map(string|array $mixed): string|array
	{
		if (is_array($mixed)) {
			$mixed = ArrayTools::mapKeys($mixed, $this->schemaVariables());
		}
		return ArrayTools::map($mixed, $this->schemaVariables());
	}

	/**
	 *
	 * @return string
	 */
	final public function primaryTable(): string
	{
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
	 * @param Base $db
	 * @param array $columns
	 * @return string|bool
	 */
	private static function validate_index_column_specification(Base $db, array $columns): string|bool
	{
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
	 * Convert an array-based table schema to a Table object
	 *
	 * @param Base $db
	 * @param string $table_name
	 * @param array $table_schema
	 * @param string|null $context
	 * @return Table
	 * @throws SchemaException
	 * @throws Semantics
	 * @throws SyntaxException
	 * @throws NotFoundException
	 */
	public static function schema_to_database_table(Base $db, string $table_name, array $table_schema, string $context = null): Table
	{
		$logger = $db->application->logger;

		$table = new Table($db, $table_name, $table_schema['engine'] ?? '');
		if (array_key_exists('source', $table_schema)) {
			$table->setSource($table_schema['source']);
		}
		if (!array_key_exists('columns', $table_schema)) {
			throw new SyntaxException("No columns exist in table \"$table_name\" schema");
		}
		foreach ($table_schema['columns'] as $column_name => $column_spec) {
			if (!$db->validColumnName($column_name)) {
				$logger->error('Invalid index name in schema found in {table_name} in {context}: Invalid column name {index}', compact('context', 'table_name', 'column_name'));

				continue;
			}
			$table->columnAdd(new Column($table, $column_name, $column_spec));
		}
		foreach ([
			'indexes' => Index::TYPE_INDEX, 'unique keys' => Index::TYPE_UNIQUE,
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
					$index = new Index($table, $index, $index_type);
					$index->addColumns($columns);
				} catch (Semantics $e) {
					throw new SchemaException($db, 'index {index}', 'Duplicate index {index}', $__, 0, $e);
				}
			}
		}
		if (array_key_exists('primary key', $table_schema)) {
			throw new SyntaxException("Table definition for $table_name should contain key \"primary keys\" not \"primary key\"");
		}
		$primary_columns = $table_schema['primary keys'] ?? null;
		if (is_array($primary_columns) && count($primary_columns) > 0) {
			if (!($error = self::validate_index_column_specification($db, $primary_columns))) {
				$logger->error('Invalid primary index column spec {error} in schema found ' . 'in {table_name} in {context}: Invalid index column', [
					'error' => $error, 'context' => $context, 'table_name' => $table_name,
				]);
			} else {
				if (!$table->hasIndex(Index::NAME_PRIMARY)) {
					try {
						$index = new Index($table, Index::NAME_PRIMARY, Index::TYPE_PRIMARY);
						$index->addColumns($primary_columns);
					} catch (Semantics $e) {
						throw new RuntimeException('!hasIndex -> addIndex failed for ' . Index::NAME_PRIMARY, 0, $e);
					}
				} else {
					$index = $table->index(Index::NAME_PRIMARY);
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
	 * @return Table[]
	 * @throws SchemaException
	 * @throws Semantics
	 * @throws SyntaxException|NotFoundException
	 */
	public function tables(): array
	{
		$logger = $this->application->logger;
		$schema = $this->schema();
		$tables = [];
		$db = $this->database();
		foreach ($schema as $table_name => $table_schema) {
			if (is_numeric($table_name)) {
				throw new SyntaxException(get_class($this) . " should contain an array of table schemas\n" . var_export($table_schema, true));
			}
			if ($this->debug()) {
				$logger->debug('Schema: ' . $this->class_object->class . " \"$table_name\"");
			}
			$tables[$table_name] = $table = self::schema_to_database_table($db, $this->map($table_name), $table_schema);

			if (array_key_exists('on create', $table_schema)) {
				// Make this first class TODO KMD 2023
				$table->setOptionPath(['on', 'create'], $table_schema['on create']);
			}
		}
		return $tables;
	}

	/**
	 * Convert the whole thing into a string.
	 *
	 * @return string
	 * @see Options::__toString()
	 */
	public function __toString()
	{
		$result = [];

		try {
			foreach ($this->tables() as $table) {
				$result = array_merge($result, $table->sqlCreate());
			}
		} catch (Throwable $e) {
			PHP::log($e);
			return '';
		}
		return implode("\n", $result);
	}

	/**
	 * Return an array of SQL to update an object's schema to its database
	 * @param ORMBase $object
	 * @return array
	 * @throws ClassNotFound
	 * @throws NotFoundException
	 * @throws ORMNotFound
	 * @throws ParameterException
	 * @throws SchemaException
	 * @throws Semantics
	 * @throws SyntaxException
	 */
	public static function update_object(ORMBase $object): array
	{
		$logger = $object->application->logger;

		/* @var $db Base */
		$object_class = $object::class;
		$schema = $object->database_schema();
		if (!$schema instanceof Schema) {
			$logger->warning('{class} did not return a Schema ({type})', [
				'class' => $object_class, 'type' => Types::type($schema),
			]);
			return [];
		}
		return $schema->_update_object();
	}

	/**
	 * Internal function helper for update_object
	 *
	 * @return array
	 * @throws NotFoundException
	 * @throws Semantics
	 * @throws SyntaxException
	 * @throws SchemaException
	 * @see update_object
	 */
	protected function _update_object(): array
	{
		$db = $this->database();

		$tables = $this->tables();
		$sql_results = [];
		foreach ($tables as $table) {
			/* @var $table Table */
			try {
				$actual_table = $db->databaseTable($table->name());
				$sql_results = array_merge($sql_results, self::update($db, $actual_table, $table));
				$results = $this->object->callHookArguments('schema_update_alter', [
					$this, $actual_table, $sql_results,
				], $sql_results);
				if (is_array($results)) {
					$sql_results = $results;
				}
			} catch (TableNotFound) {
				$sql_results = array_merge($sql_results, $table->sqlCreate());
			}
		}
		return $sql_results;
	}

	/**
	 * Given a database table definition, synchronize it with given definition
	 *
	 * @param Base $db
	 * @param string $create_sql
	 * @param boolean $change_permanently
	 * @return array of sql commands
	 * @throws NotFoundException
	 * @throws ClassNotFound
	 * @throws ParameterException
	 */
	public static function tableSynchronize(Base $db, string $create_sql, bool $change_permanently = true): array
	{
		$table = $db->parseCreateTable($create_sql, __METHOD__);
		return self::synchronize($db, $table, $change_permanently);
	}

	/**
	 *
	 * @param Base $db
	 * @param Table $table
	 * @param boolean $change_permanently
	 * @return string[]
	 */
	public static function synchronize(Base $db, Table $table, bool $change_permanently = true): array
	{
		$name = $table->name();

		try {
			$old_table = $db->databaseTable($name);
			return self::update($db, $old_table, $table, $change_permanently);
		} catch (TableNotFound) {
			return $table->sqlCreate();
		}
	}

	/**
	 * The money
	 *
	 * @param Base $db
	 * @param Table $db_table_old
	 * @param Table $db_table_new
	 * @param boolean $change_permanently
	 * @return array
	 */
	public static function update(Base $db, Table $db_table_old, Table $db_table_new, bool $change_permanently = false): array
	{
		$app = $db->application;
		$logger = $app->logger;
		$debug = self::schemaDebugging($app);

		$generator = $db->sqlDialect();

		if ($debug) {
			$logger->debug('Schema::debug is enabled');
		}
		$table = $db_table_old->name();
		if ($db_table_new->isSimilar($db_table_old, $debug)) {
			if ($debug) {
				$logger->debug("Tables are similar: \"{table}\":\nDatabase: \n{dbOld}\nCode:\n{dbNew}", [
					'table' => $table, 'dbOld' => Text::indent($db_table_old->source()),
					'dbNew' => Text::indent($db_table_new->source()),
				]);
			}
			return [];
		}

		if ($debug) {
			$logger->debug("Schema::update: \"{table}\" tables differ:\nDatabase: \n{dbOld}\nCode:\n{dbNew}", [
				'table' => $table, 'dbOld' => Text::indent($db_table_old->source()),
				'dbNew' => Text::indent($db_table_new->source()),
			]);
		}

		$drops = [];
		$changes = $db_table_new->sql_alter($db_table_old);
		$adds = [];

		$columnsOld = $db_table_old->columns();
		$columnsNew = $db_table_new->columns();

		/* @var $dbColOld Column */
		/* @var $dbColNew Column */

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
					$changes[$column] = $generator->alterTableChangeColumn($db_table_new, $columnsOld[$previous_name], $dbColNew);
					$ignoreColumns[$previous_name] = true;
					$ignoreColumns[$column] = true;
				}
			} elseif ($dbColOld && $dbColNew && !$dbColOld->isSimilar($dbColNew, $debug)) {
				$previous_name = $dbColNew->previousName();
				if (isset($columns[$previous_name])) {
					if ($previous_name) {
						// New column replaces an old
						$changes[$previous_name] = $generator->alterTableColumnDrop($db_table_new, $columnsOld[$column]);
						$dbColOld->setName($previous_name);
						$changes[$column] = $generator->alterTableChangeColumn($db_table_new, $dbColOld, $dbColNew);
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
				if (!$dbColOld->isSimilar($dbColNew, $debug)) {
					$sql = $generator->alterTableChangeColumn($db_table_new, $dbColOld, $dbColNew);
					if ($sql) {
						$changes[$column] = $sql;
					}
					// TODO MySQL specific stuff should be factored out
					if ($dbColNew->isIncrement() && $dbColNew->isIndex(Index::TYPE_PRIMARY)) {
						$name = $db_table_new->primary()->name();
						$ignoreIndexes[$name] = true;
					}
					$drops[$column] = $generator->alterTableColumnDrop($db_table_new, $dbColOld);
					$adds[$column] = $generator->alterTableColumnAdd($db_table_new, $dbColNew);
				}
			} elseif ($dbColOld) {
				/* Handle ALTER TABLE DROP COLUMN */
				$drops[$column] = $generator->alterTableColumnDrop($db_table_new, $dbColOld);
				if ($db_table_old->hasOption('remove_sql')) {
					$rm_cols = $db_table_old->option('remove_sql');
					if (array_key_exists($column, $rm_cols)) {
						$drops["-$column"] = $rm_cols[$column];
					}
				}
			} elseif ($dbColNew) {
				/* Handle ALTER TABLE ADD COLUMN */
				$adds[$column] = $generator->alterTableColumnAdd($db_table_new, $dbColNew);
				if ($dbColNew->hasOption('add_sql')) {
					$adds["+$column"] = $dbColNew->option('add_sql');
				}
			}
			$last_column = $column;
		}

		$indexes_old = $db_table_old->indexes();
		$indexes_new = $db_table_new->indexes();

		/* @var $index_old Index */
		/* @var $index_new Index */
		foreach ($indexes_old as $index_old) {
			$index_name = $index_old->name();
			if (isset($ignoreIndexes[$index_name])) {
				continue;
			}
			if (array_key_exists($index_name, $indexes_new)) {
				$index_new = $indexes_new[$index_name];
				if (!$index_old->isSimilar($index_new, $debug)) {
					$changes = array_merge(['index_' . $index_name => $index_old->sqlIndexDrop(), ], $changes);
					$adds['index_' . $index_name] = $index_new->sqlIndexAdd();
				}
			} else {
				$changes = array_merge(['index_' . $index_name => $index_old->sqlIndexDrop(), ], $changes);
			}
		}
		foreach ($indexes_new as $index_new) {
			$index_name = $index_new->name();
			if (isset($ignoreIndexes[$index_name])) {
				continue;
			}
			if (!array_key_exists($index_name, $indexes_old)) {
				$adds['index_' . $index_name] = $index_new->sqlIndexAdd();
			}
		}

		$log = [];
		$failures = [];
		/*
		 * Do it.
		 *
		 * Change tables we need. If any step fails, then do a drop/add below (this is added above, redundantly)
		 */
		$changes = array_map(Types::toList(...), $changes);
		$changes = $generator->callHookArguments('update_alter', [$changes, ], $changes);
		if ($change_permanently) {
			foreach ($changes as $column => $sqlList) {
				$changeOk = true;
				foreach ($sqlList as $sql) {
					try {
						$result = $db->query($sql);
						$log[] = [$sqlList, $result];
					} catch (Duplicate|TableNotFound|NoResults $e) {
						$changeOk = false;
						$log[] = [$sql, $e];
						$failures[] = [$sql, $e];
					}
				}
				if ($changeOk) {
					// Change succeeded - skip drop/add below.
					unset($drops[$column]);
					unset($adds[$column]);
				}
			}
		}

		/*
		 * Drop, then add to handle column names which may be identical, but types are different
		 * Data loss will occur here!
		 */
		$add_drop_sql = [];
		foreach (array_merge($drops, $adds) as $sql) {
			$add_drop_sql = array_merge($add_drop_sql, Types::toList($sql));
		}
		$add_drop_sql = $generator->callHookArguments('update_alter', [$add_drop_sql, ], $add_drop_sql);
		if ($change_permanently) {
			foreach ($add_drop_sql as $sql) {
				try {
					$result = $db->query($sql);
					$log[] = [$sql, $result];
				} catch (Duplicate|TableNotFound|NoResults $e) {
					$log[] = [$sql, $e];
					$failures[] = [$sql, $e];
				}
			}
		}
		if (count($failures) > 0) {
			$generator->callHookArguments('failures', [$failures, $log]);
		}
		$change_sql = [];
		foreach ($changes as $sqlList) {
			$change_sql = array_merge($change_sql, $sqlList);
		}
		return array_merge($change_sql, $add_drop_sql);
	}
}
