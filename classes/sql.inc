<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/sql.inc $
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2009, Market Acumen, Inc.
 */
class sql {

	public static function now() {
		return "NOW()";
	}

	public static function utc_now() {
		return "UTC_TIMESTAMP()";
	}

	private static function mixed_to_sql($v) {
		if ($v === null) {
			return "NULL";
		}
		if (is_string($v)) {
			if (strlen($v) === 0) {
				return "''";
			}
			return self::quote($v);
		} else if (is_bool($v)) {
			return $v ? "'true'" : "'false'";
		} else if ($v instanceof Object) {
			return $v->id();
		} else if (is_object($v)) {
			return self::quote($v->__toString());
		} else if (is_numeric($v)) {
			if ($v === INF) {
				return "'1e500'";
			} else if ($v === -INF) {
				return "'-1e500'";
			}
		}
		return $v;
	}

	private static function pair_to_sql($k, $v, $is_compare = false) {
		list($k, $cmp) = pair($k, "|", $k, "=");
		if ($k[0] === '*') {
			if ($v === null) {
				return substr($k, 1) . " IS " . (($cmp === '!=') ? "NOT " : '') . "NULL";
			}
			return substr($k, 1) . "$cmp$v";
		} else if ($v === null && $is_compare) {
			if ($cmp === "!=") {
				return self::quote_column($k) . " IS NOT NULL";
			} else if ($cmp === "=") {
				return self::quote_column($k) . " IS NULL";
			} else {
				return self::quote_column($k) . " IS NULL";
			}
		} else if ($cmp === '%') {
			return self::quote_column($k) . " LIKE " . self::quote("%$v%");
		} else if ($cmp === '!%') {
			return self::quote_column($k) . " NOT LIKE " . self::quote("%$v%");
		} else {
			return self::quote_column($k) . " $cmp " . self::mixed_to_sql($v);
		}
	}

	/**
	 * @deprecated 2014-02-06
	 * @see Database_SQL::what
	 * @param unknown $what
	 * @param string $distinct
	 * @return string
	 */
	static function what($what, $distinct = null) {
		if (is_array($what)) {
			$result = array();
			foreach ($what as $alias => $sql) {
				if (is_numeric($alias)) {
					$result[] = self::quote_column($sql);
				} else {
					$quote_column = ($alias[0] !== '*');
					if ($quote_column) {
						$sql = self::quote_column($sql);
					} else {
						$alias = substr($alias, 1);
					}
					$result[] = "$sql AS $alias";
				}
			}
			$what = implode(", ", $result);
		}
		$distinct = ($distinct ? "DISTINCT " : "");
		return $distinct . strval($what);
	}

	private static function parse_conjunction($key, $conjunction) {
		foreach (array(
			"AND" => "AND",
			"OR" => "OR"
		) as $token) {
			if (str::ends($key, "|$token", true)) {
				return array(
					substr($key, 0, -(strlen($token) + 1)),
					$token
				);
			}
		}
		return array(
			$key,
			$conjunction === "AND" ? "OR" : "AND"
		);
	}

	/**
	 * Where clause generation.
	 * Does not include the "WHERE" string, just
	 * expr conj expr
	 * @param array $arr Where clause as $key => $value, has special meanings when $key begins with *
	 * @param string $conj The conjunction to generate the where clause
	 * @param string $prefix_in Prefix used
	 * @param unknown_type $suffix
	 * @return unknown
	 */
	public static function where_clause($arr, $conj = null, $prefix_in = "", $suffix = "") {
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
			$newk = $k;
			if (strpos($newk, ".") === false) {
				if ($newk[0] === '*') {
					$newk = '*' . $prefix . substr($newk, 1);
				} else {
					$newk = $prefix . $newk;
				}
			}
			if (is_array($v)) {
				if (is_numeric($k)) {
					$result[] = "(" . self::where_clause($v, $conj === "AND" ? "OR" : "AND", $prefix_in) . ")";
				} else if (count($v) === 0) {
					$result[] = self::quote_column($newk) . " IS NULL";
				} else {
					$conj_sql = array();
					list($newk, $new_conj) = self::parse_conjunction($newk, $conj);
					foreach ($v as $vv) {
						$conj_sql[] = self::pair_to_sql($newk, $vv, true);
					}
					$result[] = "(" . implode(" $new_conj ", $conj_sql) . ")";
				}
			} else {
				$result[] = self::pair_to_sql($newk, $v, true);
			}
		}
		return implode(" " . trim($conj) . " ", $result) . ($suffix ? " $suffix " : "");
	}

	public static function where_prefix($sql) {
		$sql = trim($sql);
		if (empty($sql)) {
			return "";
		}
		return " WHERE $sql ";
	}

	public static function type_where($k, $cmp, $x) {
		if (is_array($x)) {
			$wh = array();
			foreach ($x as $y) {
				$wh[] = self::type_where($k, $cmp, $y);
			}
			if (count($wh) === 0)
				return "";
			return "(" . implode(" OR ", $wh) . ")";
		}
		if (substr($k, 0, 1) === "*") {
			return substr($k, 1) . " $cmp $x";
		} else if (is_numeric($x)) {
			return "$k $cmp $x";
		} else if ($x === null || empty($x)) {
			$cmp = ($cmp === "=") ? "IS" : "IS NOT";
			return "$k $cmp NULL";
		}
		return "$k $cmp " . self::quote($x);
	}

	public static function where($where, $conj = null, $prefix = "") {
		return self::where_prefix(self::where_clause($where, $conj, $prefix));
	}

	/*
	 * SQL Tools
	 */
	private static function limit($offset = 0, $limit = -1) {
		if ($offset == 0) {
			if ($limit <= 0) {
				return "";
			} else {
				return " LIMIT $limit";
			}
		} else if ($limit <= 0) {
			return " LIMIT $offset,";
		} else {
			return " LIMIT $offset,$limit";
		}
	}

	private static function order_by($s, $prefix = "") {
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
				$s
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

	static function quote($x) {
		backtrace();
		if ($x === null || $x === "") {
			return "NULL";
		}

		return db::connect()->quote_text($x);
	}

	public static function quote_table($table) {
		list($dbname, $table) = pair($table, ".", null, $table);
		if (empty($dbname)) {
			return '`' . unquote($table, "``") . '`';
		} else {
			return '`' . unquote($dbname, "``") . '`.`' . unquote($table, "``") . '`';
		}
	}

	public static function table_as($table, $as) {
		if (empty($as)) {
			return self::quote_table($table);
		}
		return self::quote_table($table) . " AS " . self::quote_identifier($as);
	}

	public static function quote_column($column, $alias = null) {
		if ($alias === null) {
			list($alias, $column) = pair($column, ".", null, $column);
		}
		if (empty($alias)) {
			return '`' . unquote($column, "``") . '`';
		} else {
			$suffix = $column === "*" ? $column : '`' . unquote($column, "``") . '`';
			return '`' . unquote($alias, "``") . '`.' . $suffix;
		}
	}

	public static function quote_identifier($name) {
		return "`" . addcslashes($name, "`") . "`";
	}

	public static function join($joins) {
		if (!is_array($joins) || count($joins) === 0) {
			return "\n";
		}
		return "\n" . implode("\n", $joins) . "\n";
	}

	public static function update($t, $arr, $where = null, array $options = array()) {
		$iname = array();
		foreach ($arr as $k => $v) {
			$iname[] = self::pair_to_sql($k, $v);
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
			'suffix' => ''
		);
		$sql = "{update prefix}UPDATE{update suffix} {table prefix}" . self::quote_table($t) . "{table suffix} {set prefix}SET{set suffix}\n\t{values prefix}" . implode(",\n\t", $iname) . "{values suffix}\t{where prefix}" . self::where($where) . "{where suffix}{suffix}";
		return map($sql, $options);
	}

	/**
	 * @deprecated 2014-02-06
	 * @see Database_SQL::select
	 * @param unknown $what
	 * @param unknown $tables
	 * @param unknown $where
	 * @param string $group_by
	 * @param string $order_by
	 * @param number $offset
	 * @param unknown $limit
	 * @return string
	 */
	public static function select($what, $tables, $where, $group_by = false, $order_by = false, $offset = 0, $limit = -1) {
		$alias = null;
		$where = self::where($where, null, $alias);
		if (is_string($tables)) {
			$sql_tables = self::quote_table($tables);
		} else if (is_array($tables)) {
			$alias = key($tables);
			$sql_tables = self::table_as(array_shift($tables), $alias);
			$sql_tables .= self::join($tables);
			$where = ltrim($where);
		} else {
			$sql_tables = (string) $tables;
		}
		$sql = "SELECT " . self::what($what) . " FROM " . $sql_tables . $where . self::group_by($group_by) . self::order_by($order_by) . self::limit($offset, $limit);
		return trim($sql);
	}

	public static function insert($t, array $arr, $low_priority = false) {
		return self::_insert("INSERT", $t, $arr, $low_priority);
	}

	public static function insert_prefix($t, array $arr, $low_priority = false) {
		list($iname, ) = self::_insert_to_name_values($arr);
		$low_priority = $low_priority ? " LOW_PRIORITY" : "";
		$sql = "INSERT$low_priority INTO " . self::quote_table($t) . " (\n\t`" . implode("`,\n\t`", $iname) . "`\n) ";
		return $sql;
	}

	public static function replace($t, array $arr, $low_priority = false) {
		return self::_insert("REPLACE", $t, $arr, $low_priority);
	}

	public static function insert_select($t, array $arr, $select, $low_priority = false) {
		return self::_insert_select("INSERT", $t, $arr, $select);
	}

	public static function replace_select($t, array $arr, $select, $low_priority = false) {
		return self::_insert_select("REPLACE", $t, $arr, $select);
	}

	private static function _insert_to_name_values($arr) {
		$iname = array();
		$ivalue = array();
		foreach ($arr as $k => $v) {
			if (substr($k, 0, 1) === '*') {
				$iname[] = substr($k, 1);
				$ivalue[] = $v;
			} else {
				$iname[] = $k;
				$ivalue[] = self::mixed_to_sql($v);
			}
		}
		return array(
			$iname,
			$ivalue
		);
	}

	private static function _insert($verb, $t, $arr, $low_priority = false) {
		list($iname, $ivalue) = self::_insert_to_name_values($arr);
		$low_priority = $low_priority ? " LOW_PRIORITY" : "";
		$sql = "$verb$low_priority INTO " . self::quote_table($t) . " (\n\t`" . implode("`,\n\t`", $iname) . "`\n) VALUES (\n\t" . implode(",\n\t", $ivalue) . "\n)";
		return $sql;
	}

	private static function _insert_select($verb, $t, $arr, $select, $low_priority = false) {
		$low_priority = $low_priority ? " LOW_PRIORITY" : "";
		$sql = "$verb$low_priority INTO " . self::quote_table($t) . " (\n\t" . implode(",\n\t", $arr) . "\n) $select";
		return $sql;
	}

	public static function delete($table, $where) {
		return "DELETE FROM " . self::quote_table($table) . self::where($where);
	}

	public static function drop_table($table) {
		return "DROP TABLE IF EXISTS " . self::quote_table($table) . "";
	}

	public static function add_days($sqldatetime, $days) {
		return self::add_hours($sqldatetime, $days * 24);
	}

	public static function add_hours($sqldatetime, $hours) {
		return self::add_seconds($sqldatetime, $hours * 3600);
	}

	public static function add_minutes($sqldatetime, $minutes) {
		return self::add_seconds($sqldatetime, $minutes * 60);
	}

	public static function add_seconds($sqldatetime, $seconds) {
		$ts = utc_parse_time($sqldatetime);
		$ts += $seconds;
		return self::utc_from_time($ts);
	}

	public static function set_time($sqldatetime, $time = "00:00:00") {
		$ts = utc_parse_time($sqldatetime);
		return self::add_seconds(substr(self::utc_from_time($ts), 0, 11) . $time, 0);
	}

	public static function from_time($ts) {
		if (!is_numeric($ts)) {
			return null;
		}
		return date("Y-m-d H:i:s", $ts);
	}

	public static function utc_from_time($ts) {
		if (!is_numeric($ts)) {
			return null;
		}
		return gmdate("Y-m-d H:i:s", $ts);
	}

	public static function unhex($data) {
		return "UNHEX(" . self::quote($data) . ")";
	}

	public static function hex($data) {
		return "HEX(" . self::quote($data) . ")";
	}
}
