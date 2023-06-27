<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\Database;

use zesk\ArrayTools;
use zesk\Exception\ClassNotFound;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Hookable;
use zesk\preg;
use zesk\StringTools;
use zesk\Text;

/**
 *
 * @author kent
 *
 */
abstract class SQLParser extends Hookable
{
	public const COMMAND_CREATE_TABLE = 'create table';

	public const pattern_database_hint = '/--\s*Database:\s*(\w+)/i';

	/**
	 *
	 * @var Base
	 */
	protected Base $database;

	/**
	 *
	 * @return SQLDialect
	 */
	final public function sql(): SQLDialect
	{
		return $this->database->sqlDialect();
	}

	/**
	 * Create a new database parser
	 *
	 * @param Base $database
	 * @param array $options
	 */
	public function __construct(Base $database, array $options = [])
	{
		$this->database = $database;
		parent::__construct($database->application, $options);
	}

	/**
	 * Remove comments from a block of SQL statements
	 *
	 * @param string $sql
	 * @return string
	 */
	public function removeComments(string $sql): string
	{
		return Text::removeLineComments($sql, '--');
	}

	public const COMMAND_INSERT = 'insert';

	public const COMMAND_UPDATE = 'update';

	public const COMMAND_SELECT = 'select';

	public const COMMAND_ALTER = 'alter';

	public const COMMAND_DROP = 'drop';

	public const PATTERN_INSERT_PREFIX_TABLE = 'insert\s+into';

	public const PATTERN_SELECT_PREFIX_TABLE = 'select.*?\s+from';

	public const PATTERN_ALTER_PREFIX_TABLE = 'alter\s+table';

	/**
	 * Parse SQL to determine type of command
	 *
	 * @param string $sql
	 * @return array
	 */
	public function parseSQL(string $sql): array
	{
		$sql = $this->sql()->removeComments($sql);
		$sql = trim($sql);
		$result = [];
		$commandPattern = implode('|', [
			self::COMMAND_CREATE_TABLE, self::COMMAND_INSERT, self::COMMAND_UPDATE, self::COMMAND_SELECT,
			self::COMMAND_ALTER, self::COMMAND_DROP,
		]);
		if ($sql === '') {
			$result['command'] = 'none';
		} elseif (preg_match('/^(' . $commandPattern . ')/i', $sql, $matches)) {
			$result['command'] = strtolower($matches[1]);
			$tablePrefixPatterns = implode('|', [
				self::COMMAND_CREATE_TABLE, self::COMMAND_UPDATE, self::PATTERN_INSERT_PREFIX_TABLE,
				self::PATTERN_SELECT_PREFIX_TABLE, self::PATTERN_ALTER_PREFIX_TABLE,
			]);
			if (preg_match('/^(?:' . $tablePrefixPatterns . ')\s+([`A-Za-z0-9_]+)\s+/i', $sql, $matches)) {
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
	 * @param string $sqlScript
	 * @return array
	 */
	public function splitSQLStatements(string $sqlScript): array
	{
		return StringTools::splitSQLStatements($sqlScript);
	}

	/**
	 *
	 * @param Base $db
	 * @param string $sql
	 * @param string $source
	 * @return SQLParser
	 * @throws ClassNotFound
	 * @throws KeyNotFound
	 * @throws NotFoundException
	 * @throws ParameterException
	 */
	public static function parseFactory(Base $db, string $sql, string $source): SQLParser
	{
		$app = $db->application;
		if ($app->development() && empty($source)) {
			throw new ParameterException('{method} missing source {args}', [
				'method' => __METHOD__, 'args' => [
					$sql, $source,
				],
			]);
		}
		$db_module = $app->databaseModule();
		$matches = null;
		if (preg_match(self::pattern_database_hint, $sql, $matches)) {
			$db_scheme = strtolower($matches[1]);
			if ($db->supportsScheme($db_scheme)) {
				return $db->sqlParser();
			}

			try {
				$db = $db_module->schemeFactory($db_scheme);
			} catch (NotFoundException $e) {
				$app->logger->error('Unable to parse SQL from {source}, halting', [
					'source' => $source,
				]);

				throw $e;
			}
		}
		return $db->sqlParser();
	}

	/**
	 * Convert from SQL to Table
	 *
	 * @param string $sql
	 * @return Table
	 */
	abstract public function createTable(string $sql): Table;

	abstract public function createIndex(Table $table, string $sql): bool;

	/**
	 * Convert an order-by clause into an array, parsing out any functions or other elements
	 *
	 * @param string $order_by
	 * @return array
	 */
	public function splitOrderBy(string $order_by): array
	{
		$map = [];
		/*
		 * Remove quoted strings (simple)
		 * Remove nested functions (two-deep)
		 * Remove functions (one-deep)
		 */
		$patterns = [
			'/\'[^\']*\'/', '/[a-z_][a-z0-9_]*\([^()]*\(([^)]*\)[^()]*)\)/i', '/[a-z_][a-z0-9_]*\([^)]*\)/i',
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
	public function reverseOrderBy(string|array $order_by): string|array
	{
		$was_string = false;
		if (is_string($order_by)) {
			$was_string = true;
			$order_by = $this->splitOrderBy($order_by);
		}
		$reversed_order_by = [];
		$suffixes = [
			' ASC' => ' DESC', ' DESC' => ' ASC',
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
