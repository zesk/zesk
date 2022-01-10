<?php
declare(strict_types=1);

/**
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */

namespace zesk;

/**
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @package zesk
 */
class Database_Index {
	public const SIZE_DEFAULT = -1;

	/**
	 * The databsae this is associated with
	 *
	 * @var Database
	 */
	private $database;

	/**
	 * The table this index is associated with
	 *
	 * @var Database_Table
	 */
	private $table;

	/**
	 * Array of name => size
	 * @var array
	 */
	private $columns;

	/**
	 * index name
	 *
	 * @var string
	 */
	private $name;

	/**
	 * index type
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Index structure (database-specific)
	 *
	 * @todo move this to database-specific code
	 * @var string
	 */
	private $structure;

	public const Index = "INDEX";

	public const Unique = "UNIQUE";

	public const Primary = "PRIMARY KEY";

	/**
	 *
	 * @param Database_Table $table
	 * @param string $name
	 * @param unknown $columns
	 * @param string $type
	 * @param unknown $structure
	 * @throws Exception_Semantics
	 */
	public function __construct(Database_Table $table, string $name = "", array $columns = [], $type = "INDEX", string $structure = null) {
		$this->table = $table;
		$this->database = $table->database();
		$this->columns = [];
		$this->type = self::determineType($type);
		$this->name = empty($name) && $this->type === self::Primary ? "primary" : $name;

		$this->structure = $this->determineStructure($structure);

		if (is_array($columns)) {
			foreach ($columns as $col => $size) {
				if (is_numeric($size) || is_bool($size)) {
					$this->addColumn($col, is_bool($size) ? Database_Index::SIZE_DEFAULT : intval($size));
				} elseif (!is_string($size)) {
					throw new Exception_Semantics(map("Columns must be name => size, or => name ({0} => {1} passed for table {2}", [
						$col,
						$size,
						$table->name(),
					]));
				} else {
					$this->addColumn($size);
				}
			}
		}
		$table->addIndex($this);
	}

	/**
	 *
	 */
	public function __destruct() {
		unset($this->table);
		unset($this->database);
		unset($this->columns);
	}

	/**
	 *
	 * @param string $sqlType
	 * @return string
	 * @todo Move into database implementation
	 */
	public static function determineType($sqlType) {
		if (empty($sqlType)) {
			return self::Index;
		}
		switch (strtolower($sqlType)) {
			case "unique":
			case "unique key":
				return self::Unique;
			case "primary key":
			case "primary":
				return self::Primary;
			default:
			case "key":
			case "index":
				return self::Index;
		}
	}

	/**
	 *
	 * @param unknown $structure
	 * @return string
	 */
	public function determineStructure($structure) {
		if (is_string($structure)) {
			$structure = strtoupper($structure);
			switch ($structure) {
				case "BTREE":
				case "HASH":
					return $structure;
			}
		}
		return strtoupper($this->database->default_index_structure($this->type));
	}

	/**
	 *
	 * @param string $lower
	 * @return string
	 */
	public function name(): string {
		return $this->name;
	}

	/**
	 * @return Database_Table
	 */
	public function table($set = null): Database_Table {
		if ($set !== null) {
			$this->database->application->deprecated("table setter");
			$this->setTable($set);
		}
		return $this->table;
	}

	/**
	 * @return Database_Table
	 */
	public function setTable(Database_Table $set): self {
		$this->table = $set;
		return $this;
	}

	/**
	 * @return array
	 */
	public function columns(): array {
		return array_keys($this->columns);
	}

	/**
	 *
	 */
	public function column_sizes(): array {
		return $this->columns;
	}

	/**
	 *
	 * @return number
	 */
	public function column_count() {
		return count($this->columns);
	}

	/**
	 *
	 * @return string
	 */
	public function type($set = null) {
		if ($set !== null) {
			$this->type = self::determineType($set);
			return $this;
		}
		return $this->type;
	}

	/**
	 *
	 * @return string
	 */
	public function structure() {
		return $this->structure;
	}

	/**
	 * @param Database_Column $column
	 * @param int $size
	 * @return $this
	 */
	public function addDatabaseColumn(Database_Column $database_column, int $size = self::SIZE_DEFAULT): self {
		$column = $database_column->name();
		if ($this->type === self::Primary) {
			$database_column->primary_key(true);
		}
		$this->columns[$column] = $size;
		return $this;
	}

	/**
	 * @param string $column
	 * @param int $size
	 * @return $this
	 * @throws Exception_NotFound
	 */
	public function addColumn(string $column, int $size = self::SIZE_DEFAULT) {
		$database_column = $this->table->column($column);
		if (!$database_column) {
			throw new Exception_NotFound("{method}: {col} not found in {table}", [
				"col" => $column,
				"method" => __METHOD__,
				"table" => $this->table,
			]);
		}
		return $this->addDatabaseColumn($database_column, $size);
	}

	/**
	 *
	 * @param unknown $mixed
	 * @param string $size
	 * @return \zesk\Database_Index
	 * @throws Database_Exception
	 * @throws Exception_NotFound
	 * @deprecated 2022-01
	 */
	public function column_add(mixed $mixed, int $size = self::SIZE_DEFAULT) {
		if ($mixed instanceof Database_Column) {
			return $this->addDatabaseColumn($mixed, $size);
		} elseif (is_string($mixed)) {
			return $this->addColumn($mixed, $size);
		} elseif (is_array($mixed)) {
			foreach ($mixed as $k => $v) {
				if (is_numeric($k)) {
					$this->addColumn($v);
				} else {
					$this->addColumn($k, $v);
				}
			}
			return $this;
		} else {
			throw new Database_Exception($this->database, "Database_Index::column_add(" . gettype($mixed) . "): Invalid type", [], 0);
		}
	}

	/**
	 * @param Database_Index $that
	 * @param bool $debug
	 * @return bool
	 * @deprecated 2022-01
	 */
	public function is_similar(Database_Index $that, bool $debug = false): bool {
		return $this->isSimilar($that, $debug);
	}

	/**
	 *
	 * @param Database_Index $that
	 * @param bool $debug
	 * @return boolean
	 */
	public function isSimilar(Database_Index $that, bool $debug = false): bool {
		$logger = $this->database->application->logger;
		if ($this->type() !== $that->type()) {
			if ($debug) {
				$logger->debug("Database_Index::is_similar(" . $this->type() . " !== " . $that->type() . ") Table types different");
			}
			return false;
		}
		if ($this->structure() !== $that->structure()) {
			if ($debug) {
				$logger->debug("Database_Index::is_similar(" . $this->structure() . " !== " . $that->structure() . ") Table structures different");
			}
			return false;
		}
		if ($this->table()->name() !== $that->table()->name()) {
			if ($debug) {
				$logger->debug("Database_Index::is_similar(" . $this->table() . " !== " . $that->table() . ") Tables different");
			}
			return false;
		}
		if ($this->name(true) !== $that->name(true)) {
			if ($debug) {
				$logger->debug("Database_Index::is_similar(" . $this->name() . " !== " . $that->name() . ") Names different");
			}
			return false;
		}
		if ($this->column_count() !== $that->column_count()) {
			if ($debug) {
				$logger->debug("Database_Index::is_similar(" . $this->column_count() . " !== " . $that->column_count() . ") ColumnCount different");
			}
			return false;
		}
		$thisCols = $this->columns();
		$thatCols = $that->columns();
		ksort($thisCols);
		ksort($thatCols);
		if (serialize($thisCols) !== serialize($thatCols)) {
			if ($debug) {
				$logger->debug("Database_Index::is_similar(\n" . serialize($thisCols) . " !== \n" . serialize($thatCols) . "\n) ColumnCount different");
			}
			return false;
		}
		return true;
	}

	/**
	 *
	 */
	public function sql_index_type(): string {
		return $this->database->sql()->index_type($this->table, $this->name, $this->type, $this->column_sizes());
	}

	/**
	 *
	 * @return string
	 */
	public function sql_index_add(): string {
		return $this->database->sql()->alter_table_index_add($this->table, $this);
	}

	/**
	 *
	 * @return string
	 */
	public function sql_index_drop(): string {
		return $this->database->sql()->alter_table_index_drop($this->table, $this);
	}

	/**
	 *
	 * @return boolean
	 */
	public function isPrimary(): bool {
		return $this->type === self::Primary;
	}

	/**
	 *
	 * @return boolean
	 */
	public function isIndex(): bool {
		return $this->type === self::Index;
	}

	/**
	 *
	 * @return boolean
	 */
	public function isUnique(): bool {
		return $this->type === self::Unique;
	}

	/**
	 *
	 * @return string
	 */
	public function _debug_dump(): string {
		$vars = get_object_vars($this);
		$vars['database'] = $this->database->code_name();
		$vars['table'] = $this->table->name();
		return "Object:" . __CLASS__ . " (\n" . Text::indent(_dump($vars)) . "\n)";
	}

	/**
	 * @return boolean
	 * @deprecated 2022-01
	 */
	public function is_primary(): bool {
		return $this->isPrimary();
	}

	/**
	 * @return boolean
	 * @deprecated 2022-01
	 */
	public function is_index() {
		return $this->isIndex();
	}

	/**
	 * @return boolean
	 * @deprecated 2022-01
	 */
	public function is_unique() {
		return $this->isUnique();
	}
}
