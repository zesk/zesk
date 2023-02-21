<?php
declare(strict_types=1);
/**
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */

namespace zesk\Database;

use zesk\Exception\KeyNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\Semantics;
use zesk\Text;

/**
 * @author kent
 * @package zesk
 */
class Index {
	public const SIZE_DEFAULT = -1;

	/**
	 * The database this is associated with
	 *
	 * @var Base
	 */
	private Base $database;

	/**
	 * The table this index is associated with
	 *
	 * @var Table
	 */
	private Table $table;

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
	 * @param Table $table
	 * @param string $name
	 * @param string $type
	 * @param ?string $structure
	 * @throws Semantics
	 */
	public function __construct(Table $table, string $name = '', string $type = self::TYPE_INDEX, string $structure = null) {
		$this->table = $table;
		$this->database = $table->database();
		$this->columns = [];
		$this->type = self::determineType($type);
		$this->name = $this->type === self::TYPE_PRIMARY ? self::NAME_PRIMARY : $name;

		$this->structure = $this->determineStructure($structure);

		$this->columns = [];

		try {
			$table->removeIndex($this->name());
		} catch (NotFoundException|KeyNotFound) {
		}

		try {
			$table->addIndex($this);
		} catch (KeyNotFound $e) {
			throw new Semantics('Adding index failed', [
				'name' => $this->name(),
				'table_name' => $table->name(),
			], 0, $e);
		}
	}

	/**
	 * @param array $columns
	 * @return $this
	 * @throws Semantics
	 */
	public function addColumns(array $columns): self {
		$table = $this->table;
		foreach ($columns as $col => $size) {
			if (is_numeric($size) || is_bool($size)) {
				try {
					$this->addColumn($col, is_bool($size) ? Index::SIZE_DEFAULT : intval($size));
				} catch (NotFoundException $e) {
					throw new Semantics('Columns must be name => size, or => name ({0} => {1} passed for table {2}', [
						$col,
						$size,
						$table->name(),
					], 0, $e);
				}
			} elseif (!is_string($size)) {
				throw new Semantics('Columns must be name => size, or => name ({0} => {1} passed for table {2}', [
					$col,
					$size,
					$table->name(),
				]);
			} else {
				try {
					$this->addColumn($size);
				} catch (NotFoundException $e) {
					throw new Semantics('No such column found {name} in {table_name}', [
						'name' => $size,
						'table_name' => $table->name(),
					], 0, $e);
				}
			}
		}
		return $this;
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
	 * @return Table
	 */
	public function table(): Table {
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
	public function type(): string {
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
	 * @param Column $database_column
	 * @param int $size
	 * @return $this
	 */
	public function addDatabaseColumn(Column $database_column, int $size = self::SIZE_DEFAULT): self {
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
	 * @throws NotFoundException
	 */
	public function addColumn(string $column, int $size = self::SIZE_DEFAULT): self {
		try {
			$database_column = $this->table->column($column);
			return $this->addDatabaseColumn($database_column, $size);
		} catch (KeyNotFound $e) {
			throw new NotFoundException('{method}: {col} not found in {table}', [
				'col' => $column,
				'method' => __METHOD__,
				'table' => $this->table,
			], 0, $e);
		}
	}

	/**
	 *
	 * @param Index $that
	 * @param bool $debug
	 * @return boolean
	 */
	public function isSimilar(Index $that, bool $debug = false): bool {
		$logger = $this->database->application->logger;
		if ($this->type() !== $that->type()) {
			if ($debug) {
				$logger->debug('Index::isSimilar(' . $this->type() . ' !== ' . $that->type() . ') Table types different');
			}
			return false;
		}
		if ($this->structure() !== $that->structure()) {
			if ($debug) {
				$logger->debug('Index::isSimilar(' . $this->structure() . ' !== ' . $that->structure() . ') Table structures different');
			}
			return false;
		}
		if ($this->table()->name() !== $that->table()->name()) {
			if ($debug) {
				$logger->debug('Index::isSimilar(' . $this->table() . ' !== ' . $that->table() . ') Tables different');
			}
			return false;
		}
		if ($this->name() !== $that->name()) {
			if ($debug) {
				$logger->debug('Index::isSimilar(' . $this->name() . ' !== ' . $that->name() . ') Names different');
			}
			return false;
		}
		if ($this->columnCount() !== $that->columnCount()) {
			if ($debug) {
				$logger->debug('Index::isSimilar(' . $this->columnCount() . ' !== ' . $that->columnCount() . ') ColumnCount different');
			}
			return false;
		}
		$thisCols = $this->columns();
		$thatCols = $that->columns();
		ksort($thisCols);
		ksort($thatCols);
		if (serialize($thisCols) !== serialize($thatCols)) {
			if ($debug) {
				$logger->debug("Index::isSimilar(\n" . serialize($thisCols) . " !== \n" . serialize($thatCols) . "\n) ColumnCount different");
			}
			return false;
		}
		return true;
	}

	/**
	 *
	 */
	public function sqlIndexType(): string {
		return $this->database->sqlDialect()->indexType($this->table, $this->name, $this->type, $this->columnSizes());
	}

	/**
	 *
	 * @return string
	 */
	public function sqlIndexAdd(): string {
		return $this->database->sqlDialect()->alterTableIndexAdd($this->table, $this);
	}

	/**
	 *
	 * @return string
	 */
	public function sqlIndexDrop(): string {
		return $this->database->sqlDialect()->alterTableIndexDrop($this->table, $this);
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
}
