<?php
declare(strict_types=1);

namespace MySQL;

use zesk\Database_Data_Type;
use zesk\Database_Table;
use zesk\Database_Column;
use zesk\Database_Index;
use zesk\Exception_Class_NotFound;
use zesk\Exception_Invalid;
use zesk\Exception_Key;
use zesk\Exception_NotFound;
use zesk\Exception_Semantics;
use zesk\Temporal;
use zesk\Text;
use zesk\ArrayTools;
use zesk\StringTools;
use zesk\Exception_Parameter;

/**
 * @see Database_Parser
 * @see Database_Type
 * @author kent
 *
 */
class Database_SQL extends \zesk\Database_SQL {
	/**
	 * Flush privileges string
	 *
	 * @var string
	 */
	public const SQL_FLUSH_PRIVILEGES = 'FLUSH PRIVILEGES';

	/**
	 * Grant pattern
	 *
	 * @var string
	 */
	public const SQL_GRANT = 'GRANT {privilege} ON {name}.{table} TO {user}@{from_host} IDENTIFIED BY \'{pass}\'';

	/**
	 *
	 * @param Database_Table $table
	 * @param Database_Column $addColumn
	 * @return string
	 * @throws Exception_Semantics
	 */
	public function alter_table_column_add(Database_Table $table, Database_Column $addColumn): string {
		$newName = $addColumn->name();
		$newType = $this->database_column_native_type($addColumn);
		$after_column = $addColumn->option('after_column', false);
		return 'ALTER TABLE ' . $this->quoteTable($table->name()) . ' ADD COLUMN ' . $this->quoteColumn($newName) . " $newType" . ($after_column ? ' AFTER ' . $this->quoteColumn($after_column) : '');
	}

	/**
	 * @param Database_Table $table
	 * @return array
	 * @throws Exception_Semantics
	 * @throws Exception_Key
	 */
	public function createTable(Database_Table $table): array {
		$columns = $table->columns();
		$types = [];
		$dataType = $table->database()->data_type();
		foreach ($columns as $dbCol) {
			if (!$dbCol->hasSQLType() && !$dataType->type_set_sql_type($dbCol)) {
				die(__METHOD__ . ": no SQL Type for column $dbCol");
			} else {
				$types[] = $this->quoteColumn($dbCol->name()) . ' ' . $this->database_column_native_type($dbCol, true);
			}
		}
		$indexes = $table->indexes();
		$alters = [];
		if ($indexes) {
			// MySQL includes PRIMARY KEY in definition if just one field and an auto-increment
			$skip_primary = false;
			$primary = $table->primary();
			if ($primary && $primary->columnCount() === 1 && $table->column(first($primary->columns()))->isIncrement()) {
				$skip_primary = true;
			}
			foreach ($indexes as $index) {
				/* @var $index Database_Index */
				if ($skip_primary && $index->isPrimary()) {
					continue;
				}
				$alters[] = $index->sql_index_add();
			}
		}
		$types = implode(",\n\t", $types);
		$result = [];
		$result[] = 'CREATE TABLE ' . $this->quoteTable($table->name()) . " (\n\t$types\n) ";

		return array_merge($result, $alters);
	}

	public function alter_table_index_add(Database_Table $table, Database_Index $index): string {
		$name = $index->name();
		$indexType = $index->type();
		$indexes = $index->columnSizes();
		$structure = $index->structure();
		$tableName = $table->name();
		switch ($indexType) {
			case Database_Index::TYPE_UNIQUE:
			case Database_Index::TYPE_INDEX:
			case Database_Index::TYPE_PRIMARY:
				$sqlIndexes = [];
				foreach ($indexes as $k => $size) {
					if (is_numeric($size) && $size > 0) {
						$sqlIndexes[] = $this->quoteColumn($k) . "($size)";
					} else {
						$sqlIndexes[] = $this->quoteColumn($k);
					}
				}
				if ($indexType === Database_Index::TYPE_PRIMARY) {
					$name = '';
					$suffix = '';
				} else {
					$name = ' ' . $this->quoteColumn($name);
					$suffix = ($structure) ? " TYPE $structure" : '';
				}
				return 'ALTER TABLE ' . $this->quoteTable($tableName) . " ADD $indexType$name$suffix (" . implode(', ', $sqlIndexes) . ')';
		}

		throw new Exception_Invalid(
			'{class}::sql_alter_table_index_add({tableName}, {indexType}, ...): ' .
				'Invalid index type {indexType}',
			[
				'indexType' => $indexType, 'tableName' => $tableName,
			] + ['class' => __CLASS__, ]
		);
	}

	public function alter_table_attributes(Database_Table $table, array $attributes): string {
		$defaults = $this->database->tableAttributes();
		$attributes = $this->database->normalizeAttributes($attributes);
		$attributes = ArrayTools::filter($attributes, array_keys($defaults)) + $defaults;
		foreach ($attributes as $type => $value) {
			$suffix[] = strtoupper($type) . "=$value";
		}
		return 'ALTER TABLE ' . $this->quoteTable(strval($table)) . ' ' . implode(' ', $suffix);
	}

	public function alter_table_change_column(Database_Table $table, Database_Column $oldColumn, Database_Column $newColumn): string {
		$tableName = $table->name();
		/*
		 * AUTO_INCREMENT/PRIMARY KEY definitions in MySQL are strict
		 *
		 * If a column is already AUTO_INCREMENT, then don't make it again
		 */
		$increment = $oldColumn->isIncrement() !== $newColumn->isIncrement();
		$primary = true;
		$increment = true;
		// 		if ($increment) {
		// 			if ($db_col_old->tableName()->primary()) {
		// 				$primary = false;
		// 			} else {
		// 				$increment = false;
		// 			}
		// 		}

		// OK to add primary key if no old primary key exists
		$primary = $oldColumn->table()->primary() === null;

		// OK to add increment column if no old increment column exists
		$increment = !$oldColumn->isIncrement();

		$newType = $this->database_column_native_type($newColumn, $increment, $primary);
		$previous_name = $oldColumn->name();
		$newName = $newColumn->name();
		$suffix = $newColumn->primaryKey() ? ' FIRST' : '';

		$new_sql = 'ALTER TABLE ' . $this->quoteTable($tableName) . ' CHANGE COLUMN ' . $this->quoteColumn($previous_name) . ' ' . $this->quoteColumn($newName) . " $newType $suffix";
		$old_table = $oldColumn->table();
		return trim($new_sql);
	}

	/**
	 * @param Database_Table $table
	 * @param string $columnNameName
	 * @return string
	 * @throws Exception_Semantics
	 */
	public function alter_table_column_drop(Database_Table $table, Database_Column|string $columnNameName): string {
		return 'ALTER TABLE ' . $this->quoteTable($table->name()) . ' DROP COLUMN ' . $this->quoteColumn(strval($columnNameName));
	}

	/**
	 * @param Database_Table $table
	 * @param Database_Index $index
	 * @return string
	 * @throws Exception_Invalid
	 * @throws Exception_Semantics
	 */
	public function alter_table_index_drop(Database_Table $table, Database_Index $index): string {
		$table_name = $table->name();
		$table_name = $this->quoteTable($table_name);
		$original_name = $index->name();
		$index_type = $index->type();
		$name = $this->quoteColumn($original_name);
		switch ($index_type) {
			case Database_Index::TYPE_UNIQUE:
			case Database_Index::TYPE_INDEX:
				if (empty($original_name)) {
					throw new Exception_Semantics('{method} index for table {table} has no name, but is required', [
						'method' => __METHOD__, 'table' => $table_name,
					]);
				}
				return "ALTER TABLE $table_name DROP INDEX $name";
			case Database_Index::TYPE_PRIMARY:
				return "ALTER TABLE $table_name DROP PRIMARY KEY";
			default:
				throw new Exception_Invalid('{method}({table_name}, {index_type}, ...): Invalid index type {index_type}', [
					'method' => __METHOD__, 'index_type' => $index_type, 'table_name' => $table_name,
				]);
		}
	}

	/**
	 * SQL command to alter a table type
	 *
	 * @param string $tableName
	 * @param string $newType
	 * @return string
	 */
	public function alter_table_type(string $tableName, string $newType): string {
		if (empty($newType)) {
			return '';
		}
		return 'ALTER TABLE ' . $this->quoteTable($tableName) . " ENGINE=$newType";
	}

	/**
	 * MySQL update
	 * @see Database_SQL::update()
	 */
	public function update(array $options = []): string {
		// Support ignore
		$ignore_constraints = $options['ignore_constraints'] ?? false;
		if ($ignore_constraints) {
			$options['update suffix'] = ' IGNORE';
		}
		$low_priority = $options['low_priority'] ?? false;
		if ($low_priority) {
			$options['update suffix'] ??= ' LOW_PRIORITY';
		}
		return parent::update($options);
	}

	/**
	 * DELETE FROM table
	 * Support truncate
	 *
	 * @param string $table
	 * @param array $where
	 * @param array $options
	 * @return string
	 */
	public function delete(string $table, array $where, array $options = []): string {
		$truncate = $options['truncate'] ?? false;
		$where = $this->where($where);
		$verb = 'DELETE FROM';
		if (empty($where)) {
			$verb = $truncate ? 'TRUNCATE' : 'DELETE FROM';
		}
		return "$verb " . $this->quoteTable($table) . $where;
	}

	/**
	 * @param string $sql
	 * @return string
	 */
	public function removeComments(string $sql): string {
		$sql = Text::removeLineComments($sql, '--');
		$sql = Text::removeLineComments($sql, '#');
		$sql = Text::removeRangeComments($sql, '/*', '*/');
		return $sql;
	}

	public function function_ip2long(string $value): string {
		return "INET_ATON($value)";
	}

	/**
	 * Convert an array of column => size to proper SQL syntax, adding quoting as needed.
	 *
	 * @param array $column_sizes
	 * @return array
	 */
	private function _sql_column_sizes_to_quoted_list(array $column_sizes): array {
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
	 * @param Database_Table $table
	 * @param string $name
	 * @param string $type
	 * @param array $column_sizes
	 * @return string
	 * @throws Exception_Invalid
	 * @throws Exception_Semantics
	 */
	public function index_type(Database_Table $table, string $name, string $type, array $column_sizes): string {
		switch ($type) {
			case Database_Index::TYPE_UNIQUE:
			case Database_Index::TYPE_INDEX:
				break;
			case Database_Index::TYPE_PRIMARY:
				$name = '';

				break;
			default:
				throw new Exception_Invalid("{method}($table, $name, $type, ...): Invalid index type {name}", compact('name') + ['method' => __METHOD__, ]);
		}
		if ($name) {
			$name = $this->quoteColumn($name) . ' ';
		}
		return "$type $name(" . implode(', ', $this->_sql_column_sizes_to_quoted_list($column_sizes)) . ')';
	}

	/**
	 * @param string $type
	 * @param mixed $default
	 * @return string
	 * @throws Exception_Class_NotFound
	 */
	private function _sql_column_default(string $type, mixed $default): string {
		if ($default === null) {
			return ' DEFAULT NULL'; // KMD 2016-05-09 Was DEFAULT 0
		}
		$data_type = $this->database->data_type();
		switch (strtolower($type)) {
			case 'timestamp':
				if (is_numeric($default)) { // Default 0 or Default "0" is no longer supported 2020-09-01
					return '';
				}
				if (is_string($default)) {
					if (strcasecmp($default, 'null') === 0 || (strcasecmp($default, 'CURRENT_TIMESTAMP') === 0)) {
						return " DEFAULT $default";
					}
				}
				return '';
			default:
				break;
		}
		$bt = $data_type->native_type_to_sql_type($type, $type);
		switch ($bt) {
			case Database_Data_Type::SQL_TYPE_TEXT:
			case Database_Data_Type::SQL_TYPE_BLOB:
				return '';
			case Database_Data_Type::SQL_TYPE_DOUBLE:
				return ' DEFAULT ' . floatval($default);
			case Database_Data_Type::SQL_TYPE_INTEGER:
				return ' DEFAULT ' . intval($default);
			case 'boolean':
				// TODO This is probably not reachable
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
	public function function_compare_binary(string $columnName, string $cmp, string $string): string {
		return "$columnName $cmp BINARY " . $this->sql_format_string($string);
	}

	/*
	 * Date functions
	 */
	public function now(): string {
		return 'NOW()';
	}

	public function nowUTC(): string {
		return 'UTC_TIMESTAMP()';
	}

	public function function_date_add(string $target, int $number, string $units = 'second'): string {
		$dbUnits = $this->_convert_units($number, $units);
		return "DATE_ADD($target, INTERVAL $number $dbUnits)";
	}

	public function function_date_subtract(string $target, int $number, string $units = 'second'): string {
		$dbUnits = $this->_convert_units($number, $units);
		return "DATE_SUB($target, INTERVAL $number $dbUnits)";
	}

	public function function_abs(string $target): string {
		return "ABS($target)";
	}

	public function function_average(string $target): string {
		return "AVG($target)";
	}

	public function function_unhex(string $target): string {
		return "UNHEX($target)";
	}

	public function function_hex(string $target): string {
		return "HEX($target)";
	}

	/**
	 * Internal function
	 *
	 * @param int $number
	 * @param string $units
	 * @return string
	 * @throws Exception_Semantics
	 */
	private function _convert_units(int &$number, string $units): string {
		switch ($units) {
			case Temporal::UNIT_MILLISECOND:
				$number = intval($number / 1000);
				return 'SECOND';
			case Temporal::UNIT_SECOND:
				return 'SECOND';
			case Temporal::UNIT_HOUR:
				return 'HOUR';
			case Temporal::UNIT_DAY:
			case Temporal::UNIT_WEEKDAY:
				return 'DAY';
			case Temporal::UNIT_MONTH:
				return 'MONTH';
			case Temporal::UNIT_QUARTER:
				$number = $number * 3;
				return 'MONTH';
			case Temporal::UNIT_YEAR:
				return 'YEAR';
			default:
				throw new Exception_Semantics(__METHOD__ . "($number, $units): Unknown time unit.");
		}
	}

	/**
	 * SQL text values are single quoted and addslashes-quoted
	 * @param string $sql
	 * @return string
	 */
	public function sql_format_string(string $sql): string {
		return '\'' . addslashes($sql) . '\'';
	}

	/**
	 * @param string $table
	 * @param string $name
	 * @return string
	 */
	/*
	 * Platform SQL Tools
	 */
	public function sql_table_as(string $table, string $name = ''): string {
		if (empty($name)) {
			return $table;
		}
		return "$table AS $name";
	}

	public function sql_boolean($value): string {
		return toBool($value) ? '1' : '0';
	}

	/*
	 * Password Type
	 */
	public function sql_password(string $value): string {
		return 'MD5(' . $this->sql_format_string($value) . ')';
	}

	/*
	 * Functions
	 */
	public function sql_function(string $func, string $memberName, string $alias = ''): string {
		$memberName = $this->quoteColumn($memberName);
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
				throw new Exception_NotFound('No such function {func} for member {member_name} in database {class}', [
					'func' => $func, 'member_name' => $memberName, 'class' => get_class($this),
				]);
		}
	}

	/**
	 * Convert a Database_Column to a sql type for this database
	 *
	 * @param Database_Column $dbCol
	 * @return string
	 */
	private function database_column_native_type(Database_Column $dbCol, $add_increment = true, $add_primary = true): string {
		$sql_type = $dbCol->sqlType();
		$sql = "$sql_type";
		if ($dbCol->optionBool('unsigned')) {
			$sql .= ' unsigned';
		}
		if ($dbCol->isText()) {
			if ($dbCol->hasOption(Database::ATTRIBUTE_CHARACTER_SET)) {
				$sql .= ' CHARACTER SET ' . $dbCol->option(Database::ATTRIBUTE_CHARACTER_SET);
			}
			if ($dbCol->hasOption(Database::ATTRIBUTE_COLLATION)) {
				$sql .= ' COLLATE ' . $dbCol->option(Database::ATTRIBUTE_COLLATION);
			}
		}
		if ($dbCol->isIncrement() && $add_increment) {
			if ($dbCol->primaryKey()) {
				$sql .= ' AUTO_INCREMENT ' . ($add_primary ? 'PRIMARY KEY ' : '') . 'NOT NULL';
			} else {
				$sql .= ' AUTO_INCREMENT NOT NULL';
			}
		} else {
			$sql .= $dbCol->required() ? ' NOT NULL' : ' NULL';
			if ($dbCol->hasDefaultValue()) {
				$sql .= $this->_sql_column_default($sql_type, $dbCol->defaultValue());
			}
		}
		if ($dbCol->hasExtras()) {
			$sql .= ' ' . $dbCol->extras();
		}
		return $sql;
	}

	public function drop_table(string $table): array {
		if ($table instanceof Database_Table) {
			$table = $table->name();
		}
		$table = $this->quoteTable($table);
		return ["DROP TABLE IF EXISTS $table", ];
	}

	/**
	 * @param string $name
	 * @return string
	 */
	final public function quoteColumn(string $name): string {
		[$alias, $col] = pair($name, '.', '', $name);
		if ($alias) {
			return $this->quoteColumn($alias) . '.' . $this->quoteColumn($col);
		}
		return '`' . strtr($name, ['`' => '``', ]) . '`';
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Database_SQL::quoteText()
	 */
	final public function quoteText(string $text): string {
		return $this->database->nativeQuoteText($text);
	}

	/**
	 * Reverses, exactly, quoteColumn
	 *
	 * @param string $column
	 * @return string
	 */
	final public function unquoteColumn(string $column): string {
		return strtr(unquote($column, '``'), ['``' => '`', ]);
	}

	/**
	 *
	 * @param string $table
	 * @return string
	 */
	final public function quoteTable(string $table): string {
		return $this->quoteColumn($table);
	}

	/**
	 *
	 * @param string $table
	 * @return string
	 */
	final public function unquoteTable(string $table): string {
		return $this->unquoteColumn($table);
	}

	public function function_date_diff(string $date_a, string $date_b): string {
		return "TIMESTAMPDIFF(SECOND,$date_b,$date_a)";
	}

	public function hook_schema(array $sql_list): array {
		$alter_combine_prefixes = ['ALTER TABLE  ', ];
		foreach ($alter_combine_prefixes as $alter_combine_prefix) {
			$alters = [];
			foreach ($sql_list as $i => $sql) {
				if (str_starts_with($sql, $alter_combine_prefix)) {
					$alters[] = substr($sql, strlen($alter_combine_prefix));
					unset($sql_list[$i]);
				}
			}
			if (count($alters)) {
				array_unshift($sql_list, $alter_combine_prefix . implode(",\n", $alters));
			}
		}
		$sql_list = ArrayTools::rtrim($sql_list, " \t\r\n;");
		return $sql_list;
	}

	/**
	 * Permute a list
	 *
	 * @param array $options_list
	 * @param array $list
	 * @param array $option_key
	 * @return array
	 */
	private function _permute(array $options_list, array $list, string $option_key): array {
		$new_list = [];
		foreach ($list as $item) {
			foreach ($options_list as $index => $options) {
				$options_list[$index] += [$option_key => $item, ];
			}
			$new_list = array_merge($new_list, $options_list);
		}
		return $new_list;
	}

	/**
	 * Returns statement or statements to grant user access to this database. Returns null if not supported.
	 *
	 * $options contains keys:
	 *
	 * user - Username
	 * pass - Password
	 * from_host - Allowed host to connect from. Defaults to "localhost". Override with `zesk\Command_SQL::grant::from_host` config setting.
	 * tables - Tables to grant privileges on. Defaults to "*" (All)
	 * privilege - Name of privilege to grant. Defaults to "*" (All)
	 * name - Database name
	 *
	 * @param array $options
	 * @return array
	 * @throws Exception_Parameter
	 */
	public function grant(array $options): array {
		$members = [
			'user' => null, 'pass' => null, 'from_host' => 'localhost', 'tables' => '*',
			'privileges' => 'ALL PRIVILEGES', 'name' => '%',
		];
		foreach ($members as $key => $default) {
			if (isset($options[$key]) && $options[$key] === self::SQL_GRANT_ALL) {
				$options[$key] = $default;
			}
			if (!isset($options[$key])) {
				$options[$key] = $this->optionPath(['grant', $key], $default);
			}
			if (empty($options[$key])) {
				unset($options[$key]);
			}
		}
		if (!isset($options['user']) || !isset($options['pass'])) {
			throw new Exception_Parameter('Need a user and pass option passed to {method}', ['method' => __METHOD__, ]);
		}
		$permutations = [$options, ];
		foreach (['tables' => 'table', 'privileges' => 'privilege', ] as $listable => $permute_key) {
			$permutations = $this->_permute($permutations, toList($options[$listable]), $permute_key);
		}
		$result = [];
		foreach ($permutations as $permute_options) {
			$result[] = map(self::SQL_GRANT, $permute_options);
		}
		if (count($result) > 0) {
			$result[] = self::SQL_FLUSH_PRIVILEGES;
		}
		return $result;
	}
}
