<?php declare(strict_types=1);
/**
 * AutoTables functionality refactored from Database, TBD. Incomplete.
 *
 * @package zesk
 * @subpackage ORM
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk\ORM;

trait AutoTables {
	/**
	 * @var bool
	 */
	protected bool $autoTables = false;

	/**
	 * @var array
	 */
	protected array $auto_table_names_options = [];

	/**
	 * @var array
	 */
	protected array $tableNameCache = [];

	/**
	 * Remove all single-quote-delimited strings in a series of SQL statements, taking care of
	 * backslash-quotes in strings
	 * assuming the SQL is well-formed.
	 *
	 * @todo Note, this doesn't work on arbitrary binary data if passed through, should probably
	 *       handle that case - use PDO interface
	 * @param string $sql
	 * @param mixed $state
	 *            A return value to save undo information
	 * @return string SQL with strings removed
	 */
	public static function removeStringTokens(string $sql, mixed &$state): string {
		$unstrung = strtr($sql, ['\\\'' => chr(1), ]);
		$matches = null;
		if (!preg_match_all('/\'[^\']*\'/', $unstrung, $matches, PREG_PATTERN_ORDER)) {
			$state = null;
			return $sql;
		}
		$state = [];
		// When $replace is a long string, say, 29000 characters or more, can not do array_flip
		// PHP has a limit on the key size
		foreach ($matches[0] as $index => $match) {
			$search = "#\$%$index%\$#";
			$replace = strtr($match, [chr(1) => '\\\'', ]);
			$state[$search] = $replace;
			$sql = str_replace($replace, $search, $sql);
		}
		return $sql;
	}

	/**
	 * Undo the "removeStringTokens" step, exactly
	 *
	 * @param string $sql
	 * @param mixed $state
	 * @return string SQL after strings are put back in
	 */
	public static function replaceStringTokens(string $sql, mixed $state): string {
		if (!is_array($state)) {
			return $sql;
		}
		return strtr($sql, $state);
	}

	/**
	 * Getter for whether SQL is converted to use table names from class names in {} in SQL
	 *
	 * @return bool
	 */
	public function autoTableNames(): bool {
		return $this->autoTables;
	}

	/**
	 * Setter for whether SQL is converted to use table names from class names in {} in SQL
	 *
	 * @param bool $set
	 * @return self
	 */
	public function setAutoTableNames(bool $set): self {
		$this->autoTables = $set;
		return $this;
	}

	/**
	 * @return array
	 */
	public function autoTableNamesOptions(): array {
		return $this->auto_table_names_options;
	}

	/**
	 * Getter/setter for auto_table_names options, passed to object creation for ALL tables for
	 * table
	 *
	 * @param array $set
	 * @return Database
	 */
	public function setAutoTableNamesOptions(array $set): self {
		$this->auto_table_names_options = $set;
		return $this;
	}

	public function autoTableRenameIterable(iterable $iter, array $options = []): iterable {
		$result = [];
		foreach ($iter as $sql) {
			if (is_string($sql) || (is_object($sql) && method_exists($sql, '__toString'))) {
				$result[] = self::autoTableRename(strval($sql), $options);
			}
		}
		return $result;
	}

	public function autoTableRename(string $sql, array $options = []): string {
		$matches = [];
		$state = null;
		$sql = self::removeStringTokens($sql, $state);
		$sql = map($sql, $this->tableNameCache, true);
		if (!preg_match_all('/\{([A-Za-z][A-Za-z0-9_]*)(\*?)}/', $sql, $matches, PREG_SET_ORDER)) {
			return self::replaceStringTokens($sql, $state);
		}
		$options = $options + $this->auto_table_names_options;
		$map = $this->tableNameCache;
		foreach ($matches as $match) {
			[$full_match, $class, $no_cache] = $match;
			// Possible bug: How do we NOT cache table name replacements which are parameterized?, e.g Site_5343 - table {Site} should not cache this result, right?
			// TODO
			$table = $this->application->ormRegistry($class, null, $options)->table();
			if (count($options) === 0 && $no_cache !== '*') {
				$this->tableNameCache[$full_match] = $table;
			}
			$map[$full_match] = $this->quoteTable($table);
		}
		$sql = strtr($sql, $map);
		return self::replaceStringTokens($sql, $state);
	}

	/**
	 * Convert SQL and replace table names magically.
	 *
	 * @param iterable|string $sql
	 * @param array $options
	 * @return iterable|string
	 * @todo Move this to a module using hooks in Module_Database
	 */
	public function autoTableNamesReplace(iterable|string $sql, array $options = []): iterable|string {
		if (is_array($sql)) {
			return $this->autoTableRenameIterable($sql, $options);
		}
		return $this->autoTableRename($sql, $options);
	}
}

/*
 *
	public function test_unstring(): void {
		$db = $this->application->databaseRegistry();

		$sql = "UPDATE `Metric` SET
	`Model` = '1',
	`CodeName` = 'hypoglycemia',
	`Name` = 'Side Effect 2: Hypoglycemia',
	`Label` = 'Side Effect 2: Hypoglycemia',
	`Subhead` = '',
	`Metric_Category` = '1',
	`AutoFormula` = 'true',
	`Formula` = '{hypoglycemia}\n(value < 0) ? (value * {significance-ratings}) : value',
	`Benefit_Min` = '-25',
	`Benefit_Max` = '0',
	`Scaling` = '1.7',
	`OrderIndex` = '4',
	`IsActive` = 'true',
	Modified=UTC_TIMESTAMP()	 WHERE `ID` = '4';";
		$state = null;
		//echo "OLD: $sql\n";
		$sql = Database::removeStringTokens($sql, $state);
		$sql = strtr($sql, [
			'\'\'' => 'empty-string',
		]);
		//echo "NEW: $sql\n";
		$this->assertStringNotContainsString('\'', $sql);

		$state = null;
		$this->assertEquals(Database::replaceStringTokens(Database::removeStringTokens($sql, $state), $state), $sql);
		$sql = "UPDATE `Metric` SET
	`Model` = '1',
	`CodeName` = 'hypog\\'lycemia',
	`Name` = 'Side Effect 2: Hypoglycemia',
	`Label` = 'Side Effect 2: Hypoglycemia',
	`Subhead` = '',
	`Metric_Category` = '1',
	`AutoFormula` = 'true',
	`Formula` = '{hypoglycemia}\n(value < 0) ? (value * {significance-ratings}) : value',
	`Benefit_Min` = '-25',
	`Benefit_Max` = '0',
	`Scaling` = '1.7',
	`OrderIndex` = '4',
	`IsActive` = 'true',
	Modified=UTC_TIMESTAMP()	 WHERE `ID` = '4';";
		$state = null;
		//echo "OLD: $sql\n";
		$new_sql = Database::removeStringTokens($sql, $state);
		//echo "NEW: $new_sql\n";
		$this->assertStringNotContainsString('\'', $new_sql);
		$state = null;
		$this->assertEquals(Database::replaceStringTokens(Database::removeStringTokens($sql, $state), $state), $sql);
	}

 */
