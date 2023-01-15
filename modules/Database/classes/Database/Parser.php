<?php
declare(strict_types=1);

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
	public const pattern_database_hint = '/--\s*Database:\s*(\w+)/i';

	/**
	 *
	 * @var Database
	 */
	protected $database;

	/**
	 *
	 * @return Database_SQL
	 */
	final public function sql() {
		return $this->database->sql();
	}

	/**
	 * Create a new database parser
	 *
	 * @param Database $database
	 * @param array $options
	 */
	public function __construct(Database $database, array $options = []) {
		$this->database = $database;
		parent::__construct($database->application, $options);
	}

	/**
	 * Remove comments from a block of SQL statements
	 *
	 * @param string $sql
	 * @return string
	 */
	public function removeComments(string $sql): string {
		return Text::remove_line_comments($sql, '--');
	}

	/**
	 * Parse SQL to determine type of command
	 *
	 * @param string $sql
	 * @return array
	 */
	public function parseSQL(string $sql): array {
		$sql = $this->sql()->removeComments($sql);
		$sql = trim($sql);
		$result = [];
		if ($sql === '') {
			$result['command'] = 'none';
		} elseif (preg_match('/^(create table|insert|update|select|alter|drop table)/i', $sql, $matches)) {
			$result['command'] = strtolower($matches[1]);
			if (preg_match('/^(?:create table|insert into|update|select.*from)\s+([`A-Za-z0-9_]+)\s+/i', $sql, $matches)) {
				$result['table'] = $this->sql()->unquoteTable($matches[1]);
			}
		}
		return $result;
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
	public function splitSQLStatements(string $sql): array {
		$map = [
			'\\\'' => '*SLASH_SLASH_QUOTE*',
		];
		$rev_map = array_flip($map);
		// Munge our string to make pattern matching easier
		$sql = strtr($sql, $map);
		$index = 0;
		while (preg_match('/\'[^\']*\'/', $sql, $match) !== 0) {
			$from = $match[0];
			$to = chr(1) . '{' . $index . '}' . chr(2);
			$index++;
			// Map BACK to the original string, not the munged one
			$map[strtr($from, $rev_map)] = $to;
			$sql = strtr($sql, [
				$from => $to,
			]);
		}
		$sqls = ArrayTools::listTrimClean(explode(';', $sql));
		// Now convert everything back to what it is supposed to be
		$sqls = tr($sqls, array_flip($map));
		return $sqls;
	}

	/**
	 *
	 * @param Database $db
	 * @param string $sql
	 * @param string $source
	 * @return Database_Parser
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Key
	 * @throws Exception_NotFound
	 * @throws Exception_Parameter
	 */
	public static function parseFactory(Database $db, string $sql, string $source): Database_Parser {
		$app = $db->application;
		if ($app->development() && empty($source)) {
			throw new Exception_Parameter('{method} missing source {args}', [
				'method' => __METHOD__,
				'args' => [
					$sql,
					$source,
				],
			]);
		}
		$db_module = $app->database_module();
		$matches = null;
		if (preg_match(self::pattern_database_hint, $sql, $matches)) {
			$db_scheme = strtolower($matches[1]);
			if ($db->supportsScheme($db_scheme)) {
				return $db->parser();
			}

			try {
				$db = $db_module->schemeFactory($db_scheme);
			} catch (Exception_NotFound $e) {
				$app->logger->error('Unable to parse SQL from {source}, halting', [
					'source' => $source,
				]);

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
	abstract public function createTable(string $sql): Database_Table;

	abstract public function createIndex(Database_Table $table, $sql);

	/**
	 * Convert an order-by clause into an array, parsing out any functions or other elements
	 *
	 * @param string $order_by
	 * @return array
	 */
	public function splitOrderBy(string $order_by): array {
		$map = [];
		/*
		 * Remove quoted strings (simple)
		 * Remove nested functions (two-deep)
		 * Remove functions (one-deep)
		 */
		$patterns = [
			'/\'[^\']*\'/',
			'/[a-z_][a-z0-9_]*\([^()]*\(([^)]*\)[^()]*)\)/i',
			'/[a-z_][a-z0-9_]*\([^)]*\)/i',
		];
		foreach ($patterns as $pattern) {
			foreach (preg::matches($pattern, $order_by) as $match) {
				$map['%#' . count($map) . '#%'] = $match[0];
			}
		}
		// Remove tokens from order clause
		$order_by = tr($order_by, array_flip($map));
		// Split at commas
		$order_by = ArrayTools::listTrimClean(explode(',', $order_by));
		// Convert resulting array and replace removed tokens
		return tr($order_by, $map);
	}

	/**
	 * Reverses an order by clause as passed into a select query
	 *
	 * @param string|array $order_by
	 * @return string|array
	 */
	public function reverseOrderBy(string|array $order_by): string|array {
		$was_string = false;
		if (is_string($order_by)) {
			$was_string = true;
			$order_by = $this->splitOrderBy($order_by);
		}
		$reversed_order_by = [];
		$suffixes = [
			' ASC' => ' DESC',
			' DESC' => ' ASC',
		];
		foreach ($order_by as $clause) {
			$reversed = false;
			foreach ($suffixes as $suffix => $reverse_suffix) {
				if (str_ends_with($clause, $suffix)) {
					$reversed = true;
					$reversed_order_by[] = substr($clause, 0, -(strlen($suffix))) . $reverse_suffix;

					break;
				}
			}
			if (!$reversed) {
				$reversed_order_by[] = trim($clause) . ' DESC';
			}
		}
		return $was_string ? implode(', ', $reversed_order_by) : $reversed_order_by;
	}
}
