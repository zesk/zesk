<?php

/**
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
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
	public function __construct(Database $database, array $options = array()) {
		$this->database = $database;
		parent::__construct($database->application, $options);
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
	 * Using old pattern "/((?:(?:'[^']*')|[^;])*);/" caused backtrack limit errors in PHP7.
	 * So changed to remove strings from SQL and replace afterwards
	 *
	 * @param string $sql
	 * @return array
	 */
	public function split_sql_commands($sql) {
		$map = array(
			"\\'" => '*SLASH_SLASH_QUOTE*'
		);
		$rev_map = array_flip($map);
		// Munge our string to make pattern matching easier
		$sql = strtr($sql, $map);
		$index = 0;
		while (preg_match("/'[^']*'/", $sql, $match) !== 0) {
			$from = $match[0];
			$to = chr(1) . "{" . $index . "}" . chr(2);
			$index++;
			// Map BACK to the original string, not the munged one
			$map[strtr($from, $rev_map)] = $to;
			$sql = strtr($sql, array(
				$from => $to
			));
		}
		$sqls = ArrayTools::trim_clean(explode(";", $sql));
		// Now convert everything back to what it is supposed to be
		$sqls = tr($sqls, array_flip($map));
		return $sqls;
	}
	/**
	 *
	 * @param Database $db
	 * @param string $sql
	 * @return Database_Parser
	 */
	static function parse_factory(Database $db, $sql, $source) {
		$app = $db->application;
		if ($app->development() && empty($source)) {
			throw new Exception_Parameter("{method} missing source {args}", array(
				"method" => __METHOD__,
				"args" => array(
					$sql,
					$source
				)
			));
		}
		$db_module = $app->database_module();
		$matches = null;
		if (preg_match(self::pattern_database_hint, $sql, $matches)) {
			$db_scheme = strtolower($matches[1]);
			if ($db->supports_scheme($db_scheme)) {
				return $db->parser();
			}
			try {
				$db = $db_module->scheme_factory($db_scheme);
			} catch (Exception_NotFound $e) {
				$app->logger->error("Unable to parse SQL from {source}, halting", array(
					"source" => $source
				));
				throw $e;
			}
		}
		return $db->parser();
	}

	/**
	 * Convert from SQL to Database_Table
	 *
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
		$order_by = ArrayTools::trim_clean(explode(",", $order_by));
		// Convert resulting array and replace removed tokens
		return tr($order_by, $map);
	}

	/**
	 * Reverses an order by clause as passed into a select query
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
