<?php
declare(strict_types=1);

/**
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
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
	 * The database this is associated with
	 *
	 * @var Database
	 */
	private Database $database;

	/**
	 * The table this index is associated with
	 *
	 * @var Database_Table
	 */
	private Database_Table $table;

	/**
	 * Array of name => size
	 * @var array
	 */
	private array $columns;

	/**
	 * index name
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * index type
	 *
	 * @var string
	 */
	private string $type;

	/**
	 * Index structure (database-specific)
	 *
	 * @todo move this to database-specific code?
	 * @var string
	 */
	private string $structure;

	/**
	 * Name of primary index, always
	 */
	public const NAME_PRIMARY = 'primary';

	public const TYPE_INDEX = 'INDEX';

	public const TYPE_UNIQUE = 'UNIQUE';

	public const TYPE_PRIMARY = 'PRIMARY KEY';

	final public function variables(): array {
		return [
			'database_name' => $this->database->codeName(),
			'table' => $this->table(),
			'name' => $this->name(),
			'type' => $this->type(),
			'columns' => $this->columns(),
			'structure' => $this->structure(),
		];
	}

	/**
	 *
	 * @param Database_Table $table
	 * @param string $name
	 * @param array $columns
	 * @param string $type
	 * @param ?string $structure
	 * @throws Exception_Semantics
	 */
	public function __construct(Database_Table $table, string $name = '', array $columns = [], string $type = self::TYPE_INDEX, string $structure = null) {
		$this->table = $table;
		$this->database = $table->database();
		$this->columns = [];
		$this->type = self::determineType($type);
		$this->name = $this->type === self::TYPE_PRIMARY ? self::NAME_PRIMARY : $name;

		$this->structure = $this->determineStructure($structure);

		if (is_array($columns)) {
			foreach ($columns as $col => $size) {
				if (is_numeric($size) || is_bool($size)) {
					try {
						$this->addColumn($col, is_bool($size) ? Database_Index::SIZE_DEFAULT : intval($size));
					} catch (Exception_NotFound $e) {
						throw new Exception_Semantics('Columns must be name => size, or => name ({0} => {1} passed for table {2}', [
							$col,
							$size,
							$table->name(),
						], 0, $e);
					}
				} elseif (!is_string($size)) {
					throw new Exception_Semantics('Columns must be name => size, or => name ({0} => {1} passed for table {2}', [
						$col,
						$size,
						$table->name(),
					]);
				} else {
					try {
						$this->addColumn($size);
					} catch (Exception_NotFound $e) {
						throw new Exception_Semantics('No such column found {name} in {table_name}', [
							'name' => $size,
							'table_name' => $table->name(),
						], 0, $e);
					}
				}
			}
		}

		try {
			$table->removeIndex($this->name());
		} catch (Exception_NotFound) {
		}

		try {
			$table->addIndex($this);
		} catch (Exception_Key $e) {
			throw new Exception_Semantics('Adding index failed', [
				'name' => $this->name(),
				'table_name' => $table->name(),
			], 0, $e);
		}
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
	public static function determineType(string $sqlType): string {
		if (empty($sqlType)) {
			return self::TYPE_INDEX;
		}
		switch (strtolower($sqlType)) {
			case 'unique':
			case 'unique key':
				return self::TYPE_UNIQUE;
			case 'primary key':
			case 'primary':
				return self::TYPE_PRIMARY;
			default:
			case 'key':
			case 'index':
				return self::TYPE_INDEX;
		}
	}

	/**
	 *
	 * @param ?string $structure
	 * @return string
	 */
	public function determineStructure(string $structure = null): string {
		if (is_string($structure)) {
			$structure = strtoupper($structure);
			switch ($structure) {
				case 'BTREE':
				case 'HASH':
					return $structure;
			}
		}
		return strtoupper($this->database->defaultIndexStructure($this->type));
	}

	/**
	 *
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
			$this->database->application->deprecated('table setter');
		}
		return $this->table;
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
	public function columnSizes(): array {
		return $this->columns;
	}

	/**
	 *
	 * @return int
	 */
	public function columnCount(): int {
		return count($this->columns);
	}

	/**
	 *
	 * @return string
	 */
	public function type($set = null): string {
		if ($set !== null) {
			$this->database->application->deprecated('set type');
			$this->setType($set);
		}
		return $this->type;
	}

	/**
	 * @param string $set
	 * @return $this
	 */
	public function setType(string $set): self {
		$this->type = self::determineType($set);
		if ($this->type === self::TYPE_PRIMARY) {
			$this->name = self::NAME_PRIMARY;
		}
		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function structure(): string {
		return $this->structure;
	}

	/**
	 * @param Database_Column $database_column
	 * @param int $size
	 * @return $this
	 */
	public function addDatabaseColumn(Database_Column $database_column, int $size = self::SIZE_DEFAULT): self {
		$column = $database_column->name();
		if ($this->type === self::TYPE_PRIMARY) {
			$database_column->setPrimaryKey(true);
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
	public function addColumn(string $column, int $size = self::SIZE_DEFAULT): self {
		try {
			$database_column = $this->table->column($column);
			return $this->addDatabaseColumn($database_column, $size);
		} catch (Exception_Key $e) {
			throw new Exception_NotFound('{method}: {col} not found in {table}', [
				'col' => $column,
				'method' => __METHOD__,
				'table' => $this->table,
			], 0, $e);
		}
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
				$logger->debug('Database_Index::isSimilar(' . $this->type() . ' !== ' . $that->type() . ') Table types different');
			}
			return false;
		}
		if ($this->structure() !== $that->structure()) {
			if ($debug) {
				$logger->debug('Database_Index::isSimilar(' . $this->structure() . ' !== ' . $that->structure() . ') Table structures different');
			}
			return false;
		}
		if ($this->table()->name() !== $that->table()->name()) {
			if ($debug) {
				$logger->debug('Database_Index::isSimilar(' . $this->table() . ' !== ' . $that->table() . ') Tables different');
			}
			return false;
		}
		if ($this->name() !== $that->name()) {
			if ($debug) {
				$logger->debug('Database_Index::isSimilar(' . $this->name() . ' !== ' . $that->name() . ') Names different');
			}
			return false;
		}
		if ($this->columnCount() !== $that->columnCount()) {
			if ($debug) {
				$logger->debug('Database_Index::isSimilar(' . $this->columnCount() . ' !== ' . $that->columnCount() . ') ColumnCount different');
			}
			return false;
		}
		$thisCols = $this->columns();
		$thatCols = $that->columns();
		ksort($thisCols);
		ksort($thatCols);
		if (serialize($thisCols) !== serialize($thatCols)) {
			if ($debug) {
				$logger->debug("Database_Index::isSimilar(\n" . serialize($thisCols) . " !== \n" . serialize($thatCols) . "\n) ColumnCount different");
			}
			return false;
		}
		return true;
	}

	/**
	 *
	 */
	public function sql_index_type(): string {
		return $this->database->sql()->index_type($this->table, $this->name, $this->type, $this->columnSizes());
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
		return $this->type === self::TYPE_PRIMARY;
	}

	/**
	 *
	 * @return boolean
	 */
	public function isIndex(): bool {
		return $this->type === self::TYPE_INDEX;
	}

	/**
	 *
	 * @return boolean
	 */
	public function isUnique(): bool {
		return $this->type === self::TYPE_UNIQUE;
	}

	/**
	 *
	 * @return string
	 */
	public function _debug_dump(): string {
		$vars = get_object_vars($this);
		$vars['database'] = $this->database->codeName();
		$vars['table'] = $this->table->name();
		return 'Object:' . __CLASS__ . " (\n" . Text::indent(_dump($vars)) . "\n)";
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
	public function is_index(): bool {
		return $this->isIndex();
	}

	/**
	 * @return boolean
	 * @deprecated 2022-01
	 */
	public function is_unique(): bool {
		return $this->isUnique();
	}

	/**
	 *
	 * @param mixed $mixed
	 * @param string $size
	 * @return \zesk\Database_Index
	 * @throws Database_Exception
	 * @throws Exception_NotFound
	 * @deprecated 2022-01
	 */
	public function column_add(mixed $mixed, int $size = self::SIZE_DEFAULT) {
		$this->database->application->deprecated(__METHOD__);
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
			throw new Database_Exception($this->database, 'Database_Index::column_add(' . gettype($mixed) . '): Invalid type', [], 0);
		}
	}

	/**
	 * @deprecated 2022-05
	 */
	public function column_sizes(): array {
		return $this->columnSizes();
	}

	/**
	 * @return int
	 * @deprecated 2022-05
	 */
	public function column_count() {
		return $this->columnCount();
	}
}
