<?php declare(strict_types=1);

namespace MySQL;

use zesk\Database_Table;
use zesk\Database_Column;
use zesk\Database_Index;
use zesk\Exception_Invalid;
use zesk\Exception_Semantics;
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
	public const SQL_FLUSH_PRIVILEGES = "FLUSH PRIVILEGES";

	/**
	 * Grant pattern
	 *
	 * @var string
	 */
	public const SQL_GRANT = "GRANT {privilege} ON {name}.{table} TO {user}@{from_host} IDENTIFIED BY '{pass}'";

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Database_SQL::alter_table_column_add()
	 */
	public function alter_table_column_add(Database_Table $table, Database_Column $db_col_new): string {
		$newName = $db_col_new->name();
		$newType = $this->database_column_native_type($db_col_new);
		$after_column = $db_col_new->option('after_column', false);
		return "ALTER TABLE " . $this->quote_table($table->name()) . " ADD COLUMN " . $this->quote_column($newName) . " $newType" . ($after_column ? " AFTER " . $this->quote_column($after_column) : "");
	}

	public function create_table(Database_Table $table) {
		$columns = $table->columns();
		$types = [];
		foreach ($columns as $dbCol) {
			if (!$dbCol->has_sql_type() && !$this->type_set_sql_type($dbCol)) {
				die(__METHOD__ . ": no SQL Type for column $dbCol");
			} else {
				$types[] = $this->quote_column($dbCol->name()) . " " . $this->database_column_native_type($dbCol, true);
			}
		}
		$indexes = $table->indexes();
		$alters = [];
		if ($indexes) {
			foreach ($indexes as $index) {
				/* @var $index Database_Index */
				$typeSQL = $index->sql_index_type();
				$alters[] = $index->sql_index_add();
			}
		}
		$types = implode(",\n\t", $types);
		$result = [];
		$result[] = "CREATE TABLE " . $this->quote_table($table->name()) . " (\n\t$types\n) ";

		return array_merge($result, $alters);
	}

	public function alter_table_index_add(Database_Table $t, Database_Index $index): string {
		$name = $index->name();
		$indexType = $index->type();
		$indexes = $index->column_sizes();
		$structure = $index->structure();
		$table = $t->name();
		switch ($indexType) {
			case Database_Index::Unique:
			case Database_Index::Index:
			case Database_Index::Primary:
				$sqlIndexes = [];
				foreach ($indexes as $k => $size) {
					if (is_numeric($size) && $size > 0) {
						$sqlIndexes[] = $this->quote_column($k) . "($size)";
					} else {
						$sqlIndexes[] = $this->quote_column($k);
					}
				}
				if ($indexType === Database_Index::Primary) {
					$name = "";
					$suffix = "";
				} else {
					$name = " " . $this->quote_column($name);
					$suffix = ($structure) ? " TYPE $structure" : "";
				}
				return "ALTER TABLE " . $this->quote_table($table) . " ADD $indexType$name$suffix (" . implode(", ", $sqlIndexes) . ")";
		}

		throw new Exception_Invalid("{class}::sql_alter_table_index_add({table}, {indexType}, ...): Invalid index type {indexType}", compact("indexType", "table") + ["class" => __CLASS__, ]);
	}

	public function alter_table_attributes(Database_Table $table, array $attributes): string {
		$defaults = $this->database->table_attributes();
		$attributes = $this->database->normalize_attributes($attributes);
		$attributes = ArrayTools::filter($attributes, array_keys($defaults)) + $defaults;
		foreach ($attributes as $type => $value) {
			$suffix[] = strtoupper($type) . "=$value";
		}
		return "ALTER TABLE " . $this->quote_table(strval($table)) . " " . implode(" ", $suffix);
	}

	public function alter_table_change_column(Database_Table $dbtable, Database_Column $db_col_old, Database_Column $db_col_new): string {
		$table = $dbtable->name();
		/*
		 * AUTO_INCREMENT/PRIMARY KEY definitions in MySQL are strict
		 *
		 * If a column is already AUTO_INCREMENT, then don't make it again
		 */
		$increment = $db_col_old->is_increment() === $db_col_new->is_increment() ? false : true;
		$primary = true;
		$increment = true;
		// 		if ($increment) {
		// 			if ($db_col_old->table()->primary()) {
		// 				$primary = false;
		// 			} else {
		// 				$increment = false;
		// 			}
		// 		}

		// OK to add primary key if no old primary key exists
		$primary = $db_col_old->table()->primary() === null;

		// OK to add increment column if no old increment column exists
		$increment = $db_col_old->is_increment() ? false : true;

		$newType = $this->database_column_native_type($db_col_new, $increment, $primary);
		$previous_name = $db_col_old->name();
		$newName = $db_col_new->name();
		$suffix = $db_col_new->primary_key() ? " FIRST" : "";

		$new_sql = "ALTER TABLE " . $this->quote_table($table) . " CHANGE COLUMN " . $this->quote_column($previous_name) . " " . $this->quote_column($newName) . " $newType $suffix";
		$old_table = $db_col_old->table();
		return trim($new_sql);
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see zesk\Database::sql_alter_table_column_drop()
	 */
	public function alter_table_column_drop(Database_Table $table, $dbColName): string {
		return "ALTER TABLE " . $this->quote_table($table->name()) . " DROP COLUMN " . $this->quote_column(strval($dbColName));
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see zesk\Database::sql_alter_table_index_drop()
	 */
	public function alter_table_index_drop(Database_Table $table, Database_Index $index): string {
		$table_name = $table->name();
		$table_name = $this->quote_table($table_name);
		$original_name = $index->name();
		$index_type = $index->type();
		$name = $this->quote_column($original_name);
		switch ($index_type) {
			case Database_Index::Unique:
			case Database_Index::Index:
				if (empty($original_name)) {
					throw new Exception_Semantics("{method} index for table {table} has no name, but is required", ["method" => __METHOD__, "table" => $table_name, ]);
				}
				return "ALTER TABLE $table_name DROP INDEX $name";
			case Database_Index::Primary:
				return "ALTER TABLE $table_name DROP PRIMARY KEY";
			default:
				throw new Exception_Invalid("{method}({table_name}, {index_type}, ...): Invalid index type {index_type}", ["method" => __METHOD__, "index_type" => $index_type, "table_name" => $table_name, ]);
		}
	}

	/**
	 * SQL command to alter a table type
	 *
	 * @param string $table
	 * @param string $newType
	 * @return string
	 */
	public function alter_table_type(string $table, string $newType): string {
		if (empty($newType)) {
			return [];
		}
		return "ALTER TABLE " . $this->quote_table($table) . " ENGINE=$newType";
	}

	/**
	 * MySQL update
	 * @see Database_SQL::update()
	 */
	public function update(array $options = []): string {
		// Support ignore
		$ignore_constraints = avalue($options, 'ignore_constraints', false);
		if ($ignore_constraints) {
			$options['update suffix'] = " IGNORE";
		}
		$low_priority = avalue($options, 'low_priority', false);
		if ($low_priority) {
			$options['update suffix'] = avalue($options, 'update suffix', '') . " LOW_PRIORITY";
		}
		return parent::update($options);
	}

	/**
	 * DELETE FROM table
	 * Support truncate
	 *
	 * @param array $options
	 * @return string
	 */
	public function delete(array $options): string {
		$table = $where = $truncate = null;
		extract($options, EXTR_IF_EXISTS);
		$where = $this->where($where);
		$verb = "DELETE FROM";
		if (empty($where)) {
			$verb = $truncate ? "TRUNCATE" : "DELETE FROM";
		}
		return "$verb " . $this->quote_table($table) . $where;
	}

	/**
	 * @param string $sql
	 * @return string
	 */
	public function remove_comments(string $sql): string {
		$sql = Text::remove_line_comments($sql, "--");
		$sql = Text::remove_line_comments($sql, "#");
		$sql = Text::remove_range_comments($sql, "/*", "*/");
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
	 * @return string
	 * @throws Exception_Invalid
	 */
	public function index_type(Database_Table $table, string $name, string $type, array $column_sizes): string {
		switch ($type) {
			case Database_Index::Unique:
			case Database_Index::Index:
				break;
			case Database_Index::Primary:
				$name = "";

				break;
			default:
				throw new Exception_Invalid("{method}($table, $name, $type, ...): Invalid index type {name}", compact("name") + ["method" => __METHOD__, ]);
		}
		if ($name) {
			$name = $this->quote_column($name) . " ";
		}
		return "$type $name(" . implode(", ", $this->_sql_column_sizes_to_quoted_list($column_sizes)) . ")";
	}

	/**
	 * @param $type
	 * @param $default
	 * @return string
	 */
	private function _sql_column_default($type, $default): string {
		$data_type = $this->database->data_type();
		switch (strtolower($type)) {
			case "timestamp":
				if (is_numeric($default)) { // Default 0 or Default "0" is no longer supported 2020-09-01
					return "";
				}
				if (is_string($default)) {
					if (strcasecmp($default, "null") === 0 || (strcasecmp($default, "CURRENT_TIMESTAMP") === 0)) {
						return " DEFAULT $default";
					}
				}
				if ($default === null) {
					return " DEFAULT NULL"; // KMD 2016-05-09 Was DEFAULT 0
				}
				return "";
			default:
				break;
		}
		$bt = $data_type->native_type_to_sql_type($type);
		switch ($bt) {
			case Database_Type::sql_type_text:
			case Database_Type::sql_type_blob:
				return "";
			case Database_Type::sql_type_double:
				return " DEFAULT " . floatval($default);
			case Database_Type::sql_type_integer:
				return " DEFAULT " . intval($default);
			case "boolean":
				// TODO This is probably not reachable
				$sql = StringTools::from_bool($default);
				break;
			default:
				$sql = $default;
				break;
		}
		if ($default === null) {
			return " DEFAULT NULL";
		}
		return " DEFAULT " . $this->sql_format_string($sql);
	}

	/*
	 * String Comparison
	 */
	public function function_compare_binary(string $column_name, string $cmp, string $string): string {
		return "$column_name $cmp BINARY " . $this->sql_format_string($string);
	}

	/*
	 * Date functions
	 */
	public function now(): string {
		return "NOW()";
	}

	public function now_utc(): string {
		return "UTC_TIMESTAMP()";
	}

	public function function_date_add(string $target, int $number, string $units = "second"): string {
		$dbUnits = $this->_convert_units($number, $units);
		return "DATE_ADD($target, INTERVAL $number $dbUnits)";
	}

	public function function_date_subtract(string $target, int $number, string $units = "second"): string {
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
	 * @param unknown $number
	 * @param unknown $units
	 * @return string
	 * @throws Exception_Semantics
	 */
	private function _convert_units(int &$number, string $units) {
		switch ($units) {
			case "millisecond":
				$number = intval($number / 1000);
				return "SECOND";
			case "second":
				return "SECOND";
			case "hour":
				return "HOUR";
			case "day":
				return "DAY";
			case "weekday":
				return "DAY";
			case "month":
				return "MONTH";
			case "quarter":
				$number = $number * 3;
				return "MONTH";
			case "year":
				return "YEAR";
			default:
				throw new Exception_Semantics(__METHOD__ . "($number, $units): Unknown time unit.");
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $sql
	 * @return unknown
	 */
	public function sql_format_string(string $sql): string {
		return "'" . addslashes($sql) . "'";
	}

	/*
	 * Platform SQL Tools
	 */
	public function sql_table_as(string $table, string $name = ""): string {
		if (empty($name)) {
			return $table;
		}
		return "$table AS $name";
	}

	public function sql_boolean($value): string {
		return to_bool($value) ? "1" : "0";
	}

	/*
	 * Password Type
	 */
	public function sql_password(string $value): string {
		return "MD5(" . $this->sql_format_string($value) . ")";
	}

	/*
	 * Functions
	 */
	public function sql_function(string $func, string $memberName, string $alias = ""): string {
		$memberName = $this->quote_column($memberName);
		switch (strtolower(trim($func))) {
			case "min":
				return $this->sql_table_as("MIN($memberName)", $alias);
			case "max":
				return $this->sql_table_as("MAX($memberName)", $alias);
			case "sum":
				return $this->sql_table_as("SUM($memberName)", $alias);
			case "count":
				return $this->sql_table_as("COUNT($memberName)", $alias);
			case "average":
				return $this->sql_table_as("AVG($memberName)", $alias);
			case "stddev":
				return $this->sql_table_as("STDDEV($memberName)", $alias);
			case "year":
				return $this->sql_table_as("YEAR($memberName)", $alias);
			case "quarter":
				return $this->sql_table_as("QUARTER($memberName)", $alias);
			case "month":
				return $this->sql_table_as("MONTH($memberName)", $alias);
			case "day":
				return $this->sql_table_as("DAY($memberName)", $alias);
			case "hour":
				return $this->sql_table_as("HOUR($memberName)", $alias);
			case "minute":
				return $this->sql_table_as("MINUTE($memberName)", $alias);
			default:
				return false;
		}
	}

	/**
	 * Convert a Database_Column to a sql type for this database
	 *
	 * @param Database_Column $dbCol
	 * @return string
	 */
	private function database_column_native_type(Database_Column $dbCol, $add_increment = true, $add_primary = true):
	string {
		$sql_type = $dbCol->sql_type();
		$sql = "$sql_type";
		if ($dbCol->optionBool("unsigned")) {
			$sql .= " unsigned";
		}
		if ($dbCol->is_text()) {
			if ($dbCol->hasOption(Database::attribute_character_set)) {
				$sql .= " CHARACTER SET " . $dbCol->option(Database::attribute_character_set);
			}
			if ($dbCol->hasOption(Database::attribute_collation)) {
				$sql .= " COLLATE " . $dbCol->option(Database::attribute_collation);
			}
		}
		if ($dbCol->is_increment() && $add_increment) {
			if ($dbCol->primary_key()) {
				$sql .= " AUTO_INCREMENT " . ($add_primary ? "PRIMARY KEY " : "") . "NOT NULL";
			} else {
				$sql .= " AUTO_INCREMENT NOT NULL";
			}
		} else {
			$sql .= $dbCol->required() ? " NOT NULL" : " NULL";
			if ($dbCol->has_default_value()) {
				$sql .= $this->_sql_column_default($sql_type, $dbCol->option("Default"));
			}
		}
		if ($dbCol->has_extras()) {
			$sql .= " " . $dbCol->extras();
		}
		return $sql;
	}

	public function drop_table($table) {
		if ($table instanceof Database_Table) {
			$table = $table->name();
		}
		$table = $this->quote_table($table);
		return ["DROP TABLE IF EXISTS $table", ];
	}

	/**
	 * @param string $name
	 * @return string
	 * @throws Exception_Semantics
	 */
	final public function quote_column(string $name): string {
		if (empty($name)) {
			throw new Exception_Semantics("Quoting blank column name");
		}
		[$alias, $col] = pair($name, ".", "", $name);
		if ($alias) {
			return $this->quote_column($alias) . "." . $this->quote_column($col);
		}
		return '`' . strtr($name, ["`" => "``", ]) . '`';
	}

	/**
	 * (non-PHPdoc)
	 *
	 * @see Database_SQL::quote_text()
	 */
	final public function quote_text(string $text): string {
		return $this->database->native_quote_text($text);
	}

	/**
	 * Reverses, exactly, quote_column
	 *
	 * @param string $column
	 * @return string
	 */
	final public function unquote_column(string $column): string {
		return strtr(unquote($column, '``'), ["``" => "`", ]);
	}

	/**
	 *
	 * @param string $table
	 * @return string
	 */
	final public function quote_table(string $table): string {
		return $this->quote_column($table);
	}

	/**
	 *
	 * @param string $table
	 * @return string
	 */
	final public function unquote_table(string $table): string {
		return $this->unquote_column($table);
	}

	public function function_date_diff(string $date_a, string $date_b): string {
		return "TIMESTAMPDIFF(SECOND,$date_b,$date_a)";
	}

	public function hook_schema(array $sql_list) {
		$alter_combine_prefixes = ["ALTER TABLE  ", ];
		foreach ($alter_combine_prefixes as $alter_combine_prefix) {
			$alters = [];
			foreach ($sql_list as $i => $sql) {
				if (begins($sql, $alter_combine_prefix)) {
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
		$members = ["user" => null, "pass" => null, "from_host" => "localhost", "tables" => "*", "privileges" => "ALL PRIVILEGES", "name" => "%", ];
		foreach ($members as $key => $default) {
			if (isset($options[$key]) && $options[$key] === self::SQL_GRANT_ALL) {
				$options[$key] = $default;
			}
			if (!isset($options[$key])) {
				$options[$key] = $this->option_path("grant.$key", $default);
			}
			if (empty($options[$key])) {
				unset($options[$key]);
			}
		}
		if (!isset($options['user']) || !isset($options['pass'])) {
			throw new Exception_Parameter("Need a user and pass option passed to {method}", ["method" => __METHOD__, ]);
		}
		$permutations = [$options, ];
		foreach (['tables' => 'table', 'privileges' => 'privilege', ] as $listable => $permute_key) {
			$permutations = $this->_permute($permutations, to_list($options[$listable]), $permute_key);
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
