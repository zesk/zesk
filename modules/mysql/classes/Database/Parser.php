<?php

/**
 * $URL: http://code.marketacumen.com/zesk/trunk/classes/database/mysql.inc $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */
namespace MySQL;

use zesk\Database_Table;
use zesk\Database_Column;
use zesk\Database_Index;
use zesk\Exception_Parse;
use zesk\arr;

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
 * @var string
 * @see preg_match
 */
define('MYSQL_PATTERN_CREATE_TABLE', '/\s*CREATE\s+TABLE\s+(`[^`]+`|[A-Za-z][A-Za-z0-9_]+)\s*\((.*)\)([A-Za-z0-9 =_]*);?/im');

/**
 * Pattern to capture options for CREATE TABLE (ENGINE, DEFAULT CHARSET)
 *
 * Full pattern, delimited.
 *
 * @var string
 * @see preg_match
 */
define("MYSQL_PATTERN_CREATE_TABLE_OPTIONS", '/(ENGINE|DEFAULT CHARSET|COLLATE)=([A-Za-z0-9_]+)/i');

/**
 * Pattern to capture `Name's alive` or Name_Of_Column in MySQL
 *
 * Use in other patterns, not delimited
 *
 * @var string
 * @see preg_match
 *
 */
define("MYSQL_PATTERN_COLUMN_NAME", '(`[^`]+`|[A-Za-z][A-Za-z0-9_]*)');
/**
 * Pattern to capture `Name's alive` or Name_Of_Column in MySQL
 *
 * Use in other patters, not delimited
 *
 * @var string
 * @see preg_match
 *
 */
define("MYSQL_PATTERN_COLUMN_TYPE", '([A-Za-z]+(\([^)]*\))?)(\s+unsigned)?');

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
 * @var string
 * @see preg_match_all
 *
 */
define("MYSQL_PATTERN_COLUMN_LIST", '/\s*(' . MYSQL_PATTERN_COLUMN_NAME . '\s+' . MYSQL_PATTERN_COLUMN_TYPE . '([^,]*)),/i');

/**
 * Pattern to capture optional "size" after an index column name, e.g.
 *
 * INDEX name ( Account, Name(45) );
 * ^^^^
 * Use in other patters, not delimited
 *
 * @var string
 */
define('MYSQL_PATTERN_INDEX_SIZE', '[^)]+(?:\([0-9]+\))?');

define('MYSQL_PATTERN_INDEX_COLUMN', '/(`[^`]+`|[A-Z-z][A-Za-z0-9_]*)\s*\(\s*([0-9]+)\s*\)/');

/**
 * Pattern to capture indexes in a database CREATE TABLE syntax
 *
 * Full pattern, delimited.
 *
 * @var string
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
 * @var string
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
 * @var string
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
	protected $database = null;

	/**
	 * Parse DEFAULT and other options on a column
	 *
	 * @param Database_Table $table
	 * @param unknown $column_options
	 * @return multitype:
	 */
	private function parse_column_options_sql(Database_Table $table, $sql_type, $column_options) {
		static $patterns = array(
			'not null',
			'default null',
			'default \'([^\']*)\'',
			'default (-?[0-9:]+)',
			'default ([a-zA-Z_]+)',
			'default current_timestamp',
			'character set ([A-Za-z][-_A-Za-z0-9]*)',
			'collate ([A-Za-z][-_A-Za-z0-9]*)',
			'auto_increment',
			'primary key',
			'on update current_timestamp'
		);

		$col_opt_matches = null;
		$options = array();
		$preg_pattern = '/' . implode('|', $patterns) . '/i';
		if (!preg_match_all($preg_pattern, $column_options, $col_opt_matches, PREG_SET_ORDER)) {
			return $options;
		}
		$data_type = $this->database->data_type();
		foreach ($col_opt_matches as $arr) {
			$type = strtolower($arr[0]);
			$value = last($arr);
			switch ($type) {
				case "default null":
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
					$options['default'] = "CURRENT_TIMESTAMP";
					break;
				case 'on update current_timestamp':
					$options['column_extras'] = 'ON UPDATE CURRENT_TIMESTAMP';
					break;
				case 'primary key':
					$options['primary key'] = true;
					break;
				default :
					if (begins($type, "default ")) {
						$options['default'] = $data_type->native_type_default($sql_type, $value);
					}
					if (begins($type, "character set ")) {
						$options[Database::attribute_character_set] = $value;
					}
					if (begins($type, "collate ")) {
						$options[Database::attribute_collation] = $value;
					}
					break;
			}
		}
		return $options;
	}

	/**
	 * Parse "int(11) unsigned AUTO_INCREMENT PRIMARY KEY NOT NULL," updating table.
	 *
	 * @param Database_Table $table
	 * @param string $sql
	 * @throws Exception_Parse
	 * @return unknown
	 */
	private function parse_column_sql(Database_Table $table, $sql) {
		$columns_matches = array();
		if (!preg_match_all(MYSQL_PATTERN_COLUMN_LIST, $sql, $columns_matches, PREG_SET_ORDER)) {
			throw new Exception_Parse(__("Unable to parse table {0} column definition: {1}", $table->name(), substr($sql, 128)));
		}
		$db = $table->database();
		foreach ($columns_matches as $col_match) {
			/*
			 * Check for index lines and handle differently
			 */
			$options = array();
			$column_name = unquote($col_match[2], '``');
			$col_opt_matches = false;
			$sql_type = $col_match[3];

			$options = $this->parse_column_options_sql($table, $sql_type, $col_match[6]);

			if (begins($sql_type, "varbinary")) {
				$options['binary'] = true;
			}
			$size = to_integer(unquote($col_match[4], '()'), null);
			if ($size !== null) {
				$options['size'] = $size;
			}
			if (trim(strtolower($col_match[5])) === "unsigned") {
				$options['unsigned'] = true;
			}
			$options['type'] = $sql_type;
			$options['sql_type'] = trim($sql_type);

			if ($sql_type === "timestamp" && !isset($options['default'])) {
				if (avalue($options, 'not null')) {
					$options['default'] = "CURRENT_TIMESTAMP";
				} else {
					$options['default'] = null;
				}
			}

			$col = new Database_Column($table, $column_name, $options);
			$options = $this->database->column_attributes($col);
			$col->set_option($options, null, false);
			$table->column_add($col);
		}
		return $sql;
	}

	/**
	 * Parse CREATE TABLE options
	 *
	 * @param string $sql
	 * @return array Options to set to table
	 */
	private static function create_table_options($sql) {
		/*
		 * Parse table options (end of table declaration)
		 */
		$table_options = array();
		$temp = false;
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
	private static function parse_index_sql(Database_Table $table, $sql_columns, &$indexes_state) {
		/*
		 * Extract indexes from definition
		 */
		$index_matches = false;
		if (!preg_match_all(MYSQL_PATTERN_INDEXES, $sql_columns, $index_matches, PREG_SET_ORDER)) {
			return $sql_columns;
		}
		foreach ($index_matches as $index_match) {
			$index_type = $index_match[1];
			$index_name = unquote($index_match[3], '``');
			$index_columns = unquote(arr::trim(explode(",", $index_match[4])), '``');
			$index_structure = avalue($index_match, 5, null);

			$indexes_state[] = compact("index_type", "index_name", "index_columns", "index_structure");
			$sql_columns = str_replace($index_match[0], "", $sql_columns);
		}
		return $sql_columns;
	}

	/**
	 * Process parsed indexes
	 *
	 * @param Database_Table $table
	 * @param array $indexes
	 */
	private static function process_indexes(Database_Table $table, array $indexes) {
		foreach ($indexes as $state) {
			$index_type = $index_name = $index_columns = $index_structure = null;
			extract($state, EXTR_IF_EXISTS);
			$index = new Database_Index($table, $index_name, null, $index_type, $index_structure);
			foreach ($index_columns as $index_column) {
				$index_size_match = false;
				if (preg_match(MYSQL_PATTERN_INDEX_COLUMN, $index_column, $index_size_match)) {
					$index->column_add(unquote($index_size_match[1], '``'), intval($index_size_match[2]));
				} else {
					$index->column_add($index_column);
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
	private static function tips(&$sql) {
		$matches = null;
		$renamed_columns = array();
		if (preg_match_all(MYSQL_PATTERN_TIP_COLUMN, $sql, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$sql = str_replace($match[0], "", $sql);
				$renamed_columns[$match[2]] = $match[1];
			}
		}

		$add_tips = array();
		$remove_tips = array();
		$tip_matches = null;
		if (preg_match_all(MYSQL_PATTERN_TIP_ALTER, $sql, $tip_matches, PREG_SET_ORDER)) {
			foreach ($tip_matches as $tip_match) {
				list($full_match, $plus_minus, $column, $alter_sql) = $tip_match;
				$alter_sql = rtrim($alter_sql, ";\n") . ";";
				$col = unquote($column, "``");
				if ($plus_minus === '+') {
					$add_tips[$col] = avalue($add_tips, $col, "") . $alter_sql;
				} else {
					$remove_tips[$col] = avalue($remove_tips, $col, "") . $alter_sql;
				}
				$sql = str_replace($full_match, "", $sql);
			}
		}
		return array(
			"rename" => $renamed_columns,
			"add" => $add_tips,
			"remove" => $remove_tips
		);
	}

	/**
	 * Allow renaming of columns and of tables using comments.
	 *
	 * @param Database_Table $table
	 * @param array $tips
	 */
	private function apply_tips(Database_Table $table, array $tips) {
		$rename_tips = avalue($tips, 'rename', array());
		foreach ($rename_tips as $column => $previous_name) {
			/* @var $col Database_Column */
			$col = $table->column($column);
			if ($col) {
				$col->previous_name($previous_name);
			} else {
				$this->application->logger->notice($table->name() . " contains rename tip for non-existent new column: $previous_name => $column");
			}
		}

		$add_tips = avalue($tips, 'add', array());
		foreach ($add_tips as $column => $add_sql) {
			$col = $table->column($column);
			if ($col) {
				$col->set_option("add_sql", $add_sql);
			} else {
				$this->application->logger->notice($table->name() . " contains add tip for non-existent new column: $column => $add_sql");
			}
		}
		$remove_tips = avalue($tips, 'add', array());
		if (count($remove_tips) > 0) {
			$table->set_option("remove_sql", $remove_tips);
		}
	}
	function create_index(Database_Table $table, $sql) {
		return false;
	}

	/**
	 * The money
	 *
	 * @see Database_Parser::create_table() Parses CREATE TABLE for MySQL and returns a
	 *      Database_Table
	 */
	function create_table($sql) {
		$matches = false;
		$source_sql = $sql;

		/*
		 * Extract tips from SQL first, save them
		 */
		$tips = self::tips($sql);
		$sql = $this->sql()->remove_comments($sql);

		/*
		 * Parse table into name, columns, and options
		 */
		if (!preg_match(MYSQL_PATTERN_CREATE_TABLE, strtr($sql, "\n", " "), $matches)) {
			throw new Exception_Parse(__("Unable to parse CREATE TABLE starting with: {0}", $sql));
		}

		$table = unquote($matches[1], "``");

		/*
		 * Parse table options (end of table declaration)
		 */
		$table_options = self::create_table_options($matches[3]);

		$type = avalue($table_options, "engine", avalue($table_options, "type", $this->database->default_engine()));
		$table = new Database_Table($this->database, $table, $type, $table_options);
		$table->source($source_sql);
		$sql_columns = trim($matches[2]) . ",";

		/*
		 * Extract indexes first
		 */
		$indexes = array();
		$sql_columns = self::parse_index_sql($table, $sql_columns, $indexes);
		/*
		 * Parse individual columns
		 */
		$sql_columns = self::parse_column_sql($table, $sql_columns);

		self::process_indexes($table, $indexes);

		/*
		 * Apply tips to entire table
		 */
		$this->apply_tips($table, $tips);

		return $table;
	}
}
