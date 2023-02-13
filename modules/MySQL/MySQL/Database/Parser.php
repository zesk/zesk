<?php
declare(strict_types=1);
/**
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */

namespace MySQL;

use zesk\Database as zeskDatabase;
use zesk\Database_Table;
use zesk\Database_Column;
use zesk\Database_Index;
use zesk\Exception_Key;
use zesk\Exception_NotFound;
use zesk\Exception_Parse;
use zesk\ArrayTools;
use zesk\Exception_Semantics;

/**
 * Pattern to capture an enture CREATE TABLE sql command
 *
 * Full pattern, delimited.
 *
 * 2017-03-13: Note the inside capturing pattern was (?:.|\n) which broke the PCRE parser on large
 * tables in PHP7, I assume
 * due to excessive backtracking. So tables should do strtr($sql, "\n", " ") before using this
 * pattern on it.
 *
 * @see preg_match
 */
define('MYSQL_PATTERN_CREATE_TABLE', '/\s*CREATE\s+TABLE\s+(`[^`]+`|[A-Za-z][A-Za-z0-9_]+)\s*\((.*)\)([A-Za-z0-9 =_]*);?/im');

/**
 * Pattern to capture options for CREATE TABLE (ENGINE, DEFAULT CHARSET)
 *
 * Full pattern, delimited.
 *
 * @see preg_match
 */
define('MYSQL_PATTERN_CREATE_TABLE_OPTIONS', '/(ENGINE|DEFAULT CHARSET|COLLATE)=([A-Za-z0-9_]+)/i');

/**
 * Pattern to capture `Name's alive` or Name_Of_Column in MySQL
 *
 * Use in other patterns, not delimited
 *
 * @see preg_match
 *
 */
define('MYSQL_PATTERN_COLUMN_NAME', '(`[^`]+`|[A-Za-z][A-Za-z0-9_]*)');
/**
 * Pattern to capture `Name's alive` or Name_Of_Column in MySQL
 *
 * Use in other patters, not delimited
 *
 * @see preg_match
 *
 */
define('MYSQL_PATTERN_COLUMN_TYPE', '([A-Za-z]+(\([^)]*\))?)(\s+unsigned)?');

/**
 * Pattern to capture multiple columns in a database CREATE TABLE syntax
 *
 * Full pattern. For simplicity, append a "," to the source pattern, so:
 *
 * if (preg_match_all(MYSQL_PATTERN_COLUMN_LIST, "$column_sql,", $matches)) {
 * }
 *
 * To simplify the parsing
 *
 * @see preg_match_all
 *
 */
define('MYSQL_PATTERN_COLUMN_LIST', '/\s*(' . MYSQL_PATTERN_COLUMN_NAME . '\s+' . MYSQL_PATTERN_COLUMN_TYPE . '([^,]*)),/i');

/**
 * Pattern to capture optional "size" after an index column name, e.g.
 *
 * INDEX name ( Account, Name(45) );
 * ^^^^
 * Use in other patters, not delimited
 *
 */
define('MYSQL_PATTERN_INDEX_SIZE', '[^)]+(?:\([0-9]+\))?');

define('MYSQL_PATTERN_INDEX_COLUMN', '/(`[^`]+`|[A-Z-z][A-Za-z0-9_]*)\s*\(\s*([0-9]+)\s*\)/');

/**
 * Pattern to capture indexes in a database CREATE TABLE syntax
 *
 * Full pattern, delimited.
 *
 */
define('MYSQL_PATTERN_INDEXES', '/(UNIQUE KEY|PRIMARY\s+KEY|KEY|UNIQUE|INDEX)(\s+' . MYSQL_PATTERN_COLUMN_NAME . ')?\s*\((' . MYSQL_PATTERN_INDEX_SIZE . ')\s*\)\s*(?:USING ([A-Za-z]+))?,/i');

/**
 * Pattern to capture tips regarding column renaming.
 * Syntax is:
 *
 * /* COLUMN: Old_Name -> New_Name *\/
 *
 * Full pattern, delimited.
 *
 */
define('MYSQL_PATTERN_TIP_COLUMN', '/^--+\s*COLUMN:\s*' . MYSQL_PATTERN_COLUMN_NAME . '\s*->\s*' . MYSQL_PATTERN_COLUMN_NAME . '\s*$/im');

/**
 * Pattern to capture tips regarding column addition and removal.
 * Syntax is:
 *
 * -- -Column_Name: UPDATE {table} SET Other_Column=Column_Name*100;
 * -- +Column_Name: UPDATE {table} SET Column_Name=42;
 *
 * After the : is one ore more SQL statements to run when that column is removed (-) or added (+).
 *
 * SQL is run for removed columns BEFORE they are removed to allow for preservation of data.
 *
 * SQL is run AFTER columns are added to facilitate configuring the new column with appropriate
 * values.
 *
 * @todo Probably should add the ability to require other tables to be updated as well before
 *       changes are made
 *
 *       Full pattern, delimited.
 *
 */
define('MYSQL_PATTERN_TIP_ALTER', '/^--+\s*([+-])' . MYSQL_PATTERN_COLUMN_NAME . ':\s+(.*)$/im');

/**
 *
 * @author kent
 *
 */
class Database_Parser extends \zesk\Database_Parser {
	/**
	 *
	 * @var Database
	 */
	protected zeskDatabase $database;

	/**
	 * Parse DEFAULT and other options on a column
	 *
	 * @param string $sql_type
	 * @param string $column_options
	 * @return array
	 */
	private function parseColumnOptionsSQL(string $sql_type, string $column_options): array {
		static $patterns = [
			'not null', 'default null', 'default \'([^\']*)\'', 'default b\'([01]+)\'', 'default (-?[0-9:]+)',
			'default ([a-zA-Z_]+)', 'default current_timestamp', 'character set ([A-Za-z][-_A-Za-z0-9]*)',
			'collate ([A-Za-z][-_A-Za-z0-9]*)', 'auto_increment', 'primary key', 'on update current_timestamp',
		];

		$col_opt_matches = null;
		$options = [];
		$preg_pattern = '/' . implode('|', $patterns) . '/i';
		if (!preg_match_all($preg_pattern, $column_options, $col_opt_matches, PREG_SET_ORDER)) {
			return $options;
		}
		$data_type = $this->database->data_type();
		foreach ($col_opt_matches as $arr) {
			$type = strtolower($arr[0]);
			$value = last($arr);
			switch ($type) {
				case 'default null':
					$options['not null'] = false;
					$options['default'] = null;

					break;
				case 'not null':
					$options['not null'] = true;

					break;
				case 'auto_increment':
					$options['increment'] = true;

					break;
				case 'default current_timestamp':
					$options['default'] = 'CURRENT_TIMESTAMP';

					break;
				case 'on update current_timestamp':
					$options['column_extras'] = 'ON UPDATE CURRENT_TIMESTAMP';

					break;
				case 'primary key':
					$options['primary key'] = true;

					break;
				default:
					if (str_starts_with($type, 'default ')) {
						$options['default'] = $data_type->native_type_default($sql_type, $value);
					}
					if (str_starts_with($type, 'character set ')) {
						$options[Database::ATTRIBUTE_CHARACTER_SET] = $value;
					}
					if (str_starts_with($type, 'collate ')) {
						$options[Database::ATTRIBUTE_COLLATION] = $value;
					}

					break;
			}
		}
		return $options;
	}

	/**
	 * Parse "int(11) unsigned AUTO_INCREMENT PRIMARY KEY NOT NULL," updating $table
	 *
	 * @param Database_Table $table
	 * @param string $sql
	 * @return string
	 * @throws Exception_Parse
	 */
	private function parseColumnSQL(Database_Table $table, string $sql): string {
		$columns_matches = [];
		if (!preg_match_all(MYSQL_PATTERN_COLUMN_LIST, $sql, $columns_matches, PREG_SET_ORDER)) {
			throw new Exception_Parse('Unable to parse table {name} column definition: {sqlSample}', [
				'name' => $table->name(), 'sqlSample' => substr($sql, 128),
			]);
		}
		foreach ($columns_matches as $col_match) {
			/*
			 * Check for index lines and handle differently
			 */
			$column_name = unquote($col_match[2], '``');
			$sql_type = $col_match[3];

			$options = $this->parseColumnOptionsSQL($sql_type, $col_match[6]);

			if (str_starts_with($sql_type, 'varbinary')) {
				$options['binary'] = true;
			}
			$size = toInteger(unquote($col_match[4], '()'), 0);
			if ($size !== 0) {
				$options['size'] = $size;
			}
			if (trim(strtolower($col_match[5])) === 'unsigned') {
				$options['unsigned'] = true;
			}
			$options['type'] = $sql_type;
			$options['sql_type'] = trim($sql_type);

			if ($sql_type === 'timestamp' && !isset($options['default'])) {
				if ($options['not null'] ?? null) {
					// KMD Was 2020-07-13 "CURRENT_TIMESTAMP";
					$options['default'] = 0;
				}
			}
			$col = new Database_Column($table, $column_name, $options);
			$options = $this->database->columnAttributes($col);
			$col->setOptions($options, false);

			try {
				$table->columnAdd($col);
			} catch (Exception_Semantics $e) {
				throw new Exception_Parse('Invalid column spec {table}', $table->variables(), 0, $e);
			}
		}
		return $sql;
	}

	/**
	 * Parse CREATE TABLE options
	 *
	 * @param string $sql
	 * @return array Options to set to table
	 */
	private static function createTableOptions(string $sql): array {
		/*
		 * Parse table options (end of table declaration)
		 */
		$table_options = [];
		$temp = [];
		if (preg_match_all(MYSQL_PATTERN_CREATE_TABLE_OPTIONS, $sql, $temp, PREG_SET_ORDER)) {
			// echo "***** $table\n";
			// dump($temp);
			foreach ($temp as $row) {
				$table_options[strtolower($row[1])] = $row[2];
			}
		}
		return $table_options;
	}

	/**
	 * Given the inside of the create table command, parse and remove indexes and store in
	 * $indexes_state
	 *
	 * @param Database_Table $table
	 * @param string $sql_columns
	 * @param mixed $indexes_state
	 * @return string
	 */
	private static function parseIndexSQL(Database_Table $table, string $sql_columns, array &$indexes_state): string {
		/*
		 * Extract indexes from definition
		 */
		$index_matches = [];
		if (!preg_match_all(MYSQL_PATTERN_INDEXES, $sql_columns, $index_matches, PREG_SET_ORDER)) {
			return $sql_columns;
		}
		foreach ($index_matches as $index_match) {
			$index_columns = array_map(fn ($v) => unquote($v, '``'), ArrayTools::trim(explode(',', $index_match[4])));
			$indexes_state[] = [
				'index_type' => $index_match[1], 'index_name' => unquote($index_match[3], '``'),
				'index_columns' => $index_columns, 'index_structure' => $index_match[5] ?? null,
			];
			$sql_columns = str_replace($index_match[0], '', $sql_columns);
		}
		return $sql_columns;
	}

	/**
	 * Process parsed indexes
	 *
	 * @param Database_Table $table
	 * @param array $indexes
	 * @return void
	 * @throws Exception_Parse
	 */
	private static function processIndexes(Database_Table $table, array $indexes): void {
		foreach ($indexes as $state) {
			$index_type = $index_name = $index_columns = $index_structure = '';
			extract($state, EXTR_IF_EXISTS);

			try {
				$index = new Database_Index($table, $index_name, $index_type, $index_structure);
			} catch (Exception_Semantics $e) {
				throw new Exception_Parse('Invalid index data specified {data} in index {index_name}', [
					'data' => $state, 'index_name' => $index_name,
				], 0, $e);
			}
			foreach ($index_columns as $index_column) {
				$index_size_match = false;

				try {
					if (preg_match(MYSQL_PATTERN_INDEX_COLUMN, $index_column, $index_size_match)) {
						$column_name = unquote($index_size_match[1], '``');
						$index->addColumn($column_name, intval($index_size_match[2]));
					} else {
						$column_name = $index_column;
						$index->addColumn($index_column);
					}
				} catch (Exception_NotFound $e) {
					throw new Exception_Parse('Invalid column {column_name} in index {index_name}', [
						'column_name' => $column_name, 'index_name' => $index_name,
					], 0, $e);
				}
			}
		}
	}

	/**
	 * Parse Tips
	 *
	 * @param string $sql
	 * @return array
	 */
	private static function tips(string &$sql): array {
		$matches = [];
		$renamed_columns = [];
		if (preg_match_all(MYSQL_PATTERN_TIP_COLUMN, $sql, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$sql = str_replace($match[0], '', $sql);
				$renamed_columns[$match[2]] = $match[1];
			}
		}

		$add_tips = [];
		$remove_tips = [];
		$tip_matches = null;
		if (preg_match_all(MYSQL_PATTERN_TIP_ALTER, $sql, $tip_matches, PREG_SET_ORDER)) {
			foreach ($tip_matches as $tip_match) {
				[$full_match, $plus_minus, $column, $alter_sql] = $tip_match;
				$alter_sql = rtrim($alter_sql, ";\n") . ';';
				$col = unquote($column, '``');
				if ($plus_minus === '+') {
					$add_tips[$col] ??= $alter_sql;
				} else {
					$remove_tips[$col] ??= $alter_sql;
				}
				$sql = str_replace($full_match, '', $sql);
			}
		}
		return ['rename' => $renamed_columns, 'add' => $add_tips, 'remove' => $remove_tips, ];
	}

	/**
	 * Allow renaming of columns and of tables using comments.
	 *
	 * @param Database_Table $table
	 * @param array $tips
	 * @return void
	 */
	private function applyTips(Database_Table $table, array $tips): void {
		$rename_tips = $tips['rename'] ?? [];
		foreach ($rename_tips as $columnName => $previousColumnName) {
			try {
				$col = $table->column($columnName);
				$col->setPreviousName($previousColumnName);
			} catch (Exception_Key) {
				$this->application->logger->notice('{name} contains rename tip for non-existent new column:{previousColumnName} => {columnName}', [
					'name' => $table->name(), 'previousColumnName' => $previousColumnName, 'columnName' => $columnName,
				]);
			}
		}

		$add_tips = $tips['add'] ?? [];
		foreach ($add_tips as $columnName => $addSQL) {
			try {
				$col = $table->column($columnName);
				$col->setOption('add_sql', $addSQL);
			} catch (Exception_Key) {
				$this->application->logger->notice($table->name() . '{name} contains add tip for non-existent new column: {columnName} => {addSQL}', [
					'name' => $table->name(), 'addSQL' => $addSQL, 'columnName' => $columnName,
				]);
			}
		}
		$remove_tips = $tips['add'] ?? [];
		if (count($remove_tips) > 0) {
			$table->setOption('remove_sql', $remove_tips);
		}
	}

	public function createIndex(Database_Table $table, $sql) {
		return false;
	}

	/**
	 * The money
	 *
	 * @see Database_Parser::createTable() Parses CREATE TABLE for MySQL and returns a
	 *      Database_Table
	 */
	/**
	 * @param string $sql
	 * @return Database_Table
	 * @throws Exception_Parse
	 * @throws Exception_Semantics
	 */
	public function createTable(string $sql): Database_Table {
		$matches = false;
		$source_sql = $sql;

		/*
		 * Extract tips from SQL first, save them
		 */
		$tips = self::tips($sql);
		$sql = $this->sql()->removeComments($sql);

		/*
		 * Parse table into name, columns, and options
		 */
		if (!preg_match(MYSQL_PATTERN_CREATE_TABLE, strtr($sql, "\n", ' '), $matches)) {
			throw new Exception_Parse('Unable to parse CREATE TABLE starting with: {0}', $sql);
		}

		$table = unquote($matches[1], '``');

		/*
		 * Parse table options (end of table declaration)
		 */
		$table_options = self::createTableOptions($matches[3]);

		$type = $table_options['engine'] ?? $table_options['type'] ?? $this->database->defaultEngine();
		$table = new Database_Table($this->database, $table, $type, $table_options);
		$table->setSource($source_sql);
		$sql_columns = trim($matches[2]) . ',';

		/*
		 * Extract indexes first
		 */
		$indexes = [];
		$sql_columns = self::parseIndexSQL($table, $sql_columns, $indexes);
		/*
		 * Parse individual columns
		 */
		$sql_columns = self::parseColumnSQL($table, $sql_columns);

		self::processIndexes($table, $indexes);

		/*
		 * Apply tips to entire table
		 */
		$this->applyTips($table, $tips);

		return $table;
	}
}
