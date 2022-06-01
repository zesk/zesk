<?php
declare(strict_types=1);
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
	protected Database $database;

	/**
	 *
	 */
	public const CONJUNCTION_OR = 'OR';

	/**
	 *
	 */
	public const CONJUNCTION_AND = 'AND';

	/**
	 * Wildcard token used to grant all privileges
	 *
	 * @see grant
	 * @var string
	 */
	public const SQL_GRANT_ALL = '*';

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
	public function alter_table_column_add(Database_Table $table, Database_Column $c): string {
		$column = $c->name();
		$sqlType = $c->sqlType();
		return "ALTER TABLE $table ADD COLUMN $column $sqlType";
	}

	/**
	 * SQL to add an index to a table
	 *
	 * @param Database_Table $table
	 * @param Database_Index $index
	 * @return string|array
	 */
	abstract public function alter_table_index_add(Database_Table $table, Database_Index $index): string;

	/**
	 * Generate SQL to modify table attributes
	 * @param Database_Table $table
	 * @param array $attributes new attributes (database-specific)
	 * @return string|array
	 */
	abstract public function alter_table_attributes(Database_Table $table, array $attributes): string;

	/**
	 * Return a SQL statement to change a table's column to another type
	 *
	 * @param Database_Table $table
	 *            The table to alter
	 * @param Database_Column $old_column
	 * @param Database_Column $new_column
	 * @return string SQL statement to alter a table's definition
	 */
	abstract public function alter_table_change_column(Database_Table $table, Database_Column $old_column, Database_Column $new_column): string;

	/**
	 * Return a SQL statement to remove a table's column
	 *
	 * @param Database_Table $table
	 *            The table to alter
	 * @param string $column
	 *            The column to remove
	 * @return string SQL statement to alter a table's definition
	 */
	public function alter_table_column_drop(Database_Table $table, $column): string {
		return "ALTER TABLE $table DROP COLUMN $column";
	}

	/**
	 * SQL to drop an index from a table
	 *
	 * @param Database_Table $table
	 * @param Database_Index $index
	 * @return string
	 */
	abstract public function alter_table_index_drop(Database_Table $table, Database_Index $index): string;

	/**
	 * @param Database_Table $table
	 * @param string $type
	 * @return string
	 */
	abstract public function alter_table_type(string $table, string $newType): string;

	/*====================================================================================================================================*/

	/**
	 * Return a SQL statement to create a table from the Database_Table object
	 *
	 * @param Database_Table $dbTableObject
	 * @return array SQL statements to "CREATE TABLE" and any related objects
	 */
	abstract public function createTable(Database_Table $dbTableObject);

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
	abstract public function sql_function(string $func, string $memberName, string $alias = ''): string;

	/**
	 * Remove comments from SQL
	 *
	 * @param string $sql
	 * @return string
	 */
	abstract public function removeComments(string $sql): string;

	/**
	 * Compatible
	 *
	 * @param string $value
	 * @return number
	 */
	public function function_ip2long(string $value): string {
		return strval(ip2long($value));
	}

	abstract public function index_type(Database_Table $table, string $name, string $type, array $column_sizes): string;

	/**
	 * Does a case-sensitive comparison (where comparison)
	 *
	 * @param string $column_name
	 *            Name of the column you are comparing
	 * @param string $cmp
	 *            Standard comparison function from Zesk, such as =, <, <=, >, >=, LIKE
	 * @param string $string
	 *            What you're comparing. This will be quoted in the resulting expression.
	 * @return string Expression to plug into SQL
	 */
	abstract public function function_compare_binary(string $column_name, string $cmp, string $string): string;

	/**
	 * Does a case-sensitive comparison (where comparison)
	 *
	 * @param string $date_a Date A
	 * @param string $date_b Date B
	 * @return string Expression to plug into SQL
	 */
	abstract public function function_date_diff(string $date_a, string $date_b): string;

	/**
	 * Absolute value of a number
	 *
	 * @param string $target
	 */
	abstract public function function_abs(string $target): string;

	/**
	 * MAX(col) SQL
	 *
	 * @param string $target Column or expression
	 * @param bool $expression When true, $target is unquoted (careful!)
	 * @return string
	 */
	public function function_max(string $target, bool $expression = false): string {
		return 'MAX(' . ($expression ? $target : $this->quoteColumn($target)) . ')';
	}

	/**
	 * MIN(col) SQL
	 *
	 * @param string $target Column or expression
	 * @param bool $expression When true, $target is unquoted (careful!)
	 * @return string
	 */
	public function function_min(string $target, bool $expression = false): string {
		return 'MIN(' . ($expression ? $target : $this->quoteColumn($target)) . ')';
	}

	/**
	 * Average(col) SQL
	 *
	 * @param string $target
	 * @return string
	 */
	abstract public function function_average(string $target): string;

	/**
	 * Convert to hexadecimal
	 *
	 * @param string $target
	 * @return string
	 */
	abstract public function function_hex(string $target): string;

	/**
	 * Convert from hexadecimal
	 *
	 * @param string $target
	 * @return string
	 */
	abstract public function function_unhex(string $target): string;

	/**
	 * Date addition
	 *
	 * @param string $target
	 * @param int $number
	 * @param string $unit
	 */
	abstract public function function_date_add(string $target, int $number, string $units = 'second'): string;

	/**
	 * Date subtraction
	 *
	 * @param string $target
	 * @param int $number
	 * @param string $unit
	 */
	abstract public function function_date_subtract(string $target, int $number, string $unit = 'second'): string;

	/**
	 * The SQL function to generate the current time in the current time zone
	 *
	 * @return string A SQL command for this database
	 */
	abstract public function now(): string;

	/**
	 * The SQL function to generate the current time in the UTC time zone
	 *
	 * @return string A SQL command for this database
	 */
	abstract public function now_utc(): string;

	/**
	 * TABLE as ALIAS SQL
	 *
	 * @param string $table
	 * @param string $name
	 * @return string
	 */
	public function table_as(string $table, string $name = ''): string {
		if (empty($name)) {
			return $this->quoteTable($table);
		}
		return $this->quoteTable($table) . ' AS ' . $this->quoteTable($name);
	}

	/**
	 * DATABASE.TABLE as ALIAS SQL
	 *
	 * @param string $database
	 * @param string $table
	 * @param string $name
	 * @return string
	 */
	public function database_table_as(string $database, string $table, string $name = ''): string {
		$result = $this->quoteTable($database) . '.' . $this->quoteTable($table);
		if (empty($name)) {
			return $result;
		}
		return $result . ' AS ' . $this->quoteTable($name);
	}

	/**
	 * ALIAS.COLUMN SQL
	 *
	 * @param string $column
	 * @param string $alias
	 * @return string
	 */
	public function columnAlias(string $column, string $alias = ''): string {
		$column = $column === '*' ? $column : $this->quoteColumn($column);
		return empty($alias) ? $column : $this->quoteColumn($alias) . '.' . $column;
	}

	/**
	 * COLUMN AS ALIAS SQL
	 *
	 * @param string $column
	 * @param string $alias
	 * @return string
	 */
	public function columnAs(string $column, string $alias = ''): string {
		$column = $this->quoteColumn($column);
		return $column . (empty($alias) ? '' : ' AS ' . $this->quoteColumn($alias));
	}

	/**
	 * UPDATE
	 * "tableA AS A, tableB as B, tableC as C"
	 * WHERE A...
	 *
	 * @param array $tables
	 * @return string
	 */
	private function update_tables(array $tables): string {
		$sql_phrases = [];
		foreach ($tables as $alias => $table) {
			$sql_phrases[] = $this->table_as($table, $alias);
		}
		return implode(', ', $sql_phrases);
	}

	/**
	 * Quote a table name
	 *
	 * @param string $name
	 * @return string
	 */
	abstract public function quoteTable(string $name): string;

	/**
	 * Quote a text string
	 *
	 * @param string $text
	 * @return string
	 */
	abstract public function quoteText(string $text): string;

	/**
	 * Quote a column name
	 *
	 * @param string|array $name
	 * @return string array
	 */
	abstract public function quoteColumn(string $name): string;

	/**
	 * Reverses, exactly, quoteColumn
	 *
	 * @param string $name
	 * @return string
	 */
	abstract public function unquoteColumn(string $name): string;

	/**
	 * Unquote a table
	 *
	 * @param string $name
	 * @return string
	 */
	abstract public function unquoteTable(string $name): string;

	/**
	 *
	 * @param string $key Column key to parse a conjunction from
	 * @param string $conjunction "AND" or "OR"
	 * @return array
	 */
	private static function parse_conjunction(string $key, string $conjunction): array {
		foreach (['AND', 'OR', ] as $token) {
			if (StringTools::ends($key, "|$token", true)) {
				return [substr($key, 0, -(strlen($token) + 1)), $token, ];
			}
		}
		return [$key, $conjunction === 'AND' ? 'OR' : 'AND', ];
	}

	/**
	 * Prefix a where clause with the WHERE keyword, if there is one.
	 * If not, return the blank string.
	 *
	 * @param string $sql
	 *            where clause
	 * @return string
	 */
	protected function where_prefix(string $sql): string {
		return $this->_sql_prefix($sql, 'WHERE');
	}

	/**
	 * Prefix a where clause with the WHERE keyword, if there is one.
	 * If not, return the blank string.
	 *
	 * @param string $sql
	 *            where clause
	 * @return string
	 */
	protected function having_prefix(string $sql): string {
		return $this->_sql_prefix($sql, 'HAVING');
	}

	/**
	 * Prefix a where clause with a keyword, if there is one.
	 * If not, return the blank string.
	 *
	 * @param string $sql
	 * @param string $prefix
	 * @return string
	 */
	private function _sql_prefix(string $sql, string $prefix) {
		$sql = trim($sql);
		if (empty($sql)) {
			return '';
		}
		return " $prefix $sql ";
	}

	private static function valid_conjucnction(string $conjunction): string {
		return strtoupper($conjunction) === self::CONJUNCTION_OR ? self::CONJUNCTION_OR : self::CONJUNCTION_AND;
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
	 */
	public function where_clause(array $arr, string $conj = self::CONJUNCTION_AND, string $prefix_in = '', string $suffix = ''): string {
		if (count($arr) === 0) {
			return '';
		}
		$conj = self::valid_conjucnction($conj);
		$prefix = empty($prefix_in) ? '' : $prefix_in . '.';
		$result = [];
		foreach ($arr as $k => $v) {
			if (is_numeric($k) && is_string($v) || $k === '') {
				$result[] = $v;

				continue;
			}
			$new_key = $k;
			if (!str_contains($new_key, '.')) {
				if (str_starts_with($new_key, '*')) {
					$new_key = '*' . $prefix . substr($new_key, 1);
				} else {
					$new_key = $prefix . $new_key;
				}
			}
			if (is_array($v) || $v instanceof Iterator) {
				if (is_numeric($k)) {
					$result[] = '(' . $this->where_clause($v, ($conj !== self::CONJUNCTION_OR) ? self::CONJUNCTION_OR : self::CONJUNCTION_AND, $prefix_in) . ')';
				} elseif (count($v) === 0) {
					$result[] = $this->quoteColumn($new_key) . ' IS NULL';
				} else {
					$conj_sql = [];
					[$new_key, $new_conj] = $this->parse_conjunction($new_key, $conj);
					foreach ($v as $vv) {
						$conj_sql[] = $this->pair_to_sql($new_key, $vv, true);
					}
					if (count($conj_sql) > 0) {
						$result[] = '(' . implode(" $new_conj ", $conj_sql) . ')';
					}
				}
			} else {
				$result[] = $this->pair_to_sql($new_key, $v, true);
			}
		}
		if (count($result) === 0) {
			return '';
		}
		return implode(' ' . trim($conj) . ' ', $result) . ($suffix ? " $suffix " : '');
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
	public function where(array $where, string $conj = 'AND', string $prefix = ''): string {
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
	public function having(array $having, string $conj = 'AND', string $prefix = '') {
		return $this->having_prefix($this->where_clause($having, $conj, $prefix));
	}

	/**
	 * Join clause
	 *
	 * @param array $joins
	 * @return string
	 */
	private function join(array $joins) {
		if (count($joins) === 0) {
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
	private function what(string|array $what, bool $distinct = false): string {
		if (is_array($what)) {
			$result = [];
			foreach ($what as $as => $select_column) {
				if (is_numeric($as)) {
					$result[] = $this->quoteColumn($select_column);
				} else {
					$literal = ($as[0] === '*');
					if ($literal) {
						$result[] = "$select_column AS " . $this->quoteColumn(substr($as, 1));
					} else {
						$result[] = $this->columnAs($select_column, $as);
					}
				}
			}
			$what = implode(', ', $result);
		}
		$distinct = ($distinct ? 'DISTINCT ' : '');
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
	public function update(array $options = []): string {
		$table = $options['table'] ?? '';
		$values = toArray($options['values'] ?? []);
		$where = toArray($options['where'] ?? []);
		$alias = $options['alias'] ?? '';
		assert(is_string($table));
		assert(is_string($alias));
		$name_equals_values = [];
		foreach ($values as $k => $v) {
			// TODO, This allows for | keys, which it shouldn't allow.
			// KMD 2022 - nice comment. Why shouldn't it allow it?
			$name_equals_values[] = $this->pair_to_sql($k, $v);
		}
		$options += [
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
		];
		$sql = '{prefix}{update prefix}UPDATE{update suffix} {table prefix}';
		$sql .= $this->update_tables([$alias => $table]) . "{table suffix} {set prefix}SET{set suffix}\n\t{values prefix}";
		$sql .= implode(",\n\t", $name_equals_values) . "{values suffix}\t{where prefix}" . $this->where($where) . '{where suffix}{suffix}';
		return trim(map($sql, $options));
	}

	/**
	 * Select query
	 *
	 * @param array $options
	 * @return string
	 * @throws Exception_Unimplemented|Exception_Semantics
	 */
	public function select(array $options): string {
		$offset = toInteger($options['offset'] ?? 0);
		$limit = toInteger($options['limit'] ?? -1);
		$what = $options['what'] ?? [];
		$distinct = toBool($options['distinct'] ?? false);
		$alias = strval($options['alias'] ?? '');
		$conjunction = strval($options['conjunction'] ?? 'AND');
		$tables = $options['tables'] ?? [];
		$having = toArray($options['having'] ?? []);
		$group_by = toArray($options['group_by'] ?? []);
		$order_by = toArray($options['order_by'] ?? []);
		$where = toArray($options['where'] ?? []);
		if (!is_array($what)) {
			$what = strval($what);
		}
		if (empty($what)) {
			throw new Exception_Semantics('Need a non-empty what');
		}
		$where = $this->where($where, $conjunction, $alias);
		if (is_string($tables)) {
			$sql_tables = $this->quoteTable($tables);
		} elseif (is_array($tables)) {
			if (count($tables) === 0) {
				throw new Exception_Semantics('Need at least one table');
			}
			$alias = key($tables);
			if (!is_string($alias)) {
				$alias = '';
			}
			$sql_tables = $this->table_as(array_shift($tables), $alias);
			$sql_tables .= $this->join($tables);
			$where = ltrim($where);
		} else {
			$sql_tables = (string) $tables;
		}
		if (empty($sql_tables)) {
			throw new Exception_Semantics('No table supplied');
		}
		$sql = 'SELECT ' . $this->what($what, $distinct) . ' FROM ' . $sql_tables . $where . $this->group_by($group_by) . $this->having($having) . $this->order_by($order_by) . $this->limit($offset, $limit);
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
	 */
	public function insert(string $table, array $values, array $options): string {
		$verb = $options['verb'] ?? 'INSERT';
		$low_priority = toBool($options['low_priority'] ?? false);

		[$insert_names, $insert_values] = $this->_insert_to_name_values($values);
		$low_priority = $low_priority ? ' LOW_PRIORITY' : '';
		return "$verb $low_priority INTO " . $this->quoteTable($table) . " (\n\t`" . implode("`,\n\t`", $insert_names) . "`\n) VALUES (\n\t" . implode(",\n\t", $insert_values) . "\n)";
	}

	/**
	 * INSERT INTO table SELECT foo .
	 *
	 * ..
	 *
	 * @param array $options
	 * @return string
	 */
	public function insert_select(string $table, array $values, string $select_sql, array $options): string {
		$verb = $options['verb'] ?? 'INSERT';
		$low_priority = toBool($options['low_priority'] ?? false);
		[$insert_name] = $this->_insert_to_name_values($values);
		$low_priority = $low_priority ? ' LOW_PRIORITY' : '';
		return "$verb $low_priority INTO " . $this->quoteTable($table) . " (\n\t`" . implode("`,\n\t`", $insert_name) . "`\n) $select_sql";
	}

	/**
	 * DELETE FROM table
	 *
	 * @param array $options
	 * @return string
	 */
	public function delete(string $table, array $where, array $options = []): string {
		return 'DELETE FROM ' . $this->quoteTable($table) . $this->where($where);
	}

	/**
	 * Convert pair to insert name/value pair
	 *
	 * @param array $arr
	 * @return array(array("name0","name1",..."),array("value0","value1",...))
	 */
	private function _insert_to_name_values(array $arr): array {
		$insert_names = [];
		$insert_values = [];
		foreach ($arr as $k => $v) {
			if (str_starts_with($k, '*')) {
				$insert_names[] = substr($k, 1);
				$insert_values[] = $v;
			} else {
				$insert_names[] = $k;
				$insert_values[] = $this->mixed_to_sql($v);
			}
		}
		return [$insert_names, $insert_values, ];
	}

	/**
	 * GROUP BY SQL
	 *
	 * @param string|array $s
	 * @return string
	 */
	public function group_by(array|string $s): string {
		if (is_array($s)) {
			$s = implode(', ', $s);
		}
		if (is_string($s)) {
			$s = trim($s);
			if (empty($s)) {
				return '';
			}
			return " GROUP BY $s";
		}
		return '';
	}

	/**
	 * ORDER BY clause
	 *
	 * @param string $s
	 * @param string $prefix
	 * @return string
	 */
	public function order_by(string|array $s, string $prefix = ''): string {
		if (empty($s)) {
			return '';
		}
		if (is_string($s)) {
			if (str_contains($s, ';')) {
				$s = explode(';', $s);
			}
		}
		if (!is_array($s)) {
			$s = [$s, ];
		}
		$s = array_filter($s);
		if (count($s) === 0) {
			return '';
		}
		$r = [];
		foreach ($s as $oby) {
			if ($oby[0] === '-') {
				$oby = substr($oby, 1) . ' DESC';
			}
			if (!str_contains($oby, '.')) {
				if (!empty($prefix)) {
					$oby = "$prefix.$oby";
				}
			}
			$r[] = $oby;
		}
		return ' ORDER BY ' . implode(', ', $r);
	}

	/**
	 * LIMIT clause.
	 *
	 * @param int $offset
	 * @param int $limit
	 * @return string
	 */
	private function limit(int $offset = 0, int $limit = -1): string {
		if ($offset == 0) {
			if ($limit <= 0) {
				return '';
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
	 */
	protected function pair_to_sql(string $k, mixed $v, bool $is_compare = false): string {
		[$k, $cmp] = pair($k, '|', $k, '=');
		if ($k[0] === '*') {
			if ($v === null) {
				return substr($k, 1) . ' IS ' . (($cmp === '!=') ? 'NOT ' : '') . 'NULL';
			}
			return substr($k, 1) . "$cmp$v";
		} elseif ($v === null && $is_compare) {
			if ($cmp === '!=') {
				return $this->quoteColumn($k) . ' IS NOT NULL';
			} elseif ($cmp === '=') {
				return $this->quoteColumn($k) . ' IS NULL';
			} else {
				return $this->quoteColumn($k) . ' IS NULL';
			}
		} elseif ($cmp === '%') {
			return $this->quoteColumn($k) . ' LIKE ' . $this->quoteText("%$v%");
		} elseif ($cmp === '!%') {
			return $this->quoteColumn($k) . ' NOT LIKE ' . $this->quoteText("%$v%");
		} else {
			return $this->quoteColumn($k) . " $cmp " . $this->mixed_to_sql($v);
		}
	}

	/**
	 * Convert a value to a SQL value
	 *
	 * @param mixed $v
	 * @return string
	 */
	protected function mixed_to_sql(mixed $v): string {
		if ($v === null) {
			return 'NULL';
		}
		if (is_string($v)) {
			if (strlen($v) === 0) {
				return '\'\'';
			}
			return $this->quoteText($v);
		}
		if (is_bool($v)) {
			return $v ? '1' : '0';
		}
		if ($v instanceof Model) {
			return strval($v->id());
		}
		if (is_object($v)) {
			if ($v instanceof Iterator) {
				$result = [];
				foreach ($v as $item) {
					$result[] = $this->mixed_to_sql($item);
				}
				return '(' . implode(', ', $result) . ')';
			}
			return $this->quoteText($v->__toString());
		}
		if (is_numeric($v)) {
			if ($v === INF) {
				return '\'1e500\'';
			}
			if ($v === -INF) {
				return '\'-1e500\'';
			}
		}
		return strval($v);
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
