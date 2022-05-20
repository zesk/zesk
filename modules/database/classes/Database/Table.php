<?php
declare(strict_types=1);

/**
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2005, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */

namespace zesk;

use JetBrains\PhpStorm\Pure;

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
	private Database $database;

	/**
	 *
	 * @var string
	 */
	private string $name;

	/**
	 *
	 * @var string
	 */
	private string $type;

	/**
	 *
	 * @var array
	 */
	public array $columns = [];

	/**
	 *
	 * @var ?Database_Index
	 */
	private ?Database_Index $primary = null;

	/**
	 *
	 * @var array
	 */
	public array $indexes = [];

	/**
	 * Has $indexes been computed yet
	 *
	 * @var bool
	 */
	private bool $_indexes_collected = false;

	/**
	 *
	 * @var array
	 */
	public array $on = [];

	/**
	 * @var string
	 */
	protected string $source = '';

	/**
	 * Create a table
	 *
	 * @param Database $db
	 * @param string $table_name
	 * @param string $type
	 * @param array $options
	 */
	public function __construct(Database $db, string $table_name, string $type = '', array $options = []) {
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
	 * @return ?Database_Index
	 */
	public function primary(): ?Database_Index {
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

	/**
	 * @param ?string $set
	 * @param bool $append
	 * @return string
	 */
	public function source(string $set = null, bool $append = false): string {
		if ($set === null) {
			return $this->source;
		}
		$this->application->deprecated('setter');
		$this->setSource($set, $append);
		return $this->source;
	}

	/**
	 * @param string|null $set
	 * @param bool $append
	 * @return $this
	 */
	public function setSource(string $set = null, bool $append = false): self {
		$this->source = $append ? ($this->source ? $this->source . ";\n" : '') . $set : $set;
		return $this;
	}

	/**
	 * Database object
	 *
	 * @return Database
	 */
	public function database(): Database {
		return $this->database;
	}

	/**
	 * Has column
	 *
	 * @param string $name
	 * @return boolean
	 */
	public function hasColumn(string $name): bool {
		return array_key_exists($name, $this->columns);
	}

	/**
	 * Table type
	 *
	 * @return string
	 */
	public function type(): string {
		if (empty($this->type)) {
			return $this->database->default_engine();
		}
		return $this->type;
	}

	/**
	 * Default index structure for table (e.g.
	 * BTREE, etc.)
	 * @return string
	 * @deprecated 2022-01
	 */
	public function default_index_structure(): string {
		return $this->defaultIndexStructure();
	}

	/**
	 * Default index structure for table (e.g.
	 * BTREE, etc.)
	 * @return string
	 */
	public function defaultIndexStructure(): string {
		return $this->database->default_index_structure($this->type);
	}

	/**
	 * Table name
	 *
	 * @return string
	 */
	public function name($set = null): string {
		if ($set !== null) {
			$this->application->deprecated('name setter');
		}
		return $this->name;
	}

	/**
	 * @param string $set
	 * @return $this
	 */
	public function setName(string $set): self {
		// TODO Some text validation here?
		$this->name = $set;
		return $this;
	}

	/**
	 * Array of Database_Column
	 *
	 * @return array
	 */
	public function columns(): array {
		return $this->columns;
	}

	/**
	 * Return array of column names
	 *
	 * @return string[]
	 */
	public function columnNames(): array {
		$result = [];
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
	 * @throws Exception_Key
	 */
	public function column(string $name): Database_Column {
		if (array_key_exists($name, $this->columns)) {
			return $this->columns[$name];
		}

		throw new Exception_Key('No column {name} in {table}', ['name' => $name, 'table' => $this->name]);
	}

	/**
	 * Retieve the previous column definition for a column
	 *
	 * @param string $find_name
	 * @return ?Database_Column
	 */
	public function previousColumn(string $find_name): ?Database_Column {
		foreach ($this->columns as $column) {
			$previous_name = $column->previous_name();
			if (!$previous_name) {
				continue;
			}
			if (strcasecmp($previous_name, $find_name) === 0) {
				return $column;
			}
		}
		return null;
	}

	/**
	 * Index exists in table?
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasIndex(string $name): bool {
		foreach ($this->indexes() as $index) {
			if ($index->name() === $name) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @return array
	 * @throws Exception_NotFound
	 * @throws Exception_Semantics
	 */
	private function collectIndexes(): array {
		$indexes = [];
		foreach ($this->columns as $col) {
			/* @var $col Database_Column */
			$dbColName = $col->name();
			$indexes_types = $col->indexesTypes();
			foreach ($indexes_types as $name => $type) {
				if (!$this->hasIndex($name)) {
					$indexes[$name] = new Database_Index($this, $name, [], $type);
				}
				$indexes[$name]->addColumn($dbColName, $col->optionInt($type . '_size', Database_Index::SIZE_DEFAULT));
			}
		}
		return $indexes;
	}

	/**
	 * Retrieve the index for the table
	 *
	 * @param string $name
	 * @return Database_Index
	 * @throws Exception_NotFound
	 */
	public function index(string $name): Database_Index {
		$indexes = $this->indexes();
		if (!isset($indexes[$name])) {
			throw new Exception_NotFound('Index {name}', ['name' => $name]);
		}
		return $indexes[$name];
	}

	/**
	 *
	 */
	public function indexes(): array {
		if (!$this->_indexes_collected) {
			// TODO Prevent recursion - this occurs when these lines are switched - why? KMD 2022-01
			// Probably assumption wrong somewhere
			$this->_indexes_collected = true;
			$this->indexes = [];
			$indexes = $this->collectIndexes();
			assert($this->indexes === []);
			$this->indexes += $indexes;
		}
		$by_name = [];
		foreach ($this->indexes as $index) {
			$by_name[$index->name()] = $index;
		}
		return $by_name;
	}

	/**
	 * @param Database_Index $index
	 * @return $this
	 * @throws Exception_Key
	 */
	public function addIndex(Database_Index $index): self {
		$indexes = $this->indexes();
		$name = $index->name();
		if (isset($indexes[$name])) {
			throw new Exception_Key('Index {name} Already exists', ['name' => $name]);
		}
		if ($index->type() === Database_Index::TYPE_PRIMARY) {
			if ($this->primary) {
				$this->removePrimaryIndex();
			}
			assert($this->primary === null);
			$this->setPrimaryIndex($index);
		}
		$this->indexes[$name] = $index;

		return $this;
	}

	/**
	 * @param string $name
	 * @return Database_Index
	 * @throws Exception_NotFound
	 */
	public function removeIndex(string $name): Database_Index {
		$index = $this->index($name);
		if ($index->type() === Database_Index::TYPE_PRIMARY) {
			if ($this->primary) {
				assert($index === $this->primary);
				$this->removePrimaryIndex();
			}
			$this->setPrimaryIndex($index);
		}
		$name = $index->name();
		assert(array_key_exists($name, $this->indexes));
		unset($this->indexes[$name]);
		return $index;
	}

	/**
	 * @param Database_Index $index
	 * @return void
	 * @throws Exception_Key
	 */
	private function setPrimaryIndex(Database_Index $index): void {
		assert($this->primary === null);
		$this->primary = $index;
		foreach ($index->columns() as $col) {
			$this->column($col)->setPrimaryKey(true);
		}
	}

	/**
	 * @return void
	 * @throws Exception_Key
	 */
	private function removePrimaryIndex(): void {
		foreach ($this->primary->columns() as $col) {
			$this->column($col)->setPrimaryKey(false);
		}
		$this->primary = null;
	}

	/**
	 * @param Database_Column $dbCol
	 * @return $this
	 * @throws Exception_Semantics
	 */
	public function columnAdd(Database_Column $dbCol): self {
		$column = $dbCol->name();
		assert($column !== null);
		if (array_key_exists($column, $this->columns)) {
			throw new Exception_Semantics('Database_Table::column_add({column}) already exists in {table}', [
				'column' => $column,
				'table' => $this->name,
			]);
		}
		$this->call_hook('column_add', $dbCol);

		if (!$dbCol->hasSQLType()) {
			throw new Exception_Semantics("{method}: No SQL type for column {column} in table {table}\noptions: {options}", [
				'method' => __METHOD__,
				'options' => json_encode($dbCol->options()),
				'column' => $column,
				'table' => $dbCol->table()->name(),
			]);
		}
		$after_column = $dbCol->option('after_column');
		if ($after_column) {
			$this->columns = ArrayTools::insert($this->columns, $after_column, [$column => $dbCol,]);
		} else {
			$this->columns[$column] = $dbCol;
		}
		if ($dbCol->primaryKey()) {
			if (!$this->primary) {
				$this->primary = new Database_Index($this, Database_Index::NAME_PRIMARY, [], Database_Index::TYPE_PRIMARY);
			}
			$this->primary->addDatabaseColumn($dbCol);
		}
		return $this;
	}

	/**
	 * Return statements to alter a table to a new setup
	 *
	 * @param Database_Table $old_table
	 * @return array
	 */
	public function sql_alter(Database_Table $old_table) {
		$result = [];
		$oldTableType = $old_table->type();
		$newTableType = $this->type();
		$tableName = $this->Name();

		$this->application->logger->debug('Table sql_alter {old} {new}', [
			'old' => $oldTableType,
			'new' => $newTableType,
		]);
		if (!$this->table_attributes_is_similar($old_table)) {
			$result[] = $this->database->sql()->alter_table_attributes($this, $this->options());
		}
		return $result;
	}

	/**
	 * @param Database_Table $that
	 * @param bool $debug
	 * @return bool
	 */
	private function table_attributes_is_similar(Database_Table $that, bool $debug = false): bool {
		$logger = $this->application->logger;
		$defaults = $this->database->table_attributes();
		$this_attributes = $this->options($defaults);
		$that_attributes = $that->options($defaults);
		if ($this_attributes !== $that_attributes) {
			if ($debug) {
				$logger->debug('Database_Table::is_similar({this_name}): Mismatched attributes: {this} != {that}', [
					'this' => $this_attributes,
					'that' => $that_attributes,
					'this_name' => $this->name,
					'that_name' => $that->name,
				]);
			}
			return false;
		}
		return true;
	}

	/**
	 * @param Database_Table $that
	 * @param bool $debug
	 * @return bool
	 */
	public function is_similar(Database_Table $that, bool $debug = false): bool {
		return $this->isSimilar($that, $debug);
	}

	/**
	 *
	 * @param Database_Table $that
	 * @param bool $debug
	 * @return bool
	 */
	public function isSimilar(Database_Table $that, bool $debug = false): bool {
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
			if (!$thisCol->isSimilar($this->database, $thatCol, $debug)) {
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
	 * List of one or more statements to create a table
	 *
	 * @return array
	 */
	public function sqlCreate(): array {
		$result = $this->database->sql()->create_table($this);
		$result[] = '-- database type ' . $this->database->type();
		$result[] = '-- sql ' . get_class($this->database->sql());
		$result = array_merge($result, $this->actionSQL('create'));
		$this->clearActionSQL('create');
		return $result;
	}

	/**
	 * Returns true when action is good
	 *
	 * @param string $action
	 * @return bool
	 */
	private static function _validate_action(string $action): bool {
		return in_array($action, [
			'create',
			'add column',
			'drop column',
			'add index',
			'drop index',
			'add primary key',
			'drop primary key',
		]);
	}

	/**
	 *
	 * @param string $action
	 * @param array $sql
	 * @return self
	 * @throws Exception_Semantics
	 */
	public function addActionSQL(string $action, array $sql): self {
		if (!self::_validate_action($action)) {
			throw new Exception_Semantics("Invalid action $action passed to Database_Table::on for table $this->name");
		}
		if (!array_key_exists($action, $this->on)) {
			$this->on[$action] = [];
		}
		$this->on[$action] = array_merge($this->on[$action], $sql);
		return $this;
	}

	/**
	 * Return list oF SQL to run when an action occurs in this table
	 *
	 * @param string $action
	 * @return array
	 */
	public function actionSQL(string $action): array {
		return self::_validate_action($action) ? $this->on[$action] ?? [] : [];
	}

	/**
	 * Clear the action's SQL statements
	 *
	 * @param string $action
	 * @return self
	 */
	public function clearActionSQL(string $action): self {
		unset($this->on[$action]);
		return $this;
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
	 * @return array
	 */
	public function variables(): array {
		return [
			'database_name' => $this->database->code_name(),
			'columns' => $this->columnNames(),
			'name' => $this->name(),
			'type' => $this->type(),
		];
	}

	/**
	 *
	 * @return string
	 */
	public function _debug_dump(): string {
		$dump = get_object_vars($this);
		$dump['database'] = $this->database ? $this->database->code_name() : null;
		$dump['primary'] = $this->primary ? $this->primary->name() : null;
		return 'Object ' . __CLASS__ . " (\n" . Text::indent(_dump($dump, true)) . "\n)";
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
	 * Index exists in table?
	 * @param string $name
	 * @return bool
	 * @deprecated 2022-01
	 */
	public function has_index(string $name): bool {
		return $this->hasIndex($name);
	}

	/**
	 * @param Database_Column $dbCol
	 * @return $this
	 * @throws Exception_Semantics
	 * @deprecated 2022-01
	 */
	public function column_add(Database_Column $dbCol): self {
		return $this->columnAdd($dbCol);
	}

	/**
	 *
	 * @param Database_Index $index
	 */
	public function index_add(Database_Index $index): self {
		return $this->addIndex($index);
	}

	/**
	 * @param array $indexes
	 * @deprecated 2022-01
	 */
	public function set_indexes(array $indexes): void {
		foreach ($indexes as $v) {
			$this->addIndex($v);
		}
	}

	/**
	 * Return array of column names
	 * @return string[]
	 * @deprecated 2022-01
	 */
	public function column_names(): array {
		return $this->columnNames();
	}

	/**
	 * Retieve the previous column definition for a column
	 *
	 * @param string $name
	 * @return ?Database_Column
	 * @deprecated 2022-01
	 */
	public function previous_column(string $find_name): ?Database_Column {
		return $this->previousColumn($find_name);
	}

	/**
	 * Has column
	 *
	 * @param string $name
	 * @return boolean
	 * @deprecated 2022-01
	 *
	 */
	public function has_column(string $name): bool {
		return $this->hasColumn($name);
	}

	/**
	 * @param string $action
	 * @param array $sqls
	 * @return self
	 * @deprecated 2022-01
	 */
	public function on_action(string $action, array $sqls): self {
		return $this->addActionSQL($action, $sqls);
	}

	/**
	 * @return string
	 * @deprecated 2022-01
	 */
	public function create_sql(): array {
		return $this->sqlCreate();
	}
}
