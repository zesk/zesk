<?php
/**
 *
 */
namespace zesk;

use \Iterator;

/**
 *
 * @author kent
 *
 */
abstract class Database_SQL extends Hookable {
	/**
	 *
	 * @var Database
	 */
	protected $database = null;

	/**
	 * Wildcard token used to grant all privileges
	 *
	 * @see grant
	 * @var string
	 */
	const SQL_GRANT_ALL = "*";

	/**
	 *
	 * @param Database $database
	 */
	public function __construct(Database $database) {
		parent::__construct($database->application);
		$this->database = $database;
	}

	/**
	 * Return a SQL statement to alter a table by adding a column
	 *
	 * @param Database_Table $table
	 *            The table to alter
	 * @param Database_Column $c
	 * @return string SQL statement to alter a table's definition
	 */
	public function alter_table_column_add(Database_Table $table, Database_Column $c) {
		$column = $c->name();
		$sqlType = $c->sql_type();
		return "ALTER TABLE $table ADD COLUMN $column $sqlType";
	}

	/**
	 * SQL to add an index to a table
	 *
	 * @param Database_Table $table
	 * @param Database_Index $index
	 * @return string|array
	 */
	abstract public function alter_table_index_add(Database_Table $table, Database_Index $index);

	/**
	 * Generate SQL to modify table attributes
	 * @param Database_Table $table
	 * @param array $attributes new attributes (database-specific)
	 * @return string|array
	 */
	abstract public function alter_table_attributes(Database_Table $table, array $attributes);

	/**
	 * Return a SQL statement to change a table's column to another type
	 *
	 * @param Database_Table $table
	 *            The table to alter
	 * @param Database_Column $old_column
	 * @param Database_Column $new_column
	 * @return string SQL statement to alter a table's definition
	 */
	abstract public function alter_table_change_column(Database_Table $table, Database_Column $old_column, Database_Column $new_column);

	/**
	 * Return a SQL statement to remove a table's column
	 *
	 * @param Database_Table $table
	 *        	The table to alter
	 * @param string $column
	 *        	The column to remove
	 * @return string SQL statement to alter a table's definition
	 */
	public function alter_table_column_drop(Database_Table $table, $column) {
		return "ALTER TABLE $table DROP COLUMN $column";
	}

	/**
	 * SQL to drop an index from a table
	 *
	 * @return string
	 * @param Database_Table $table
	 * @param Database_Index $index
	 */
	abstract public function alter_table_index_drop(Database_Table $table, Database_Index $index);

	/**
	 * @param Database_Table $table
	 * @param string $type
	 * @return string
	 */
	abstract public function alter_table_type($table, $type);

	/*====================================================================================================================================*/

	/**
	 * Return a SQL statement to create a table from the Database_Table object
	 *
	 * @param Database_Table $dbTableObject
	 * @return array SQL statements to "CREATE TABLE" and any related objects
	 */
	abstract public function create_table(Database_Table $dbTableObject);

	/*====================================================================================================================================*/

	/**
	 * Return a SQL statement to create a table from the Database_Table object
	 *
	 * @param Database_Table|string $mixed
	 * @return array SQL statement to "DROP TABLE" and any related objects
	 */
	abstract public function drop_table($mixed);

	/**
	 * Return SQL for function acting on memberName, optionally referenced as alias
	 *
	 * @param string $func
	 * @param string $memberName
	 * @param string $alias
	 */
	abstract public function sql_function($func, $memberName, $alias = "");

	/**
	 * Remove comments from SQL
	 *
	 * @param string $sql
	 * @return string
	 */
	abstract public function remove_comments($sql);

	/**
	 * Compatible
	 *
	 * @param string $value
	 * @return number
	 */
	public function function_ip2long($value) {
		return ip2long($value);
	}

	abstract public function index_type($table, $index, $type, array $column_sizes);

	/**
	 * Does a case-sensitive comparison (where comparison)
	 *
	 * @return string Expression to plug into SQL
	 * @param string $column_name
	 *        	Name of the column you are comparing
	 * @param string $cmp
	 *        	Standard comparison function from Zesk, such as =, <, <=, >, >=, LIKE
	 * @param string $string
	 *        	What you're comparing. This will be quoted in the resulting expression.
	 */
	abstract public function function_compare_binary($column_name, $cmp, $string);

	/**
	 * Does a case-sensitive comparison (where comparison)
	 *
	 * @return string Expression to plug into SQL
	 * @param string $date_a Date A
	 * @param string $date_b Date B
	 */
	abstract public function function_date_diff($date_a, $date_b);

	/**
	 * The SQL function to generate the current time in the current time zone
	 *
	 * @return string A SQL command for this database
	 */
	abstract public function now();

	/**
	 * The SQL function to generate the current time in the UTC time zone
	 *
	 * @return string A SQL command for this database
	 */
	abstract public function now_utc();

	/**
	 * DATE_ADD($target, INTERVAL 2 DAY) or whatever the SQL is required
	 *
	 * @param string $target
	 * @param integer $number
	 * @param string $unit
	 * @return string
	 */
	abstract public function function_date_add($target, $number, $unit = "second");

	/**
	 * Absolute value of a number
	 *
	 * @param string $target
	 */
	abstract public function function_abs($target);

	/**
	 * MAX(col) SQL
	 *
	 * @param string $target Column or expression
	 * @param bool $expression When true, $target is unquoted (careful!)
	 * @return string
	 */
	public function function_max($target, $expression = false) {
		return "MAX(" . ($expression ? $target : $this->quote_column($target)) . ")";
	}

	/**
	 * MIN(col) SQL
	 *
	 * @param string $target Column or expression
	 * @param bool $expression When true, $target is unquoted (careful!)
	 * @return string
	 */
	public function function_min($target, $expression = false) {
		return "MIN(" . ($expression ? $target : $this->quote_column($target)) . ")";
	}

	/**
	 * Average(col) SQL
	 *
	 * @param string $target
	 * @return string
	 */
	abstract public function function_average($target);

	/**
	 * Convert to hexadecimal
	 *
	 * @param string $target
	 * @return string
	 */
	abstract public function function_hex($target);

	/**
	 * Convert from hexadecimal
	 *
	 * @param string $target
	 * @return string
	 */
	abstract public function function_unhex($target);

	/**
	 * Date subtraction
	 *
	 * @param string $target
	 * @param integer $number
	 * @param string $unit
	 */
	abstract public function function_date_subtract($target, $number, $unit = "second");

	/**
	 * TABLE as ALIAS SQL
	 *
	 * @param string $table
	 * @param string $name
	 * @return string
	 */
	public function table_as($table, $name = "") {
		if (empty($name)) {
			return $this->quote_table($table);
		}
		return $this->quote_table($table) . " AS " . $this->quote_table($name);
	}

	/**
	 * DATABASE.TABLE as ALIAS SQL
	 *
	 * @param string $database
	 * @param string $table
	 * @param string $name
	 * @return string
	 */
	public function database_table_as($database, $table, $name = "") {
		$result = $this->quote_table($database) . "." . $this->quote_table($table);
		if (empty($name)) {
			return $result;
		}
		return $result . " AS " . $this->quote_table($name);
	}

	/**
	 * ALIAS.COLUMN SQL
	 *
	 * @param string $column
	 * @param string $alias
	 * @return string
	 */
	public function column_alias($column, $alias = "") {
		if (is_array($column)) {
			foreach ($column as $index => $col) {
				$column[$index] = $this->column_alias($col, $alias);
			}
			return $column;
		}
		$column = $column === '*' ? $column : $this->quote_column($column);
		return empty($alias) ? $column : $this->quote_column($alias) . "." . $column;
	}

	/**
	 * COLUMN AS ALIAS SQL
	 *
	 * @param string $column
	 * @param string $alias
	 * @return string
	 */
	public function column_as($column, $alias = "") {
		if (is_array($column)) {
			foreach ($column as $index => $col) {
				$column[$index] = $this->column_as($col, $alias);
			}
			return $column;
		}
		$column = $this->quote_column($column);
		return $column . (empty($alias) ? "" : " AS " . $this->quote_column($alias));
	}

	/**
	 * UPDATE
	 * "tableA AS A, tableB as B, tableC as C"
	 * WHERE A...
	 *
	 * @param array $tables
	 * @return string
	 */
	private function update_tables($tables) {
		if (is_string($tables)) {
			return $this->quote_table($tables);
		}
		$sql_phrases = array();
		foreach ($tables as $alias => $table) {
			$sql_phrases[] = $this->table_as($table, $alias);
		}
		return implode(", ", $sql_phrases);
	}

	/**
	 * Quote a table name
	 *
	 * @return string
	 * @param string $name
	 */
	abstract public function quote_table($name);

	/**
	 * Quote a text string
	 *
	 * @return string
	 * @param string $text
	 */
	abstract public function quote_text($text);

	/**
	 * Quote a column name
	 *
	 * @return string array
	 * @param string|array $name
	 */
	abstract public function quote_column($name);

	/**
	 * Reverses, exactly, quote_column
	 *
	 * @param string $name
	 * @return string
	 */
	abstract public function unquote_column($name);

	/**
	 * Unquote a table
	 *
	 * @param string $name
	 * @return string
	 */
	abstract public function unquote_table($name);

	/**
	 *
	 * @param string $key Column key to parse a conjunction from
	 * @param string $conjunction "AND" or "OR"
	 * @return array
	 */
	private static function parse_conjunction($key, $conjunction) {
		foreach (array(
			"AND",
			"OR",
		) as $token) {
			if (StringTools::ends($key, "|$token", true)) {
				return array(
					substr($key, 0, -(strlen($token) + 1)),
					$token,
				);
			}
		}
		return array(
			$key,
			$conjunction === "AND" ? "OR" : "AND",
		);
	}

	/**
	 * Prefix a where clause with the WHERE keyword, if there is one.
	 * If not, return the blank string.
	 *
	 * @param string $sql
	 *        	where clause
	 * @return string
	 */
	protected function where_prefix($sql) {
		return $this->_sql_prefix($sql, "WHERE");
	}

	/**
	 * Prefix a where clause with the WHERE keyword, if there is one.
	 * If not, return the blank string.
	 *
	 * @param string $sql
	 *        	where clause
	 * @return string
	 */
	protected function having_prefix($sql) {
		return $this->_sql_prefix($sql, "HAVING");
	}

	/**
	 * Prefix a where clause with a keyword, if there is one.
	 * If not, return the blank string.
	 *
	 * @param string $sql
	 * @param string $prefix
	 * @return string
	 */
	private function _sql_prefix($sql, $prefix) {
		$sql = trim($sql);
		if (empty($sql)) {
			return "";
		}
		return " $prefix $sql ";
	}

	/**
	 * Where clause generation.
	 * Does not include the "WHERE" string, just
	 * expr conj expr
	 *
	 * @param array $arr
	 *            Where clause as $key => $value, has special meanings when $key begins with *
	 * @param string $conj
	 *            The conjunction to generate the where clause
	 * @param string $prefix_in
	 *            Prefix used
	 * @param string $suffix
	 * @return string
	 * @throws Exception_Unimplemented
	 */
	public function where_clause($arr, $conj = null, $prefix_in = "", $suffix = "") {
		if (!is_array($arr) || count($arr) === 0) {
			return "";
		}
		if (!is_string($conj)) {
			$conj = "AND";
		}
		$prefix = empty($prefix_in) ? "" : $prefix_in . ".";
		$result = array();
		foreach ($arr as $k => $v) {
			if (is_numeric($k) && is_string($v) || $k === "") {
				$result[] = $v;

				continue;
			}
			$new_key = $k;
			if (strpos($new_key, ".") === false) {
				if (substr($new_key, 0, 1) === '*') {
					$new_key = '*' . $prefix . substr($new_key, 1);
				} else {
					$new_key = $prefix . $new_key;
				}
			}
			if (is_array($v) || $v instanceof Iterator) {
				if (is_numeric($k)) {
					$result[] = "(" . $this->where_clause($v, $conj === "AND" ? "OR" : "AND", $prefix_in) . ")";
				} elseif (count($v) === 0) {
					$result[] = $this->quote_column($new_key) . " IS NULL";
				} else {
					$conj_sql = array();
					list($new_key, $new_conj) = $this->parse_conjunction($new_key, $conj);
					foreach ($v as $vv) {
						$conj_sql[] = $this->pair_to_sql($new_key, $vv, true);
					}
					if (count($conj_sql) > 0) {
						$result[] = "(" . implode(" $new_conj ", $conj_sql) . ")";
					}
				}
			} else {
				$result[] = $this->pair_to_sql($new_key, $v, true);
			}
		}
		if (count($result) === 0) {
			return "";
		}
		return implode(" " . trim($conj) . " ", $result) . ($suffix ? " $suffix " : "");
	}

	/**
	 * Where clause generation
	 *
	 * @param array $where
	 *            Where clause
	 * @param string $conj
	 * @param string $prefix
	 * @return string
	 * @throws Exception_Unimplemented
	 */
	public function where($where, $conj = null, $prefix = "") {
		return $this->where_prefix($this->where_clause($where, $conj, $prefix));
	}

	/**
	 * Having clause generation
	 *
	 * @param array $having Having clause
	 * @param string $conj
	 * @param string $prefix
	 * @return string
	 * @throws Exception_Unimplemented
	 */
	public function having(array $having, $conj = null, $prefix = "") {
		return $this->having_prefix($this->where_clause($having, $conj, $prefix));
	}

	/**
	 * Join clause
	 *
	 * @param array $joins
	 * @return string
	 */
	private function join($joins) {
		if (!is_array($joins) || count($joins) === 0) {
			return "\n";
		}
		return "\n" . implode("\n", $joins) . "\n";
	}

	/**
	 * Compute what clause
	 *
	 * @param mixed $what
	 * @param string $distinct
	 * @return string
	 */
	private function what($what, $distinct = null) {
		if (is_array($what)) {
			$result = array();
			foreach ($what as $as => $select_column) {
				if (is_numeric($as)) {
					$result[] = $this->quote_column($select_column);
				} else {
					$literal = ($as[0] === '*');
					if ($literal) {
						$result[] = "$select_column AS " . $this->quote_column(substr($as, 1));
					} else {
						$result[] = $this->column_as($select_column, $as);
					}
				}
			}
			$what = implode(", ", $result);
		}
		$distinct = ($distinct ? "DISTINCT " : "");
		return $distinct . strval($what);
	}

	/**
	 * Update SQL
	 *
	 * Child classes should augment by adding prefixes/suffixes as needed.
	 *
	 * @param array $options
	 * @return string
	 * @throws Exception_Unimplemented
	 */
	public function update(array $options = array()) {
		$table = null;
		$values = array();
		$where = array();
		extract($options, EXTR_IF_EXISTS);
		$name_equals_values = array();
		foreach ($values as $k => $v) {
			// TODO, This allows for | keys, which it shouldn't allow.
			$name_equals_values[] = $this->pair_to_sql($k, $v);
		}
		$options += array(
			'prefix' => '',
			'update prefix' => '',
			'update suffix' => '',
			'table prefix' => '',
			'table suffix' => '',
			'set prefix' => '',
			'set suffix' => '',
			'values prefix' => '',
			'values suffix' => '',
			'where prefix' => '',
			'where suffix' => '',
			'suffix' => '',
		);
		$sql = "{prefix}{update prefix}UPDATE{update suffix} {table prefix}";
		$sql .= $this->update_tables($table) . "{table suffix} {set prefix}SET{set suffix}\n\t{values prefix}";
		$sql .= implode(",\n\t", $name_equals_values) . "{values suffix}\t{where prefix}" . $this->where($where) . "{where suffix}{suffix}";
		return trim(map($sql, $options));
	}

	/**
	 * Select query
	 *
	 * @param array $options
	 * @return string
	 * @throws Exception_Unimplemented
	 */
	public function select(array $options) {
		$what = $distinct = $tables = $where = $group_by = $order_by = null;
		$offset = $limit = 0;
		$having = array();
		extract($options, EXTR_IF_EXISTS);
		$alias = null;
		$where = $this->where($where, null, $alias);
		if (is_string($tables)) {
			$sql_tables = $this->quote_table($tables);
		} elseif (is_array($tables)) {
			$alias = key($tables);
			$sql_tables = $this->table_as(array_shift($tables), $alias);
			$sql_tables .= $this->join($tables);
			$where = ltrim($where);
		} else {
			$sql_tables = (string) $tables;
		}
		$sql = "SELECT " . $this->what($what, $distinct) . " FROM " . $sql_tables . $where . $this->group_by($group_by) . $this->having($having) . $this->order_by($order_by) . $this->limit($offset, $limit);
		return trim($sql);
	}

	/**
	 * Generic INSERT formatting code
	 *
	 * Pass 'table', 'values' and 'low_priority'
	 *
	 * Database extensions may exist (e.g. REPLACE INTO) and so use additional options to handle
	 * those
	 *
	 * @param array $options
	 * @return string
	 * @throws Exception_Unimplemented
	 */
	public function insert(array $options) {
		$verb = "INSERT";
		$table = $values = $low_priority = null;
		extract($options, EXTR_IF_EXISTS);
		list($insert_names, $insert_values) = $this->_insert_to_name_values($values);
		$low_priority = $low_priority ? " LOW_PRIORITY" : "";
		return "$verb $low_priority INTO " . $this->quote_table($table) . " (\n\t`" . implode("`,\n\t`", $insert_names) . "`\n) VALUES (\n\t" . implode(",\n\t", $insert_values) . "\n)";
	}

	/**
	 * INSERT INTO table SELECT foo .
	 *
	 * ..
	 *
	 * @param array $options
	 * @return string
	 * @throws Exception_Unimplemented
	 */
	public function insert_select(array $options) {
		$verb = "INSERT";
		$table = $values = $low_priority = $select = null;
		extract($options, EXTR_IF_EXISTS);
		list($insert_name) = $this->_insert_to_name_values($values);
		$low_priority = $low_priority ? " LOW_PRIORITY" : "";
		return "$verb $low_priority INTO " . $this->quote_table($table) . " (\n\t`" . implode("`,\n\t`", $insert_name) . "`\n) $select";
	}

	/**
	 * DELETE FROM table
	 *
	 * @param array $options
	 * @return string
	 * @throws Exception_Unimplemented
	 */
	public function delete(array $options) {
		$table = $where = null;
		extract($options, EXTR_IF_EXISTS);
		return "DELETE FROM " . $this->quote_table($table) . $this->where($where);
	}

	/**
	 * Convert pair to insert name/value pair
	 *
	 * @param array $arr
	 * @throws Exception_Unimplemented
	 * @return array(array("name0","name1",..."),array("value0","value1",...))
	 */
	private function _insert_to_name_values($arr) {
		$insert_names = array();
		$insert_values = array();
		foreach ($arr as $k => $v) {
			if (substr($k, 0, 1) === '*') {
				$insert_names[] = substr($k, 1);
				$insert_values[] = $v;
			} else {
				$insert_names[] = $k;
				$insert_values[] = $this->mixed_to_sql($v);
			}
		}
		return array(
			$insert_names,
			$insert_values,
		);
	}

	/**
	 * GROUP BY SQL
	 *
	 * @param string|array $s
	 * @return string
	 */
	public function group_by($s) {
		if (is_array($s)) {
			$s = implode(", ", $s);
		}
		$s = trim($s);
		if (empty($s)) {
			return "";
		}
		return " GROUP BY $s";
	}

	/**
	 * ORDER BY clause
	 *
	 * @param string $s
	 * @param string $prefix
	 * @return string
	 */
	public function order_by($s, $prefix = "") {
		if (empty($s)) {
			return "";
		}
		if (is_string($s)) {
			if (strpos($s, ";") !== false) {
				$s = explode(";", $s);
			}
		}
		if (!is_array($s)) {
			$s = array(
				$s,
			);
		}
		$r = array();
		foreach ($s as $oby) {
			if ($oby[0] === '-') {
				$oby = substr($oby, 1) . " DESC";
			}
			if (strpos($oby, ".") === false) {
				if (!empty($prefix)) {
					$oby = "$prefix.$oby";
				}
			}
			$r[] = $oby;
		}
		return " ORDER BY " . implode(", ", $r);
	}

	/**
	 * LIMIT clause.
	 *
	 * @param integer $offset
	 * @param integer $limit
	 * @return string
	 */
	private function limit($offset = 0, $limit = -1) {
		if ($offset == 0) {
			if ($limit <= 0) {
				return "";
			} else {
				return " LIMIT $limit";
			}
		} elseif ($limit <= 0) {
			return " LIMIT $offset,";
		} else {
			return " LIMIT $offset,$limit";
		}
	}

	/**
	 * Convert name value pair to SQL, either as a comparison clause, or
	 *
	 * @param string $k
	 * @param mixed $v
	 * @param boolean $is_compare
	 * @return string
	 * @throws Exception_Unimplemented
	 */
	protected function pair_to_sql($k, $v, $is_compare = false) {
		list($k, $cmp) = pair($k, "|", $k, "=");
		if ($k[0] === '*') {
			if ($v === null) {
				return substr($k, 1) . " IS " . (($cmp === '!=') ? "NOT " : '') . "NULL";
			}
			return substr($k, 1) . "$cmp$v";
		} elseif ($v === null && $is_compare) {
			if ($cmp === "!=") {
				return $this->quote_column($k) . " IS NOT NULL";
			} elseif ($cmp === "=") {
				return $this->quote_column($k) . " IS NULL";
			} else {
				return $this->quote_column($k) . " IS NULL";
			}
		} elseif ($cmp === '%') {
			return $this->quote_column($k) . " LIKE " . $this->quote_text("%$v%");
		} elseif ($cmp === '!%') {
			return $this->quote_column($k) . " NOT LIKE " . $this->quote_text("%$v%");
		} else {
			return $this->quote_column($k) . " $cmp " . $this->mixed_to_sql($v);
		}
	}

	/**
	 * Convert a value to a SQL value
	 *
	 * @param mixed $v
	 * @return string
	 * @throws Exception_Unimplemented
	 */
	protected function mixed_to_sql($v) {
		if ($v === null) {
			return "NULL";
		}
		if (is_string($v)) {
			if (strlen($v) === 0) {
				return "''";
			}
			return $this->quote_text($v);
		} elseif (is_bool($v)) {
			return $v ? "1" : "0";
		} elseif ($v instanceof Model) {
			return $v->id();
		} elseif (is_object($v)) {
			if ($v instanceof Iterator) {
				backtrace();
			}
			return $this->quote_text($v->__toString());
		} elseif (is_numeric($v)) {
			if ($v === INF) {
				return "'1e500'";
			} elseif ($v === -INF) {
				return "'-1e500'";
			}
		}
		return $v;
	}

	/**
	 * @param Model $object
	 * @param array $data
	 * @param bool $insert
	 * @return array
	 */
	public function to_database(Model $object, array $data, $insert = false) {
		return $data;
	}

	/**
	 * @param Model $object
	 * @param array $data
	 * @return array
	 */
	public function from_database(Model $object, array $data) {
		return $data;
	}

	/**
	 * Returns statement or statements to grant user access to this database. Returns null if not supported.
	 *
	 * $options contains keys:
	 *
	 * user - Username
	 * password - Password
	 * host - Allowed host to connect from. Defaults to "localhost". Override with `zesk\Command_SQL::grant::host` config setting.
	 * tables - Tables to grant privileges on. Defaults to "*" (All)
	 * privilege - Name of privilege to grant. Defaults to "*" (All)
	 * name - Database name
	 *
	 * @param array $options
	 * @return array|null
	 */
	public function grant(array $options) {
		return null;
	}
}
