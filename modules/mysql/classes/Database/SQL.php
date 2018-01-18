<?php
namespace MySQL;

use zesk\Database_Table as Database_Table;
use zesk\Database_Column as Database_Column;
use zesk\Database_Index as Database_Index;
use zesk\Database_Exception_Schema as Database_Exception_Schema;
use zesk\Exception_Invalid;
use zesk\Exception_Semantics as Exception_Semantics;
use zesk\Text as text;
use zesk\ArrayTools as arr;
use zesk\StringTools as str;

/**
 * 
 * @author kent
 *
 */
class Database_SQL extends \zesk\Database_SQL {
	public function alter_table_column_add(Database_Table $table, Database_Column $db_col_new) {
		$newName = $db_col_new->name();
		$newType = $this->database_column_native_type($db_col_new);
		$after_column = $db_col_new->option('after_column', false);
		return "ALTER TABLE " . $this->quote_table($table) . " ADD COLUMN " . $this->quote_column($newName) . " $newType" . ($after_column ? " AFTER " . self::quote_column($after_column) : "");
	}
	function alter_table_index_add(Database_Table $t, Database_Index $index) {
		$name = $index->name();
		$indexType = $index->type();
		$indexes = $index->column_sizes();
		$structure = $index->structure();
		$table = $t->name();
		switch ($indexType) {
			case Database_Index::Unique:
			case Database_Index::Index:
			case Database_Index::Primary:
				$sqlIndexes = array();
				foreach ($indexes as $k => $size) {
					if (is_numeric($size)) {
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
		throw new Exception_Invalid("{class}::sql_alter_table_index_add({table}, {indexType}, ...): Invalid index type {indexType}", compact("indexType", "table") + array(
			"class" => __CLASS__
		));
	}
	function alter_table_attributes(Database_Table $table, array $attributes) {
		$defaults = $this->database->table_attributes();
		$attributes = $this->database->normalize_attributes($attributes);
		$attributes = ArrayTools::filter($attributes, array_keys($defaults)) + $defaults;
		foreach ($attributes as $type => $value) {
			$suffix[] = strtoupper($type) . "=$value";
		}
		return "ALTER TABLE " . $this->quote_table(strval($table)) . " " . implode(" ", $suffix);
	}
	function alter_table_change_column(Database_Table $dbtable, Database_Column $db_col_old, Database_Column $db_col_new) {
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
	public function alter_table_column_drop(Database_Table $table, $dbColName) {
		return "ALTER TABLE " . $this->quote_table($table->name()) . " DROP COLUMN " . $this->quote_column($dbColName);
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see zesk\Database::sql_alter_table_index_drop()
	 */
	function alter_table_index_drop(Database_Table $table, Database_Index $index) {
		$table_name = $table->name();
		$table_name = $this->quote_table($table_name);
		$original_name = $index->name();
		$index_type = $index->type();
		$name = $this->quote_column($original_name);
		switch ($index_type) {
			case Database_Index::Unique:
			case Database_Index::Index:
				if (empty($original_name)) {
					throw new Exception_Semantics("{method} index for table {table} has no name, but is required", array(
						"method" => __METHOD__,
						"table" => $table_name
					));
				}
				return "ALTER TABLE $table_name DROP INDEX $name";
			case Database_Index::Primary:
				return "ALTER TABLE $table_name DROP PRIMARY KEY";
			default :
				throw new Exception_Invalid("{method}({table_name}, {index_type}, ...): Invalid index type {index_type}", array(
					"method" => __METHOD__,
					"index_type" => $index_type,
					"table_name" => $table_name
				));
		}
	}
	
	/**
	 * SQL command to alter a table type
	 *
	 * @param string $table
	 * @param string $newType
	 * @return string
	 */
	function alter_table_type($table, $newType) {
		if (empty($newType)) {
			return array();
		}
		return "ALTER TABLE " . $this->quote_table($table) . " ENGINE=$newType";
	}
	final function column_is_quoted($column) {
		unquote($column, '``', $q);
		return $q === '`';
	}
	
	/*====================================================================================================================================*/
	private function native_table_type(Database_Table $table) {
		$engine = $table->option('engine', $this->database->default_engine());
		if (!empty($engine)) {
			return " ENGINE=$engine";
		}
		return "";
	}
	
	/**
	 * MySQL update
	 * @see Database_SQL::update()
	 */
	public function update(array $options = array()) {
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
	public function delete(array $options) {
		$table = $where = $truncate = null;
		extract($options, EXTR_IF_EXISTS);
		$where = $this->where($where);
		$verb = "DELETE FROM";
		if (empty($where)) {
			$verb = $truncate ? "TRUNCATE" : "DELETE FROM";
		}
		return "$verb " . $this->quote_table($table) . $where;
	}
	public function remove_comments($sql) {
		$sql = Text::remove_line_comments($sql, "--");
		$sql = Text::remove_line_comments($sql, "#");
		$sql = Text::remove_range_comments($sql, "/*", "*/");
		return $sql;
	}
	function function_ip2long($value) {
		return "INET_ATON($value)";
	}
	
	/**
	 * Convert an array of column => size to proper SQL syntax, adding quoting as needed.
	 *
	 * @param array $column_sizes
	 * @return array
	 */
	private function _sql_column_sizes_to_quoted_list(array $column_sizes) {
		$sqlIndexes = array();
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
			case Database_Index::Unique:
			case Database_Index::Index:
				break;
			case Database_Index::Primary:
				$name = "";
				break;
			default :
				throw new Exception_Invalid("{method}($table, $name, $type, ...): Invalid index type {name}", compact("name") + array(
					"method" => __METHOD__
				));
		}
		if ($name) {
			$name = $this->quote_column($name) . " ";
		}
		return "$type $name(" . implode(", ", $this->_sql_column_sizes_to_quoted_list($column_sizes)) . ")";
	}
	private function _sql_column_default($type, $default) {
		$data_type = $this->database->data_type();
		switch (strtolower($type)) {
			case "timestamp":
				if (is_numeric($default) || (strcasecmp($default, "CURRENT_TIMESTAMP") === 0)) {
					return " DEFAULT $default";
				}
				if ($default === null) {
					return " DEFAULT NULL"; // KMD 2016-05-09 Was DEFAULT 0
				}
			default :
				break;
		}
		if ($default === null) {
			return " DEFAULT NULL";
		}
		$bt = $data_type->native_type_to_sql_type($type);
		switch ($bt) {
			case "integer":
				return " DEFAULT " . intval($default);
			case "boolean":
				$sql = StringTools::from_bool($default);
				break;
			default :
				$sql = $default;
				break;
		}
		return " DEFAULT " . $this->sql_format_string($sql);
	}
	
	/*
	 * String Comparison
	 */
	function function_compare_binary($column_name, $cmp, $string) {
		return "$column_name $cmp BINARY " . $this->sql_format_string($string);
	}
	
	/*
	 * Date functions
	 */
	function now() {
		return "NOW()";
	}
	function now_utc() {
		return "UTC_TIMESTAMP()";
	}
	function function_date_add($target, $number, $units = "second") {
		$dbUnits = $this->_convert_units($number, $units);
		return "DATE_ADD($target, INTERVAL $number $dbUnits)";
	}
	function function_date_subtract($target, $number, $units = "second") {
		$dbUnits = $this->_convert_units($number, $units);
		return "DATE_SUB($target, INTERVAL $number $dbUnits)";
	}
	public function function_abs($target) {
		return "ABS($target)";
	}
	public function function_average($target) {
		return "AVG($target)";
	}
	public function function_unhex($target) {
		return "UNHEX($target)";
	}
	public function function_hex($target) {
		return "HEX($target)";
	}
	
	/**
	 * Internal function
	 *
	 * @param unknown $number
	 * @param unknown $units
	 * @throws Exception_Semantics
	 * @return string
	 */
	private function _convert_units(&$number, $units) {
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
			default :
				throw new Exception_Semantics(__METHOD__ . "($number, $units): Unknown time unit.");
		}
	}
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $sql
	 * @return unknown
	 */
	function sql_format_string($sql) {
		return "'" . addslashes($sql) . "'";
	}
	
	/*
	 * Platform SQL Tools
	 */
	function sql_table_as($table, $name = "") {
		if (empty($name)) {
			return $table;
		}
		return "$table AS $name";
	}
	function sql_boolean($value) {
		return to_bool(value) ? 1 : 0;
	}
	
	/*
	 * Password Type
	 */
	function sql_password($value) {
		return "MD5(" . $this->sql_format_string($value) . ")";
	}
	
	/*
	 * Functions
	 */
	function sql_function($func, $memberName, $alias = "") {
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
			default :
				return false;
		}
	}
	
	/**
	 * Convert a Database_Column to a sql type for this database
	 *
	 * @param Database_Column $dbCol
	 * @return Ambigous <string, mixed, unknown, multitype:>
	 */
	private function database_column_native_type(Database_Column $dbCol, $add_increment = true, $add_primary = true) {
		$sql_type = $dbCol->sql_type();
		$sql = "$sql_type";
		if ($dbCol->option_bool("unsigned")) {
			$sql .= " unsigned";
		}
		if ($dbCol->is_text()) {
			if ($dbCol->has_option(Database::attribute_character_set)) {
				$sql .= " CHARACTER SET " . $dbCol->option(Database::attribute_character_set);
			}
			if ($dbCol->has_option(Database::attribute_collation)) {
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
	function drop_table($table) {
		if ($table instanceof Database_Table) {
			$table = $table->name();
		}
		$table = $this->quote_table($table);
		return array(
			"DROP TABLE IF EXISTS $table"
		);
	}
	function create_table(Database_Table $table) {
		$columns = $table->columns();
		
		$types = array();
		foreach ($columns as $dbCol) {
			if (!$dbCol->has_sql_type() && !$this->type_set_sql_type($dbCol)) {
				die(__METHOD__ . ": no SQL Type for column $dbCol");
			} else {
				$types[] = $this->quote_column($dbCol->name()) . " " . $this->database_column_native_type($dbCol, true);
			}
		}
		$indexes = $table->indexes();
		$alters = array();
		if ($indexes) {
			foreach ($indexes as $index) {
				/* @var $index Database_Index */
				$typeSQL = $index->sql_index_type();
				if ($typeSQL) {
					if ($index->type() === Database_Index::Primary) {
						$columns = $index->columns();
						if (count($columns) === 1) {
							$col = $table->column($columns[0]);
							if (!$col) {
								throw new Database_Exception_Schema($this, null, "{col} does not exist as primary key in {table}", array(
									"col" => $col,
									"table" => $table->name()
								));
							}
							if ($col->primary_key()) {
								continue;
							}
						}
					}
					$types[] = $typeSQL;
				} else {
					$alters[] = $index->createSQL();
				}
			}
		}
		$types = implode(",\n\t", $types);
		$result = array();
		$result[] = "CREATE TABLE " . $this->quote_table($table->name()) . " (\n\t$types\n) " . $this->native_table_type($table);
		
		return array_merge($result, $alters);
	}
	/**
	 *
	 * @return string
	 * @param string $table
	 */
	final function quote_column($column) {
		if (is_array($column)) {
			foreach ($column as $index => $col) {
				$column[$index] = $this->quote_column($col);
			}
			return $column;
		}
		list($alias, $col) = pair($column, ".", null, $column);
		if ($alias) {
			return $this->quote_column($alias) . "." . $this->quote_column($col);
		}
		return '`' . strtr($column, array(
			"`" => "``"
		)) . '`';
	}
	
	/**
	 * (non-PHPdoc)
	 *
	 * @see Database_SQL::quote_text()
	 */
	final function quote_text($text) {
		return $this->database->native_quote_text($text);
	}
	/**
	 * Reverses, exactly, quote_column
	 *
	 * @param string $column
	 * @return string
	 */
	final function unquote_column($column) {
		if (is_array($column)) {
			foreach ($column as $index => $col) {
				$column[$index] = $this->unquote_column($col);
			}
			return $column;
		}
		return strtr(unquote($column, '``'), array(
			"``" => "`"
		));
	}
	
	/**
	 *
	 * @param string $table
	 * @return string
	 */
	final function quote_table($table) {
		return self::quote_column($table);
	}
	/**
	 *
	 * @param string $table
	 * @return string
	 */
	final function unquote_table($table) {
		return self::unquote_column($table);
	}
	public function function_date_diff($date_a, $date_b) {
		return "TIMESTAMPDIFF(SECOND,$date_b,$date_a)";
	}
	public function hook_schema(array $sql_list) {
		$alter_combine_prefixes = array(
			"ALTER TABLE  "
		);
		foreach ($alter_combine_prefixes as $alter_combine_prefix) {
			$alters = array();
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
}
