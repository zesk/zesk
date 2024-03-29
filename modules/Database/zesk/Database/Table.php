<?php
declare(strict_types=1);
/**
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */

namespace zesk\Database;

use zesk\ArrayTools;
use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\Semantics;
use zesk\Hookable;
use zesk\Text;

/**
 *
 * @package zesk
 * @subpackage system
 */
class Table extends Hookable {
	/**
	 *
	 * @var Base
	 */
	private Base $database;

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
	 * @var ?Index
	 */
	private ?Index $primary = null;

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
	 * @param Base $db
	 * @param string $table_name
	 * @param string $type
	 * @param array $options
	 */
	public function __construct(Base $db, string $table_name, string $type = '', array $options = []) {
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
			/* $var $new_column Column */
			$this->columns[$name] = $new_column = clone $column;
			$new_column->table($this);
		}
		foreach ($this->indexes as $name => $index) {
			$this->indexes[$name] = $new_index = clone $index;
			/* $var $new_index Index */
			if ($new_index->is_primary()) {
				$this->primary = $new_index;
			}
		}
	}

	/**
	 * Returns primary index
	 *
	 * @return ?Index
	 */
	public function primary(): ?Index {
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
	 * @return string
	 */
	public function source(): string {
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
	 * @return Base
	 */
	public function database(): Base {
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
			return $this->database->defaultEngine();
		}
		return $this->type;
	}

	/**
	 * Default index structure for table (e.g.
	 * BTREE, etc.)
	 * @return string
	 */
	public function defaultIndexStructure(): string {
		return $this->database->defaultIndexStructure($this->type);
	}

	/**
	 * Table name
	 *
	 * @return string
	 */
	public function name(): string {
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
	 * Array of Column
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
			/* @var $col_object Column */
			$result[] = $col_object->name();
		}
		return $result;
	}

	/**
	 * Retrieve the column from the table
	 *
	 * @param string $name
	 * @return Column
	 * @throws KeyNotFound
	 */
	public function column(string $name): Column {
		if (array_key_exists($name, $this->columns)) {
			return $this->columns[$name];
		}

		throw new KeyNotFound('No column {name} in {table}', ['name' => $name, 'table' => $this->name]);
	}

	/**
	 * Retrieve the previous column definition for a column
	 *
	 * @param string $find_name
	 * @return ?Column
	 */
	public function previousColumn(string $find_name): ?Column {
		foreach ($this->columns as $column) {
			/* @var $column Column */
			$previous_name = $column->previousName();
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
	 * @throws NotFoundException
	 * @throws Semantics
	 */
	private function collectIndexes(): array {
		$indexes = [];
		foreach ($this->columns as $col) {
			/* @var $col Column */
			$dbColName = $col->name();
			$indexes_types = $col->indexesTypes();
			foreach ($indexes_types as $name => $type) {
				if (!$this->hasIndex($name)) {
					$indexes[$name] = new Index($this, $name, $type);
				}
				$indexes[$name]->addColumn($dbColName, $col->optionInt($type . '_size', Index::SIZE_DEFAULT));
			}
		}
		return $indexes;
	}

	/**
	 * Retrieve the index for the table
	 *
	 * @param string $name
	 * @return Index
	 * @throws NotFoundException
	 */
	public function index(string $name): Index {
		$indexes = $this->indexes();
		if (!isset($indexes[$name])) {
			throw new NotFoundException('Index {name}', ['name' => $name]);
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

			try {
				$indexes = $this->collectIndexes();
			} catch (Semantics|NotFoundException) {
				$indexes = [];
			}
			assert($this->indexes === []);
			$this->indexes = $indexes;
		}
		$by_name = [];
		foreach ($this->indexes as $index) {
			$by_name[$index->name()] = $index;
		}
		return $by_name;
	}

	/**
	 * @param Index $index
	 * @return $this
	 * @throws KeyNotFound
	 */
	public function addIndex(Index $index): self {
		$indexes = $this->indexes();
		$name = $index->name();
		if (isset($indexes[$name])) {
			throw new KeyNotFound('Index {name} Already exists', ['name' => $name]);
		}
		if ($index->type() === Index::TYPE_PRIMARY) {
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
	 * @return Index
	 * @throws NotFoundException|KeyNotFound
	 */
	public function removeIndex(string $name): Index {
		$index = $this->index($name);
		if ($index->type() === Index::TYPE_PRIMARY) {
			if ($this->primary) {
				assert($index === $this->primary);

				try {
					$this->removePrimaryIndex();
				} catch (KeyNotFound) {
				}
			}
			$this->setPrimaryIndex($index);
		}
		$name = $index->name();
		assert(array_key_exists($name, $this->indexes));
		unset($this->indexes[$name]);
		return $index;
	}

	/**
	 * @param Index $index
	 * @return void
	 * @throws KeyNotFound
	 */
	private function setPrimaryIndex(Index $index): void {
		assert($this->primary === null);
		$this->primary = $index;
		foreach ($index->columns() as $col) {
			$this->column($col)->setPrimaryKey(true);
		}
	}

	/**
	 * @return void
	 * @throws KeyNotFound
	 */
	private function removePrimaryIndex(): void {
		foreach ($this->primary->columns() as $col) {
			$this->column($col)->setPrimaryKey(false);
		}
		$this->primary = null;
	}

	/**
	 * @param Column $dbCol
	 * @return $this
	 * @throws Semantics
	 */
	public function columnAdd(Column $dbCol): self {
		$column = $dbCol->name();
		if (array_key_exists($column, $this->columns)) {
			throw new Semantics('Table::column_add({column}) already exists in {table}', [
				'column' => $column,
				'table' => $this->name,
			]);
		}
		$this->callHook('column_add', $dbCol);

		if (!$dbCol->hasSQLType()) {
			throw new Semantics("{method}: No SQL type for column {column} in table {table}\noptions: {options}", [
				'method' => __METHOD__,
				'options' => json_encode($dbCol->options()),
				'column' => $column,
				'table' => $dbCol->table()->name(),
			]);
		}
		$after_column = $dbCol->option('after_column');
		if ($after_column) {
			$this->columns = ArrayTools::insert($this->columns, $after_column, [$column => $dbCol, ]);
		} else {
			$this->columns[$column] = $dbCol;
		}
		if ($dbCol->primaryKey()) {
			if (!$this->primary) {
				$this->primary = new Index($this, Index::NAME_PRIMARY, Index::TYPE_PRIMARY);
			}
			$this->primary->addDatabaseColumn($dbCol);
		}
		return $this;
	}

	/**
	 * Return statements to alter a table to a new setup
	 *
	 * @param Table $old_table
	 * @return array
	 */
	public function sql_alter(Table $old_table): array {
		$result = [];
		$oldTableType = $old_table->type();
		$newTableType = $this->type();

		$this->application->logger->debug('Table sql_alter {old} {new}', [
			'old' => $oldTableType,
			'new' => $newTableType,
		]);
		if (!$this->isTableAttributesSimilar($old_table)) {
			$result[] = $this->database->sqlDialect()->alterTableAttributes($this, $this->options());
		}
		return $result;
	}

	/**
	 * @param Table $that
	 * @param bool $debug
	 * @return bool
	 */
	private function isTableAttributesSimilar(Table $that, bool $debug = false): bool {
		$logger = $this->application->logger;
		$defaults = $this->database->tableAttributes();
		$this_attributes = $this->options($defaults);
		$that_attributes = $that->options($defaults);
		if ($this_attributes !== $that_attributes) {
			if ($debug) {
				$logger->debug('Table::isSimilar({this_name}): Mismatched attributes: {this} != {that}', [
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
	 *
	 * @param Table $that
	 * @param bool $debug
	 * @return bool
	 */
	public function isSimilar(Table $that, bool $debug = false): bool {
		$logger = $this->application->logger;
		if (!$this->isTableAttributesSimilar($that, $debug)) {
			return false;
		}
		if (($this_count = count($this->columns())) !== ($that_count = count($that->columns()))) {
			if ($debug) {
				$logger->debug("Table::isSimilar($this->name): Column Counts: $this_count != $that_count");
			}
			return false;
		}
		if (($this_count = count($this->indexes())) !== ($that_count = count($that->indexes()))) {
			if ($debug) {
				$logger->debug("Table::isSimilar($this->name): Index Counts: $this_count != $that_count");
			}
			return false;
		}

		/*
		 * Columns
		 */
		$thisColumns = $this->columns();
		foreach ($thisColumns as $k => $thisCol) {
			/* @var $thisCol Column */
			/* @var $thatCol Column */
			try {
				$thatCol = $that->column($k);
				if (!$thisCol->isSimilar($thatCol, $debug)) {
					if ($debug) {
						$logger->debug("Table::isSimilar($this->name): Dissimilar column $k");
					}
					return false;
				}
			} catch (KeyNotFound) {
				if ($debug) {
					$logger->debug("Table::isSimilar($this->name): No target column $k");
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
					$logger->debug("Table::isSimilar($this->name): No that index $k");
				}
				return false;
			}
			if (!$this_index->isSimilar($thatIndexes[$k])) {
				if ($debug) {
					$logger->debug("Table::isSimilar($this->name): Dissimilar index $k");
				}
				return false;
			}
		}

		$extras = $this->database->tableAttributes();
		foreach ($extras as $extra => $default) {
			$this_value = $this->option($extra, $default);
			$that_value = $that->option($extra, $default);
			if ($this_value !== $that_value) {
				if ($debug) {
					$logger->debug("Table::isSimilar($this->name): $extra: $this_value !== $that_value");
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
		$result = $this->database->sqlDialect()->createTable($this);
		$result[] = '-- database type ' . $this->database->type();
		$result[] = '-- sql ' . get_class($this->database->sqlDialect());
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
	 * @throws Semantics
	 */
	public function addActionSQL(string $action, array $sql): self {
		if (!self::_validate_action($action)) {
			throw new Semantics("Invalid action $action passed to Table::on for table $this->name");
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
		return self::_validate_action($action) ? toArray($this->on[$action] ?? []) : [];
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
	public function __toString(): string {
		return $this->name();
	}

	/**
	 * @return array
	 */
	public function variables(): array {
		return [
			'database_name' => $this->database->codeName(),
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
		$dump['database'] = $this->database->codeName();
		$dump['primary'] = $this->primary?->name();
		return 'Object ' . __CLASS__ . " (\n" . Text::indent(_dump($dump)) . "\n)";
	}
}
