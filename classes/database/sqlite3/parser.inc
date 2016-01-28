<?php
/**
 * $URL: http://code.marketacumen.com/zesk/trunk/classes/database/mysql.inc $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */

/**
 * Pattern to capture an enture CREATE TABLE sql command
 *
 * Full pattern, delimited.
 *
 * @var string
 * @see preg_match
 */
define('SQLITE_PATTERN_CREATE_TABLE', '/\s*CREATE\s+TABLE\s+("[^"]+"|`[^`]+`|[A-Za-z][A-Za-z0-9_]+)\s*\(((?:.|\n)*)\)([A-Za-z0-9 =]*);?/im');

/**
 * Pattern to capture `Name's alive` or Name_Of_Column in MySQL
 *
 * Use in other patters, not delimited
 *
 * @var string
 * @see preg_match
 *
 */
define("SQLITE_PATTERN_IDENTIFIER", '("[^"]+"|`[^`]+`|[A-Za-z][A-Za-z0-9_]*)');
define("SQLITE_PATTERN_COLUMN_NAME", SQLITE_PATTERN_IDENTIFIER);
define("SQLITE_PATTERN_TABLE_NAME", SQLITE_PATTERN_IDENTIFIER);
/**
 * Pattern to capture `Name's alive` or Name_Of_Column in MySQL
 *
 * Use in other patters, not delimited
 *
 * @var string
 * @see preg_match
 *
 */
define("SQLITE_PATTERN_COLUMN_TYPE", '([A-Za-z]+(\([^)]*\))?)(\s+unsigned)?');

/**
 * Pattern to capture multiple columns in a database CREATE TABLE syntax
 *
 * Full pattern. For simplicity, append a "," to the source pattern, so:
 *
 * if (preg_match_all(SQLITE_PATTERN_COLUMN_LIST, "$column_sql,", $matches)) {
 * }
 *
 * To simplify the parsing
 *
 * @var string
 * @see preg_match_all
 *
 */
define("SQLITE_PATTERN_COLUMN_LIST", '/\s*(' . SQLITE_PATTERN_COLUMN_NAME . '\s+' . SQLITE_PATTERN_COLUMN_TYPE . '([^,]*)),/i');

/**
 * Pattern to capture indexes in a database CREATE INDEX syntax
 *
 * Full pattern, delimited.
 *
 * @var string
 */
define('SQLITE_PATTERN_INDEXES', '/CREATE\s+(UNIQUE\s+)?INDEX\s+' . SQLITE_PATTERN_COLUMN_NAME . '\s+ON\s+' . SQLITE_PATTERN_TABLE_NAME . '\s+\(([^)]+)\)/i');
define('SQLITE_PATTERN_INDEX_COLUMN_LIST', '/' . SQLITE_PATTERN_COLUMN_NAME . ',/');

/**
 * Pattern to capture tips regarding column renaming.
 * Syntax is:
 *
 * /* RENAME: Old_Name -> New_Name *\/
 *
 * Full pattern, delimited.
 *
 * @var string
 */
define('SQLITE_PATTERN_TIP_RENAME', '/\/\*\s*RENAME:\s*' . SQLITE_PATTERN_COLUMN_NAME . '\s*->\s*' . SQLITE_PATTERN_COLUMN_NAME . '\s*\*\//');

/**
 * Pattern to capture tips regarding column addition and removal.
 * Syntax is:
 *
 * /* -Column_Name: UPDATE {table} SET Other_Column=Column_Name*100; *\/
 * /* +Column_Name: UPDATE {table} SET Column_Name=42; *\/
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
define('SQLITE_PATTERN_TIP_ALTER', '/\/\*\s*([+-])' . SQLITE_PATTERN_COLUMN_NAME . ':\s+(.*)\s*\*\//');

define('SQLITE_TABLE_QUOTES', '``""');

define('SQLITE_COLUMN_QUOTES', '``""');
/**
 *
 * @author kent
 *
 */
class Database_SQLite3_Parser extends Database_Parser {

	/**
	 *
	 * @var Database_MySQL
	 */
	protected $database = null;

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
		$result = parent::parse_sql($sql);
		if (count($result) === 0) {
			$matches = null;
			if (preg_match('/^create\s+(?:unique\s+)?index\s+' . SQLITE_PATTERN_IDENTIFIER . ' on ' . SQLITE_PATTERN_IDENTIFIER . '/i', $sql, $matches)) {
				$result['command'] = 'create index';
				$result['table'] = $this->sql()->unquote_table($matches[2]);
			}
		}
		return ($field === null) ? $result : avalue($result, $field, $result);
	}
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
			'character set ([A-Za-z][A-Za-z0-9]*)',
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
				case 'on update current_timestamp':
					$options['column_extras'] = 'ON UPDATE CURRENT_TIMESTAMP';
					break;
				case 'primary key':
					$options['primary key'] = true;
					break;
				default:
					if (begins($type, "default ")) {
						$default = $options['default'] = $data_type->native_type_default($sql_type, $value);
					}
					if (begins($type, "character set ")) {
						$options['character set'] = $value;
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
		if (!preg_match_all(SQLITE_PATTERN_COLUMN_LIST, $sql, $columns_matches, PREG_SET_ORDER)) {
			throw new Exception_Parse(__("Unable to parse table {0} column definition: {1}", $table->name(), substr($sql, 128)));
		}
		$db = $table->database();
		$previous_column = null;
		foreach ($columns_matches as $col_match) {
			/*
			 * Check for index lines and handle differently
			 */
			$options = array();
			$column_name = unquote($col_match[2], SQLITE_COLUMN_QUOTES);
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
			$options['after_column'] = $previous_column;
			$col = new Database_Column($table, $column_name, $options);
			$table->column_add($col);

			$previous_column = $column_name;
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
		return $table_options;
	}

	public function create_index(Database_Table $table, $sql) {
		$indexes = self::parse_index_sql($table, $sql);
		return first($indexes);
	}
	/**
	 * Given the inside of the create table command, parse and remove indexes and store in $indexes_state
	 *
	 * @param Database_Table $table
	 * @param string $sql_columns
	 * @param mixed $indexes_state
	 * @return string
	 */
	private static function parse_index_sql(Database_Table $table, $sql) {
		/*
		 * Extract indexes from definition
		 */
		$original_table_name = $table->name();
		$index_matches = false;
		if (!preg_match_all(SQLITE_PATTERN_INDEXES, $sql, $index_matches, PREG_SET_ORDER)) {
			return array();
		}
		$indexes = array();
		foreach ($index_matches as $index_match) {
			list(, $unique, $index_name, $table_name, $columns) = $index_match;
			$table_name = unquote($table_name, SQLITE_COLUMN_QUOTES);
			if ($table_name !== $original_table_name) {
				continue;
			}
			$index_name = unquote($index_name, SQLITE_COLUMN_QUOTES);
			$columns = trim($columns);
			if (!preg_match_all(SQLITE_PATTERN_INDEX_COLUMN_LIST, "$columns,", $column_matches, PREG_PATTERN_ORDER)) {
				throw new Exception_Parse("Unable to parse SQLite3 {table} columns {index_name}: {raw_columns}", array(
					'table' => $table_name,
					'index_name' => $index_name,
					"raw_columns" => $columns
				));
			}
			$column_matches = unquote($column_matches[1], SQLITE_COLUMN_QUOTES);
			$unique = strcasecmp($unique, "unique") ? Database_Index::Unique : Database_Index::Index;
			$indexes[] = $index = new Database_Index($table, $index_name, $column_matches, $unique);
		}
		return $indexes;
	}

	/**
	 * Parse Tips
	 *
	 * @todo move to parent class
	 * @param string $sql
	 * @return array
	 */
	private static function tips(&$sql) {
		$matches = null;
		$renamed_columns = array();
		if (preg_match_all(SQLITE_PATTERN_TIP_RENAME, $sql, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$sql = str_replace($match[0], "", $sql);
				$renamed_columns[$match[2]] = $match[1];
			}
		}

		$add_tips = array();
		$remove_tips = array();
		$tip_matches = null;
		if (preg_match_all(SQLITE_PATTERN_TIP_ALTER, $sql, $tip_matches, PREG_SET_ORDER)) {
			foreach ($tip_matches as $tip_match) {
				list($full_match, $plus_minus, $column, $alter_sql) = $tip_match;
				$col = unquote($column, SQLITE_COLUMN_QUOTES);
				if ($plus_minus === '+') {
					$add_tips[$col] = $alter_sql;
				} else {
					$remove_tips[$col] = $alter_sql;
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
	 * @todo move to parent class
	 * @param Database_Table $table
	 * @param array $tips
	 */
	private static function apply_tips(Database_Table $table, array $tips) {
		$rename_tips = avalue($tips, 'rename', array());
		foreach ($rename_tips as $column => $previous_name) {
			/* @var $col Database_Column */
			$col = $table->column($column);
			if ($col) {
				$col->previous_name($previous_name);
			} else {
				log::notice($table->name() . " contains rename tip for non-existent new column: $previous_name => $column");
			}
		}

		$add_tips = avalue($tips, 'add', array());
		foreach ($add_tips as $column => $add_sql) {
			$col = $table->column($column);
			if ($col) {
				$col->set_option("add_sql", $add_sql);
			} else {
				log::notice($table->name() . " contains add tip for non-existent new column: $column => $add_sql");
			}
		}
		$remove_tips = avalue($tips, 'add', array());
		if (count($remove_tips) > 0) {
			$table->set_option("remove_sql", $remove_tips);
		}
	}

	/**
	 * The money
	 *
	 * @see Database_Parser::create_table()
	 * Parses CREATE TABLE for MySQL and returns a Database_Table
	 */
	function create_table($sql) {
		$matches = false;
		$source_sql = $sql;

		/*
		 * Extract tips from SQL first, save them
		 */
		$tips = self::tips($sql);

		// Remove # lines
		$sql = text::remove_line_comments($sql, '#'); // Technically not valid in SQL - but for legacy reasons leave it in
		$sql = text::remove_line_comments($sql, '--');

		/*
		 * Parse table into name, columns, and options
		 */
		if (!preg_match(SQLITE_PATTERN_CREATE_TABLE, $sql, $matches)) {
			throw new Exception_Parse(__("Unable to parse CREATE TABLE starting with: {0}", substr($sql, 0, 99)));
		}

		$table = unquote($matches[1], SQLITE_TABLE_QUOTES);
		/*
		 * Parse table options (end of table declaration)
		 */
		$table_options = self::create_table_options($matches[3]);

		$table = new Database_Table($this->database, $table);
		$table->source($source_sql);
		$sql_columns = trim($matches[2]) . ",";

		self::parse_column_sql($table, $sql_columns);

		self::parse_index_sql($table, $sql);

		/*
		 * Apply tips to entire table
		 */
		self::apply_tips($table, $tips);

		return $table;
	}

}
