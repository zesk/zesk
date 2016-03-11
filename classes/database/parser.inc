<?php
abstract class Database_Parser extends Hookable {
	const pattern_database_hint = '/--\s*Database:\s*(\w+)/i';
	
	/**
	 *
	 * @var Database
	 */
	protected $database;
	
	/**
	 *
	 * @return Database_SQL
	 */
	final function sql() {
		return $this->database->sql();
	}
	
	/**
	 * Create a new database parser
	 *
	 * @param Database $database
	 * @param array $options
	 */
	public function __construct(Database $database, $options = null) {
		$this->database = $database;
		parent::__construct($options);
	}
	
	/**
	 * Parse SQL to determine type of command
	 *
	 * @param string $sql
	 * @param string $field
	 *        	Optional desired field.
	 * @return multitype:string NULL |Ambigous <mixed, array>
	 */
	public function parse_sql($sql, $field = null) {
		$sql = $this->sql()->remove_comments($sql);
		$sql = trim($sql);
		$result = array();
		if ($sql === "") {
			$result['command'] = 'none';
		} else if (preg_match('/^(create table|insert|update|select|alter|drop table)/i', $sql, $matches)) {
			$result['command'] = strtolower($matches[1]);
			if (preg_match('/^(?:create table|insert into|update|select.*from)\s+([`A-Za-z0-9_]+)\s+/i', $sql, $matches)) {
				$result['table'] = $this->sql()->unquote_table($matches[1]);
			}
		}
		return ($field === null) ? $result : avalue($result, $field, $result);
	}
	
	/**
	 * Divide SQL commands into different distinct commands
	 *
	 * @param string $sql
	 * @return array
	 */
	public function split_sql_commands($sql) {
		$pattern = "/((?:(?:'[^']*')|[^;])*);/";
		$sqls = array();
		$map = array(
			"\\'" => '*SLASH_SLASH_QUOTE*'
		);
		$sql = strtr($sql, $map);
		foreach (preg::matches($pattern, "$sql;") as $match) {
			$statement = trim($match[1]);
			if (empty($statement)) {
				continue;
			}
			$sqls[] = $statement;
		}
		$sqls = tr($sqls, array_flip($map));
		return $sqls;
	}
	/**
	 *
	 * @param Database $db
	 * @param string $sql
	 * @return Database_Parser
	 */
	static function parse_factory(Database $db, $sql) {
		$matches = null;
		if (preg_match(self::pattern_database_hint, $sql, $matches)) {
			try {
				return zesk::factory("Database_" . $matches[1] . "_Parser", $db);
			} catch (Exception_Class_NotFound $e) {
				zesk::hook("exception", $e);
			}
		}
		return $db->parser();
	}
	
	/**
	 * Convert from SQL to Database_Table
	 * @param string $sql
	 * @return Database_Table
	 */
	abstract function create_table($sql);
	abstract function create_index(Database_Table $table, $sql);
	
	/**
	 * Convert an order-by clause into an array, parsing out any functions or other elements
	 *
	 * @param string $order_by
	 * @return array
	 */
	public function split_order_by($order_by) {
		if (is_array($order_by)) {
			return $order_by;
		}
		$map = array();
		/*
		 * Remove quoted strings (simple)
		 * Remove nested functions (two-deep)
		 * Remove functions (one-deep)
		 */
		$patterns = array(
			"/'[^']*'/",
			'/[a-z_][a-z0-9_]*\([^()]*\(([^)]*\)[^()]*)\)/i',
			'/[a-z_][a-z0-9_]*\([^)]*\)/i'
		);
		foreach ($patterns as $pattern) {
			foreach (preg::matches($pattern, $order_by) as $match) {
				$map["%#" . count($map) . "#%"] = $match[0];
			}
		}
		// Remove tokens from order clause
		$order_by = tr($order_by, array_flip($map));
		// Split at commas
		$order_by = arr::trim_clean(explode(",", $order_by));
		// Convert resulting array and replace removed tokens
		return tr($order_by, $map);
	}
	
	/**
	 * Reverses an order by clause as passed into a Database_Query_Select
	 *
	 * @param mixed $order_by
	 * @return array
	 */
	public function reverse_order_by($order_by) {
		$was_string = false;
		if (!is_array($order_by)) {
			$was_string = true;
			$order_by = $this->split_order_by($order_by);
		}
		$reversed_order_by = array();
		$suffixes = array(
			' ASC' => ' DESC',
			' DESC' => ' ASC'
		);
		foreach ($order_by as $clause) {
			$reversed = false;
			foreach ($suffixes as $suffix => $reverse_suffix) {
				if (endsi($clause, $suffix)) {
					$reversed = true;
					$reversed_order_by[] = substr($clause, 0, -(strlen($suffix))) . $reverse_suffix;
					break;
				}
			}
			if (!$reversed) {
				$reversed_order_by[] = trim($clause) . ' DESC';
			}
		}
		return $was_string ? implode(", ", $reversed_order_by) : $reversed_order_by;
	}
}