<?php

/**
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */
namespace zesk;

/**
 *
 * @package zesk
 * @subpackage system
 */
class Database_Table extends Hookable {
	/**
	 *
	 * @var Database
	 */
	private $database = null;

	/**
	 *
	 * @var string
	 */
	private $name = '';

	/**
	 *
	 * @var string
	 */
	private $type = null;

	/**
	 *
	 * @var array
	 */
	public $columns = array();

	/**
	 *
	 * @var Database_Index
	 */
	private $primary = null;

	/**
	 *
	 * @var array
	 */
	public $indexes = array();

	/**
	 *
	 * @var array
	 */
	public $on = array();

	/**
	 * @var string
	 */
	protected $source = null;

	/**
	 * Create a table
	 *
	 * @param Database $db
	 * @param unknown $table_name
	 * @param string $type
	 */
	public function __construct(Database $db, $table_name, $type = null, array $options = array()) {
		parent::__construct($db->application, $options);
		$this->database = $db;
		$this->name = $table_name;
		$this->type = $type;
	}

	/**
	 *
	 */
	public function __clone() {
		foreach ($this->columns as $name => $column) {
			/* $var $new_column Database_Column */
			$this->columns[$name] = $new_column = clone $column;
			$new_column->table($this);
		}
		foreach ($this->indexes as $name => $index) {
			$this->indexes[$name] = $new_index = clone $index;
			/* $var $new_index Database_Index */
			if ($new_index->is_primary()) {
				$this->primary = $new_index;
			}
		}
	}

	/**
	 * Returns primary index
	 *
	 * @return Database_Index
	 */
	public function primary() {
		return $this->primary;
	}

	/**
	 * Destroy table
	 */
	public function __destruct() {
		unset($this->indexes);
		if (isset($this->columns)) {
			foreach ($this->columns as $k => $col) {
				unset($this->columns[$k]);
			}
		}
		unset($this->primary);
		unset($this->columns);
		unset($this->database);
	}

	public function source($set = null, $append = false) {
		if ($set === null) {
			return $this->source;
		}
		$this->source = $append ? ($this->source ? $this->source . ";\n" : "") . $set : $set;
		return $this;
	}

	/**
	 * Database object
	 *
	 * @return Database
	 */
	public function database() {
		return $this->database;
	}

	/**
	 * Has column
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function has_column($name) {
		return array_key_exists($name, $this->columns);
	}

	/**
	 * Table type
	 *
	 * @return string
	 */
	public function type() {
		if (empty($this->type)) {
			return $this->database->default_engine();
		}
		return $this->type;
	}

	/**
	 * Default index structure for table (e.g.
	 * BTREE, etc.)
	 */
	public function default_index_structure() {
		return $this->database->default_index_structure($this->type);
	}

	/**
	 * Table name
	 *
	 * @return string
	 */
	public function name($set = null) {
		if ($set === null) {
			return $this->name;
		}
		$this->name = $set;
		return $this;
	}

	/**
	 * Array of Database_Column
	 *
	 * @return Database_Column[]
	 */
	public function columns() {
		return $this->columns;
	}

	/**
	 * Return array of column names
	 *
	 * @return string[]
	 */
	public function column_names() {
		$result = array();
		foreach ($this->columns as $col_object) {
			/* @var $col_object Database_Column */
			$result[] = $col_object->name();
		}
		return $result;
	}

	/**
	 * Retrieve the column from the table
	 *
	 * @param string $name
	 * @return Database_Column
	 */
	public function column($name) {
		return avalue($this->columns, $name, null);
	}

	/**
	 * Retieve the previous column definition for a column
	 *
	 * @param string $name
	 * @return Database_Column null
	 */
	public function previous_column($name) {
		foreach ($this->columns as $name => $column) {
			$previous_name = $column->previous_name();
			if (!$previous_name) {
				continue;
			}
			if (strcasecmp($previous_name, $name) === 0) {
				return $column;
			}
		}
		return null;
	}

	/**
	 * Index exists in table?
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function has_index($name) {
		$name = strtolower($name);
		return array_key_exists($name, $this->indexes);
	}

	/**
	 * Retrieve the index for the table
	 *
	 * @param unknown $name
	 * @return Database_Index
	 */
	public function index($name = null) {
		if ($name === null) {
			return $this->indexes();
		}
		$name = strtolower($name);
		return avalue($this->indexes, $name);
	}

	/**
	 *
	 */
	public function indexes() {
		if (is_array($this->indexes)) {
			return $this->indexes;
		}
		$indexes = array();
		foreach ($this->columns as $col) {
			$dbColName = $col->name();
			$indexes_types = $col->indexes_types();
			foreach ($indexes_types as $name => $type) {
				$lowname = strtolower($name);
				if (!isset($indexes[$lowname])) {
					$indexes[$lowname] = new Database_Index($this, $this->name, $name, $type);
				}
				$index = $indexes[$lowname];
				$index->column_add($dbColName, $col->option($type . "_size"));
			}
		}
		$this->indexes = $indexes;
		return $this->indexes;
	}

	/**
	 *
	 * @param Database_Index $index
	 */
	public function index_add(Database_Index $index) {
		$this->indexes[strtolower($index->name())] = $index;
		if ($index->type() === Database_Index::Primary) {
			if ($this->primary) {
				foreach ($this->primary->columns() as $col) {
					$this->column($col)->primary_key(false);
				}
			}
			$this->primary = $index;
			foreach ($index->columns() as $col) {
				$this->column($col)->primary_key(true);
			}
		}
	}

	/**
	 *
	 * @param array $indexes
	 */
	public function set_indexes(array $indexes) {
		foreach ($indexes as $v) {
			$this->index_add($v);
		}
	}

	/**
	 *
	 * @param Database_Column $dbCol
	 * @throws Exception_Semantics
	 * @return Database_Table
	 */
	public function column_add(Database_Column $dbCol) {
		$column = $dbCol->name();
		if ($column === null) {
			backtrace();
		}
		if (array_key_exists($column, $this->columns)) {
			throw new Exception_Semantics("Database_Table::column_add({column}) already exists in {table}", array(
				"column" => $column,
				"table" => $this->name,
			));
		}
		$this->call_hook("column_add", $dbCol);

		// 		if (!$dbCol->has_sql_type() && !$this->database->data_type()->type_set_sql_type($dbCol)) {
		// 			throw new Exception_Semantics("Database_Table::column_add($column): Can not set SQLType");
		// 		}
		if (!$dbCol->has_sql_type()) {
			throw new Exception_Semantics("{method}: No SQL type for column {column} in table {table}\noptions: {options}", array(
				"method" => __METHOD__,
				"options" => json_encode($dbCol->option()),
				"column" => $column,
				"table" => $dbCol->table()->name(),
			));
		}
		$after_column = $dbCol->option("after_column");
		if ($after_column) {
			$this->columns = ArrayTools::insert($this->columns, $after_column, array(
				$column => $dbCol,
			));
		} else {
			$this->columns[$column] = $dbCol;
		}
		if ($dbCol->primary_key()) {
			if ($this->primary) {
				$this->primary->column_add($column);
			} else {
				$this->primary = new Database_Index($this, '', array(
					$column => true,
				), Database_Index::Primary);
			}
		}
		return $this;
	}

	/**
	 *
	 * @param unknown $mixed
	 */
	public function column_remove($mixed) {
		if ($mixed instanceof Database_Column) {
			return $this->column_remove($mixed->name());
		}
	}

	/**
	 * Return statements to alter a table to a new setup
	 *
	 * @param Database_Table $old_table
	 * @return multitype:NULL
	 */
	public function sql_alter(Database_Table $old_table) {
		$result = array();
		$oldTableType = $old_table->type();
		$newTableType = $this->type();
		$tableName = $this->Name();

		$this->application->logger->debug("Table sql_alter {old} {new}", array(
			"old" => $oldTableType,
			"new" => $newTableType,
		));
		if (!$this->table_attributes_is_similar($old_table)) {
			$result[] = $this->database->sql()->alter_table_attributes($this, $this->option());
		}
		return $result;
	}

	private function table_attributes_is_similar(Database_Table $that, $debug = false) {
		$logger = $this->application->logger;
		$defaults = $this->database->table_attributes();
		$this_attributes = $this->option($defaults);
		$that_attributes = $that->option($defaults);
		if ($this_attributes !== $that_attributes) {
			if ($debug) {
				$logger->debug("Database_Table::is_similar({this_name}): Mismatched attributes: {this} != {that}", array(
					"this" => $this_attributes,
					"that" => $that_attributes,
					"this_name" => $this->name,
					"that_name" => $that->name,
				));
			}
			return false;
		}
		return true;
	}

	/**
	 *
	 * @param Database_Table $that
	 * @param string $debug
	 * @return boolean
	 */
	public function is_similar(Database_Table $that, $debug = false) {
		$logger = $this->application->logger;
		if (!$this->table_attributes_is_similar($that, $debug)) {
			return false;
		}
		if (($this_count = count($this->columns())) !== ($that_count = count($that->columns()))) {
			if ($debug) {
				$logger->debug("Database_Table::is_similar($this->name): Column Counts: $this_count != $that_count");
			}
			return false;
		}
		if (($this_count = count($this->indexes())) !== ($that_count = count($that->indexes()))) {
			if ($debug) {
				$logger->debug("Database_Table::is_similar($this->name): Index Counts: $this_count != $that_count");
			}
			return false;
		}

		/*
		 * Columns
		 */
		$thisColumns = $this->columns();
		foreach ($thisColumns as $k => $thisCol) {
			/* @var $thisCol Database_Column */
			/* @var $thatCol Database_Column */
			$thatCol = $that->column($k);
			if (!$thatCol) {
				if ($debug) {
					$logger->debug("Database_Table::is_similar($this->name): No that column $k");
				}
				return false;
			}
			if (!$thisCol->is_similar($this->database, $thatCol, $debug)) {
				if ($debug) {
					$logger->debug("Database_Table::is_similar($this->name): Dissimilar column $k");
				}
				return false;
			}
		}

		/*
		 * Indexes
		 */
		$thatIndexes = $that->indexes();
		$this_indexes = $this->indexes();
		foreach ($this_indexes as $k => $this_index) {
			if (!isset($thatIndexes[$k])) {
				if ($debug) {
					$logger->debug("Database_Table::is_similar($this->name): No that index $k");
				}
				return false;
			}
			if (!$this_index->is_similar($thatIndexes[$k])) {
				if ($debug) {
					$logger->debug("Database_Table::is_similar($this->name): Dissimilar index $k");
				}
				return false;
			}
		}

		$extras = $this->database->table_attributes();
		foreach ($extras as $extra => $default) {
			$this_value = $this->option($extra, $default);
			$that_value = $that->option($extra, $default);
			if ($this_value !== $that_value) {
				if ($debug) {
					$logger->debug("Database_Table::is_similar($this->name): $extra: $this_value !== $that_value");
				}
				return false;
			}
		}

		return true;
	}

	/**
	 *
	 */
	public function create_sql() {
		$result = $this->database->sql()->create_table($this);
		$result[] = '-- database type ' . $this->database->type();
		$result[] = '-- sql ' . get_class($this->database->sql());
		$result = array_merge($result, $this->on_action("create"));
		return $result;
	}

	/**
	 *
	 * @param unknown $action
	 * @param array $sqls
	 * @throws Exception_Semantics
	 * @return NULL[]|mixed|array
	 */
	public function on_action($action, array $sqls = null) {
		if (is_array($action)) {
			assert($sqls === null);
			$result = array();
			foreach ($action as $_ => $sqls) {
				$result[$_] = $this->on($_, $sqls);
			}
			return $result;
		}
		if (!in_array($action, to_list('create', 'add column', 'drop column', 'add index', 'drop index', 'add primary key', 'drop primary key'))) {
			throw new Exception_Semantics("Invalid action $action passed to Database_Table::on for table $this->name");
		}
		if ($sqls === null) {
			return avalue($this->on, $action, array());
		}
		assert(is_array($sqls));
		if (!array_key_exists($action, $this->on)) {
			$this->on[$action] = array();
		}
		return $this->on[$action] = array_merge($this->on[$action], $sqls);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see Options::__toString()
	 */
	public function __toString() {
		return $this->name();
	}

	/**
	 *
	 * @return string
	 */
	public function _debug_dump() {
		$dump = get_object_vars($this);
		$dump['database'] = $this->database ? $this->database->code_name() : null;
		$dump['primary'] = $this->primary ? $this->primary->name() : null;
		$result = "Object " . __CLASS__ . " (\n" . Text::indent(_dump($dump, true)) . "\n)";
		return $result;
	}
}
