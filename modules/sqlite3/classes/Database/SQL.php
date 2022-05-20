<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage sqlite3
 * @author kent
 * @copyright &copy; 2022 Market Acumen, Inc.
 */
namespace sqlite3;

/**
 *
 */
use zesk\Model;
use zesk\ORM;
use zesk\Exception_Unimplemented;
use zesk\Exception_Invalid;
use zesk\Database_Table;
use zesk\Database_Index;
use zesk\Database_Column;
use zesk\Text;
use zesk\StringTools;

/**
 * TODO bunch more work here
 *
 * https://sqlite.org/lang.html
 *
 * @author kent
 *
 */
class Database_SQL extends \zesk\Database_SQL {
	/**
	 *
	 * @var string
	 */
	public const sql_column_quotes = '``""';

	/**
	 * @var Database
	 */
	protected $database = null;

	/**
	 *
	 * {@inheritDoc}
	 * @see Database_SQL::alter_table_column_add()
	 */
	public function alter_table_column_add(Database_Table $table, Database_Column $db_col_new) {
		$newName = $db_col_new->name();
		$newType = $this->database_column_native_type($db_col_new);

		return 'ALTER TABLE ' . $this->quote_table($table) . ' ADD COLUMN ' . $this->quote_column($newName) . " $newType";
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see zesk\Database::sql_alter_table_column_drop()
	 */
	public function alter_table_column_drop(Database_Table $table, $dbColName) {
		$sqls = [];
		/* @var $new_table Database_Table */
		$new_table = clone $table;

		// If foreign key constraints are enabled, disable them using PRAGMA foreign_keys=OFF.
		$sqls[] = 'PRAGMA foreign_keys=OFF';
		// 		 Start a transaction.
		$sqls[] = 'BEGIN EXCLUSIVE TRANSACTION';
		// 		 Remember the format of all indexes and triggers associated with table X. This information will be needed in step 8 below. One way to do this is to run a query like the following: SELECT type, sql FROM sqlite_master WHERE tbl_name='X'.

		$new_table->column_remove($dbColName->name());

		$new_table->name($new_table_name = strval($table) . '_' . md5(microtime()));

		$quoted_table_name = $this->quote_table($table->name());
		$quoted_new_table_name = $this->quote_table($new_table_name);

		$quoted_column_list = implode(', ', $this->quote_column($new_table->column_names()));

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

	public function alter_table_index_add(Database_Table $table, Database_Index $index) {
		$indexType = $index->type();
		$unique = '';
		$indexes = $index->column_sizes();
		$name = $index->name();
		$quoted_name = $this->quote_table($name);
		$table_name = $this->quote_table($table->name());
		switch ($indexType) {
			case Database_Index::TYPE_UNIQUE:
				$unique = ' UNIQUE';

				break;
			case Database_Index::TYPE_PRIMARY:
				$columns = $index->columns();
				if (count($columns) === 1) {
					$column = $table->column(first($columns));
					$column_name = $this->quote_column($column->name());
					$column_sql = $column->sql_type();
					return "ALTER TABLE $table_name CHANGE $column_name $column_sql PRIMARY KEY";
				}
			// no break
			case Database_Index::TYPE_INDEX:
				break;
			default:
				throw new Exception_Invalid(__METHOD__ . "($table, $indexType, ...): Invalid index type $indexType");

				break;
		}
		$sqlIndexes = [];
		foreach ($indexes as $k => $size) {
			if (is_numeric($size)) {
				$sqlIndexes[] = $this->quote_column($k) . "($size)";
			} else {
				$sqlIndexes[] = $this->quote_column($k);
			}
		}
		return "CREATE$unique INDEX $quoted_name ON $table_name (" . implode(', ', $sqlIndexes) . ')';
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see zesk\Database::sql_alter_table_index_drop()
	 */
	public function alter_table_index_drop(Database_Table $table, Database_Index $index) {
		$name = $index->name();
		$name = $this->quote_column($name);
		switch ($indexType = $index->type()) {
			case Database_Index::TYPE_UNIQUE:
			case Database_Index::TYPE_INDEX:
			case Database_Index::TYPE_PRIMARY:
				return "DROP INDEX IF EXISTS $name";
			default:
				throw new Exception_Invalid(__METHOD__ . "($table, $indexType, ...): Invalid index type $indexType");
		}
	}

	public function alter_table_attributes(Database_Table $table, array $attributes) {
		return [];
	}

	public function alter_table_change_column(Database_Table $table, Database_Column $db_col_old, Database_Column $db_col_new) {
		$newType = $this->database_column_native_type($db_col_new);
		$previous_name = $db_col_old->name();
		$newName = $db_col_new->name();
		$suffix = $db_col_new->primary_key() ? ' FIRST' : '';

		$new_sql = 'ALTER TABLE ' . $this->quote_table($table) . ' CHANGE COLUMN ' . $this->quote_column($previous_name) . ' ' . $this->quote_column($newName) . " $newType $suffix";
		$old_table = $db_col_old->table();
		if ($db_col_new->primary_key() && $old_table->primary()) {
			return [
				$this->alter_table_index_drop($old_table, $old_table->primary()),
				$new_sql,
			];
		}
		return $new_sql;
	}

	/**
	 * Convert to/from Hex
	 *
	 * @param string $target
	 */
	public function function_hex($target) {
		throw new Exception_Unimplemented();
		return $target;
	}

	public function function_unhex($target) {
		throw new Exception_Unimplemented();
		return $target;
	}

	/**
	 * No table types in SQLite
	 */
	public function alter_table_type($table, $newType) {
		return [];
	}

	public function function_ip2long($value) {
		return "INET_ATON($value)";
	}

	public function remove_comments($sql) {
		$sql = Text::remove_line_comments($sql, '--');
		$sql = Text::remove_range_comments($sql);
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
				$sqlIndexes[] = $this->quote_column($k) . "($size)";
			} else {
				$sqlIndexes[] = $this->quote_column($k);
			}
		}
		return $sqlIndexes;
	}

	/**
	 *
	 * @param unknown $table
	 * @param unknown $name
	 * @param unknown $indexType
	 * @param unknown $indexes
	 * @throws Exception_Invalid
	 * @return string
	 */
	public function index_type($table, $name, $type, array $column_sizes) {
		switch ($type) {
			case Database_Index::TYPE_UNIQUE:
			case Database_Index::TYPE_INDEX:
				break;
			case Database_Index::TYPE_PRIMARY:
				$name = '';

				break;
			default:
				throw new Exception_Invalid(__METHOD__ . "($table, $name, $type, ...): Invalid index type {name}", compact('name'));
		}
		if ($name) {
			$name = $this->quote_column($name) . ' ';
		}
		return "$type $name(\n" . implode(",\n\t", $this->_sql_column_sizes_to_quoted_list($column_sizes)) . "\n)";
	}

	private function _sql_column_default($type, $default, $required = false) {
		$data_type = $this->database->data_type();
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
				$sql = StringTools::from_bool($default);

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
	public function function_compare_binary($column_name, $cmp, $string) {
		return "$column_name $cmp BINARY " . $this->sql_format_string($string);
	}

	public function function_abs($target) {
		return "ABS($target)";
	}

	public function function_average($target) {
		return "AVG($target)";
	}

	/*
	 * Date functions
	 */
	public function now() {
		return $this->database->sql_now();
	}

	public function now_utc() {
		return $this->database->sql_now_utc();
	}

	public function function_date_add($target, $number, $units = 'second'): void {
		throw new Exception_Unimplemented(__CLASS__ . '::' . __METHOD__);
		// 		$dbUnits = $this->_convert_units($number, $units);
		// 		return "DATE_ADD($target, INTERVAL $number $dbUnits)";
	}

	public function function_date_subtract($target, $number, $units = 'second'): void {
		throw new Exception_Unimplemented(__CLASS__ . '::' . __METHOD__);
		// 		$dbUnits = $this->_convert_units($number, $units);
		// 		return "DATE_SUB($target, INTERVAL $number $dbUnits)";
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $sql
	 * @return unknown
	 */
	public function sql_format_string($sql) {
		return $this->quote_text($sql);
	}

	/*
	 * Platform SQL Tools
	 */
	public function sql_table_as($table, $name = '') {
		if (empty($name)) {
			return $table;
		}
		return "$table AS $name";
	}

	public function sql_boolean($value) {
		return to_bool(value) ? 'true' : 'false';
	}

	/*
	 * Password Type
	 */
	public function sql_password($value) {
		return 'MD5(' . $this->sql_format_string($value) . ')';
	}

	/*
	 * Functions
	 */
	public function sql_function($func, $memberName, $alias = '') {
		$memberName = $this->quote_column($memberName);
		switch (strtolower(trim($func))) {
			case 'min':
				return $this->sql_table_as("MIN($memberName)", $alias);
			case 'max':
				return $this->sql_table_as("MAX($memberName)", $alias);
			case 'sum':
				return $this->sql_table_as("SUM($memberName)", $alias);
			case 'count':
				return $this->sql_table_as("COUNT($memberName)", $alias);
			case 'average':
				return $this->sql_table_as("AVG($memberName)", $alias);
			case 'stddev':
				return $this->sql_table_as("STDDEV($memberName)", $alias);
			case 'year':
				return $this->sql_table_as("YEAR($memberName)", $alias);
			case 'quarter':
				return $this->sql_table_as("QUARTER($memberName)", $alias);
			case 'month':
				return $this->sql_table_as("MONTH($memberName)", $alias);
			case 'day':
				return $this->sql_table_as("DAY($memberName)", $alias);
			case 'hour':
				return $this->sql_table_as("HOUR($memberName)", $alias);
			case 'minute':
				return $this->sql_table_as("MINUTE($memberName)", $alias);
			default:
				return false;
		}
	}

	/**
	 * Convert a Database_Column to a sql type for this database
	 *
	 * @param Database_Column $dbCol
	 * @return Ambigous <string, mixed, unknown, multitype:>
	 */
	private function database_column_native_type(Database_Column $dbCol) {
		$sql_type = $dbCol->sql_type();
		$sql = "$sql_type";
		if ($dbCol->primary_key()) {
			// Primary key should not be unsigned integer
			$sql .= ' PRIMARY KEY NOT NULL';
		} else {
			if ($dbCol->optionBool('unsigned')) {
				$sql .= ' unsigned';
			}
			$sql .= $dbCol->required() ? ' NOT NULL' : ' NULL';
			if ($dbCol->has_default_value() || $dbCol->required()) {
				$sql .= $this->_sql_column_default($sql_type, $dbCol->option('default'), $dbCol->required());
			}
		}
		if ($dbCol->has_extras()) {
			$sql .= $dbCol->extras();
		}
		return $sql;
	}

	public function drop_table($table) {
		if ($table instanceof Database_Table) {
			$table = $table->name();
		}
		$table = $this->quote_table($table);
		return [
			"DROP TABLE IF EXISTS $table",
		];
	}

	public function create_table(Database_Table $dbTableObject): void {
		throw new Exception_Unimplemented('Yup');
	}

	final public function column_is_quoted($column) {
		unquote($column, '""', $q);
		return $q === '"';
	}

	/**
	 *
	 * @return string
	 * @param string $table
	 */
	final public function quote_column($name) {
		if (is_array($name)) {
			foreach ($name as $index => $col) {
				$name[$index] = $this->quote_column($col);
			}
			return $name;
		}
		[$alias, $col] = pair($name, '.', null, $name);
		if ($alias) {
			return $this->quote_column($alias) . '.' . $this->quote_column($col);
		}
		return '"' . strtr($name, [
			'"' => '\\"',
		]) . '"';
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Database_SQL::quote_text()
	 */
	final public function quote_text($text) {
		return $this->database->native_quote_text($text);
	}

	/**
	 * Reverses, exactly, quote_column
	 *
	 * @param string $column
	 * @return string
	 */
	final public function unquote_column($column) {
		if (is_array($column)) {
			foreach ($column as $index => $col) {
				$column[$index] = $this->unquote_column($col);
			}
			return $column;
		}
		return strtr(unquote($column, self::sql_column_quotes), [
			'""' => '"',
			'``' => '`',
		]);
	}

	/**
	 *
	 * @param string $table
	 * @return string
	 */
	final public function quote_table($table) {
		return self::quote_column($table);
	}

	/**
	 *
	 * @param string $table
	 * @return string
	 */
	final public function unquote_table($table) {
		return self::unquote_column($table);
	}

	public function function_date_diff($date_a, $date_b) {
		throw new Exception_Unimplemented();
		return "TIMESTAMPDIFF(SECOND,$date_b,$date_a)";
	}

	/**
	 * @return array
	 */
	public function to_database(Model $object, array $data, $insert = false) {
		if ($object instanceof ORM) {
			/* @var $object ORM */
			if ($insert) {
				if (is_string($auto_column = $object->class_orm()->auto_column)) {
					unset($data[$auto_column]);
				}
			}
		}
		return $data;
	}
}
