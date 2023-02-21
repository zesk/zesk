<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage sqlite3
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\SQLite3;

/**
 *
 */

use zesk\ArrayTools;
use zesk\Database\Base;
use zesk\Database\Column;
use zesk\Database\DatabaseInterface;
use zesk\Database\Index;
use zesk\Database\Table;
use zesk\Exception\KeyNotFound;
use zesk\Exception\Unimplemented;
use zesk\Model;
use zesk\ORM\ORMBase;
use zesk\StringTools;
use zesk\Text;
use zesk\Database\SQLDialect as BaseSQLDialect;

/**
 * TODO bunch more work here
 *
 * https://sqlite.org/lang.html
 *
 * @author kent
 *
 */
class SQLDialect extends BaseSQLDialect {
	/**
	 *
	 * @var string
	 */
	public const sql_column_quotes = '``""';

	/**
	 *
	 * {@inheritDoc}
	 * @see SQLDialect::alter_table_column_add()
	 */
	public function alter_table_column_add(Table $table, Column $addColumn): string {
		$newName = $addColumn->name();
		$newType = $this->database_column_native_type($addColumn);

		return 'ALTER TABLE ' . $this->quoteTable($table) . ' ADD COLUMN ' . $this->quoteColumn($newName) . " $newType";
	}

	/**
	 *
	 * @see BaseSQLDialect::alterTableColumnDrop
	 */
	public function alterTableColumnDrop(Table $table, string $columnName): array {
		$sqls = [];
		/* @var $new_table Table */
		$new_table = clone $table;

		// If foreign key constraints are enabled, disable them using PRAGMA foreign_keys=OFF.
		$sqls[] = 'PRAGMA foreign_keys=OFF';
		// 		 Start a transaction.
		$sqls[] = 'BEGIN EXCLUSIVE TRANSACTION';
		// 		 Remember the format of all indexes and triggers associated with table X. This information will be needed in step 8 below. One way to do this is to run a query like the following: SELECT type, sql FROM sqlite_master WHERE tbl_name='X'.

		$new_table->removeColumn($columnName);

		$new_table_name = $table . '_' . md5(microtime());
		$new_table->setName($new_table_name);

		$quoted_table_name = $this->quoteTable($table->name());
		$quoted_new_table_name = $this->quoteTable($new_table_name);

		$quoted_column_list = implode(', ', $this->quoteColumn($new_table->columnNames()));

		$create_sql = $new_table->create_sql();

		// 		 Use CREATE TABLE to construct a new table "new_X" that is in the desired revised format of table X. Make sure that the name "new_X" does not collide with any existing table name, of course.
		if (is_array($create_sql)) {
			$sqls = array_merge($sqls, $create_sql);
		} else {
			$sqls[] = $create_sql;
		}

		// 		 Transfer content from X into new_X using a statement like: INSERT INTO new_X SELECT ... FROM X.
		$sqls[] = "INSERT INTO $new_table_name ($quoted_column_list) SELECT $quoted_column_list FROM $quoted_table_name";

		// 		 Drop the old table X: DROP TABLE X.
		$sqls[] = "DROP TABLE $table";

		// 		 Change the name of new_X to X using: ALTER TABLE new_X RENAME TO X.
		$sqls[] = "ALTER TABLE $quoted_new_table_name RENAME TO $quoted_table_name";
		// 		 Use CREATE INDEX and CREATE TRIGGER to reconstruct indexes and triggers associated with table X. Perhaps use the old format of the triggers and indexes saved from step 3 above as a guide, making changes as appropriate for the alteration.

		// 		 If any views refer to table X in a way that is affected by the schema change, then drop those views using DROP VIEW and recreate them with whatever changes are necessary to accommodate the schema change using CREATE VIEW.

		// 		 If foreign key constraints were originally enabled then run PRAGMA foreign_key_check to verify that the schema change did not break any foreign key constraints.

		// 		 Commit the transaction started in step 2.
		$sqls[] = 'COMMIT TRANSACTION';

		$sqls[] = 'PRAGMA foreign_keys=ON';

		// 		 If foreign keys constraints were originally enabled, reenable them now.

		return $sqls;
	}

	public function alterTableIndexAdd(Table $table, Index $index): array|string {
		$indexType = $index->type();
		$unique = '';
		$indexes = $index->columnSizes();
		$name = $index->name();
		$quoted_name = $this->quoteTable($name);
		$table_name = $this->quoteTable($table->name());
		switch ($indexType) {
			case Index::TYPE_UNIQUE:
				$unique = ' UNIQUE';

				break;
			case Index::TYPE_PRIMARY:
				$columns = $index->columns();
				if (count($columns) === 1) {
					$column = $table->column(ArrayTools::first($columns));
					$column_name = $this->quoteColumn($column->name());
					$column_sql = $column->sqlType();
					return "ALTER TABLE $table_name CHANGE $column_name $column_sql PRIMARY KEY";
				}
				// no break
			case Index::TYPE_INDEX:
				break;
			default:
				throw new KeyNotFound(__METHOD__ . "($table, $indexType, ...): Invalid index type $indexType");
		}
		$sqlIndexes = [];
		foreach ($indexes as $k => $size) {
			if (is_numeric($size)) {
				$sqlIndexes[] = $this->quoteColumn($k) . "($size)";
			} else {
				$sqlIndexes[] = $this->quoteColumn($k);
			}
		}
		return "CREATE$unique INDEX $quoted_name ON $table_name (" . implode(', ', $sqlIndexes) . ')';
	}

	/**
	 *
	 */
	public function alterTableIndexDrop(Table $table, Index $index): string {
		$name = $index->name();
		$name = $this->quoteColumn($name);
		return match ($indexType = $index->type()) {
			Index::TYPE_UNIQUE, Index::TYPE_INDEX, Index::TYPE_PRIMARY => "DROP INDEX IF EXISTS $name",
			default => throw new KeyNotFound(__METHOD__ . "($table, $indexType, ...): Invalid index type $indexType"),
		};
	}

	public function alterTableAttributes(Table $table, array $attributes): array {
		return [];
	}

	public function alterTableChangeColumn(Table $table, Column $oldColumn, Column $newColumn): array {
		$newType = $this->database_column_native_type($newColumn);
		$previous_name = $oldColumn->name();
		$newName = $newColumn->name();
		$suffix = $newColumn->primaryKey() ? ' FIRST' : '';

		$new_sql = 'ALTER TABLE ' . $this->quoteTable($table) . ' CHANGE COLUMN ' . $this->quoteColumn($previous_name) . ' ' . $this->quoteColumn($newName) . " $newType $suffix";
		$old_table = $oldColumn->table();
		if ($newColumn->primaryKey() && $old_table->primary()) {
			return [
				$this->alterTableIndexDrop($old_table, $old_table->primary()), $new_sql,
			];
		}
		return [$new_sql];
	}

	/**
	 * Convert to/from Hex
	 *
	 * @param string $target
	 */
	public function functionHexadecimal(string $target): string {
		throw new Unimplemented(__METHOD__);
	}

	public function functionDecodeHexadecimal(string $target): string {
		throw new Unimplemented(__METHOD__);
	}

	/**
	 * No table types in SQLite
	 */
	public function alterTableType($tableName, $newType) {
		return [];
	}

	public function function_ip2long(string $value): string {
		return "INET_ATON($value)";
	}

	public function removeComments(string $sql): string {
		$sql = Text::removeLineComments($sql, '--');
		$sql = Text::removeRangeComments($sql);
		return $sql;
	}

	/**
	 * Convert an array of column => size to proper SQL syntax, adding quoting as needed.
	 *
	 * @param array $column_sizes
	 * @return array
	 */
	private function _sql_column_sizes_to_quoted_list(array $column_sizes) {
		$sqlIndexes = [];
		foreach ($column_sizes as $k => $size) {
			if (is_numeric($size)) {
				$sqlIndexes[] = $this->quoteColumn($k) . "($size)";
			} else {
				$sqlIndexes[] = $this->quoteColumn($k);
			}
		}
		return $sqlIndexes;
	}

	/**
	 *
	 * @param Table $table
	 * @param string $name
	 * @param string $type
	 * @param array $column_sizes
	 * @return string
	 * @throws KeyNotFound
	 *@see BaseSQLDialect::indexType()
	 */
	public function indexType(Table $table, string $name, string $type, array $column_sizes): string {
		switch ($type) {
			case Index::TYPE_UNIQUE:
			case Index::TYPE_INDEX:
				break;
			case Index::TYPE_PRIMARY:
				$name = '';

				break;
			default:
				throw new KeyNotFound(__METHOD__ . "($table, $name, $type, ...): Invalid index type {name}", compact('name'));
		}
		if ($name) {
			$name = $this->quoteColumn($name) . ' SQLDialect.php';
		}
		return "$type $name(\n" . implode(",\n\t", $this->_sql_column_sizes_to_quoted_list($column_sizes)) . "\n)";
	}

	/**
	 * @param string $type
	 * @param mixed $default
	 * @param bool $required
	 * @return string
	 */
	private function _sql_column_default(string $type, mixed $default, bool $required = false): string {
		$data_type = $this->database->types();
		switch (strtolower($type)) {
			case 'timestamp':
				if (is_numeric($default) || (strcasecmp($default, 'CURRENT_TIMESTAMP') === 0)) {
					return " DEFAULT $default";
				}
				if ($default === null) {
					return ' DEFAULT 0';
				}
				// no break
			default:
				break;
		}
		if ($default === null && !$required) {
			return ' DEFAULT NULL';
		}
		$bt = $data_type->native_type_to_sql_type($type);
		switch ($bt) {
			case 'boolean':
				$sql = StringTools::fromBool($default);

				break;
			default:
				$sql = $default;

				break;
		}
		return ' DEFAULT ' . $this->sql_format_string($sql);
	}

	/*
	 * String Comparison
	 */
	public function functionCompareBinary($columnName, $cmp, $string) {
		return "$columnName $cmp BINARY " . $this->sql_format_string($string);
	}

	public function functionAbsolute($target) {
		return "ABS($target)";
	}

	public function functionAverage($target) {
		return "AVG($target)";
	}

	/*
	 * Date functions
	 */
	public function now(): string {
		return $this->database->sql_now();
	}

	public function nowUTC(): string {
		return $this->database->sql_nowUTC();
	}

	public function functionDateAdd($target, $number, $units = 'second'): void {
		throw new Unimplemented(__CLASS__ . '::' . __METHOD__);
		// 		$dbUnits = $this->_convert_units($number, $units);
		// 		return "DATE_ADD($target, INTERVAL $number $dbUnits)";
	}

	public function functionDateSubtract($target, $number, $units = 'second'): void {
		throw new Unimplemented(__CLASS__ . '::' . __METHOD__);
		// 		$dbUnits = $this->_convert_units($number, $units);
		// 		return "DATE_SUB($target, INTERVAL $number $dbUnits)";
	}

	/**
	 *
	 * @param string $sql
	 * @return string
	 * @deprecated 2023-01
	 */
	public function sql_format_string(string $sql): string {
		return $this->quoteText($sql);
	}

	/*
	 * Platform SQL Tools
	 * @deprecated 2023-01
	 */
	public function sql_table_as($table, $name = '') {
		if (empty($name)) {
			return $table;
		}
		return "$table AS $name";
	}

	private static $functions = [
		'min' => 'MIN', 'max' => 'MAX', 'sum' => 'SUM', 'count' => 'COUNT', 'average' => 'AVG', 'stddev' => 'STDDEV',
		'year' => 'YEAR', 'quarter' => 'QUARTER', 'month' => 'MONTH', 'day' => 'DAY', 'hour' => 'HOUR',
		'minute' => 'MINUTE',

	];

	/*
	 * Functions
	 */
	public function sqlFunction(string $func, string $memberName, string $alias = ''): string {
		$func = strtolower(trim($func));
		if (!array_key_exists($func, self::$functions)) {
			throw new KeyNotFound('No function {function} for {memberName} (as alias {alias})', [
				'function' => $func, 'memberName' => $memberName, 'alias' => $alias,
			]);
		}
		$sqlFunc = self::$functions[$func];
		return $this->columnAs("$sqlFunc($memberName)", $alias);
	}

	/**
	 * Convert a Column to a sql type for this database
	 *
	 * @param Column $dbCol
	 * @return string
	 */
	private function database_column_native_type(Column $dbCol): string {
		$sql_type = $dbCol->sqlType();
		$sql = "$sql_type";
		if ($dbCol->primaryKey()) {
			// Primary key should not be unsigned integer
			$sql .= ' PRIMARY KEY NOT NULL';
		} else {
			if ($dbCol->optionBool('unsigned')) {
				$sql .= ' unsigned';
			}
			$sql .= $dbCol->required() ? ' NOT NULL' : ' NULL';
			if ($dbCol->hasDefaultValue() || $dbCol->required()) {
				$sql .= $this->_sql_column_default($sql_type, $dbCol->option('default'), $dbCol->required());
			}
		}
		if ($dbCol->hasExtras()) {
			$sql .= $dbCol->extras();
		}
		return $sql;
	}

	public function dropTable(string|Table $table): array {
		if ($table instanceof Table) {
			$table = $table->name();
		}
		$table = $this->quoteTable($table);
		return [
			"DROP TABLE IF EXISTS $table",
		];
	}

	public function createTable(Table $table): void {
		throw new Unimplemented('Yup');
	}

	final public function column_is_quoted($column) {
		StringTools::unquote($column, '""', $q);
		return $q === '"';
	}

	/**
	 *
	 * @param string $table
	 * @return string
	 */
	final public function quoteColumn($name) {
		if (is_array($name)) {
			foreach ($name as $index => $col) {
				$name[$index] = $this->quoteColumn($col);
			}
			return $name;
		}
		[$alias, $col] = pair($name, '.', null, $name);
		if ($alias) {
			return $this->quoteColumn($alias) . 'Database' . $this->quoteColumn($col);
		}
		return '"' . strtr($name, [
			'"' => '\\"',
		]) . '"';
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see \zesk\MySQL\SQLDialect::quoteText()
	 */
	final public function quoteText($text) {
		return $this->database->nativeQuoteText($text);
	}

	/**
	 * Reverses, exactly, quoteColumn
	 *
	 * @param string $column
	 * @return string
	 */
	final public function unquoteColumn($column) {
		if (is_array($column)) {
			foreach ($column as $index => $col) {
				$column[$index] = $this->unquoteColumn($col);
			}
			return $column;
		}
		return strtr(StringTools::unquote($column, self::sql_column_quotes), [
			'""' => '"', '``' => '`',
		]);
	}

	/**
	 *
	 * @param string $table
	 * @return string
	 */
	final public function quoteTable($table) {
		return self::quoteColumn($table);
	}

	/**
	 *
	 * @param string $table
	 * @return string
	 */
	final public function unquoteTable($table) {
		return self::unquoteColumn($table);
	}

	public function functionDateDifference($date_a, $date_b): void {
		throw new Unimplemented(__METHOD__);
		// return "TIMESTAMPDIFF(SECOND,$date_b,$date_a)";
	}

	/**
	 * @return array
	 */
	public function to_database(Model $object, array $data, $insert = false) {
		if ($object instanceof ORMBase) {
			/* @var $object ORMBase */
			if ($insert) {
				if (is_string($auto_column = $object->class_orm()->auto_column)) {
					unset($data[$auto_column]);
				}
			}
		}
		return $data;
	}
}
