<?php

/**
 * $URL: https://code.marketacumen.com/zesk/trunk/classes/Database/Index.php $
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * @package zesk
 * @subpackage database
 */
namespace zesk;

/**
 * @author Kent M. Davidson <kent@marketacumen.com>
 * @package zesk
 */
class Database_Index {
	/**
	 * The databsae this is associated with
	 *
	 * @var Database
	 */
	private $database;
	/**
	 * The table this index is associated with
	 *
	 * @var Database_Table
	 */
	private $table;

	/**
	 * Array of name => size
	 * @var array
	 */
	private $columns;

	/**
	 * index name
	 *
	 * @var string
	 */
	private $name;

	/**
	 * index type
	 *
	 * @var string
	 */
	private $type;

	/**
	 * Index structure (database-specific)
	 *
	 * @todo move this to database-specific code
	 * @var string
	 */
	private $structure;
	const Index = "INDEX";
	const Unique = "UNIQUE";
	const Primary = "PRIMARY KEY";

	/**
	 *
	 * @param Database_Table $table
	 * @param string $name
	 * @param unknown $columns
	 * @param string $type
	 * @param unknown $structure
	 * @throws Exception_Semantics
	 */
	function __construct(Database_Table $table, $name = "", $columns = null, $type = "INDEX", $structure = null) {
		$this->table = $table;
		$this->database = $table->database();
		$this->columns = array();
		$this->type = self::determineType($type);
		$this->name = empty($name) && $this->type === self::Primary ? "primary" : $name;

		$this->structure = self::determineStructure($structure);

		if (is_array($columns)) {
			foreach ($columns as $col => $size) {
				if (is_numeric($size) || is_bool($size)) {
					$this->column_add($col, $size);
				} else if (!is_string($size)) {
					throw new Exception_Semantics(__("Columns must be name => size, or => name ({0} => {1} passed for table {2}", $col, $size, $table->name()));
				} else {
					$this->column_add($size);
				}
			}
		}
		$table->index_add($this);
	}

	/**
	 *
	 */
	function __destruct() {
		unset($this->table);
		unset($this->database);
		unset($this->columns);
	}

	/**
	 *
	 * @todo Move into database implementation
	 * @param unknown $sqlType
	 * @return string
	 */
	static public function determineType($sqlType) {
		switch (strtolower($sqlType)) {
			case "unique":
			case "unique key":
				return self::Unique;
			case "primary key":
			case "primary":
				return self::Primary;
			default :
			case "key":
			case "index":
				return self::Index;
		}
	}

	/**
	 *
	 * @param unknown $structure
	 * @return string
	 */
	public function determineStructure($structure) {
		$structure = strtoupper($structure);
		switch ($structure) {
			case "BTREE":
			case "HASH":
				return $structure;
		}
		return strtoupper($this->database->default_index_structure($this->type));
	}

	/**
	 *
	 * @param string $lower
	 * @return string
	 */
	function name() {
		return $this->name;
	}

	/**
	 * @return Database_Table
	 */
	function table(Database_Table $set = null) {
		if ($set !== null) {
			$this->table = $set;
			return $this;
		}
		return $this->table;
	}

	/**
	 * @return array
	 */
	function columns() {
		return array_keys($this->columns);
	}

	/**
	 *
	 */
	function column_sizes() {
		return $this->columns;
	}

	/**
	 *
	 * @deprecated 2016-09
	 * @return number
	 */
	function columnCount() {
		zesk()->deprecated();
		return $this->column_count();
	}
	/**
	 *
	 * @return number
	 */
	function column_count() {
		return count($this->columns);
	}

	/**
	 *
	 * @return string
	 */
	function type($set = null) {
		if ($set !== null) {
			$this->type = self::determineType($set);
			return $this;
		}
		return $this->type;
	}

	/**
	 *
	 * @return string
	 */
	function structure() {
		return $this->structure;
	}

	/**
	 *
	 * @param unknown $mixed
	 * @param string $size
	 * @throws Exception_NotFound
	 * @throws Database_Exception
	 * @return \zesk\Database_Index
	 */
	function column_add($mixed, $size = true) {
		$db_col = null;
		if ($mixed instanceof Database_Column) {
			$db_col = $mixed;
			$col = $mixed->name();
		} else if (is_string($mixed)) {
			$col = $mixed;
			$db_col = $this->table->column($col);
			if (!$db_col) {
				throw new Exception_NotFound("{method}: {col} not found in {table}", compact("col") + array(
					"method" => __METHOD__,
					"table" => $this->table
				));
			}
		} else if (is_array($mixed)) {
			foreach ($mixed as $k => $v) {
				if (is_numeric($k)) {
					$this->column_add($v);
				} else {
					$this->column_add($k, $v);
				}
			}
			return $this;
		} else {
			throw new Database_Exception($this->database, "", 0, "Database_Index::column_add(" . gettype($mixed) . "): Invalid type");
		}
		if ($this->type === self::Primary) {
			$db_col->primary_key(true);
		}
		$this->columns[$col] = is_numeric($size) ? intval($size) : true;
		return $this;
	}

	/**
	 *
	 * @param Database_Index $that
	 * @param string $debug
	 * @return boolean
	 */
	function is_similar(Database_Index $that, $debug = false) {
		$logger = $this->database->application->logger;
		if ($this->type() !== $that->type()) {
			if ($debug) {
				$logger->debug("Database_Index::is_similar(" . $this->type() . " !== " . $that->type() . ") Table types different");
			}
			return false;
		}
		if ($this->structure() !== $that->structure()) {
			if ($debug) {
				$logger->debug("Database_Index::is_similar(" . $this->structure() . " !== " . $that->structure() . ") Table structures different");
			}
			return false;
		}
		if ($this->table()->name() !== $that->table()->name()) {
			if ($debug) {
				$logger->debug("Database_Index::is_similar(" . $this->table() . " !== " . $that->table() . ") Tables different");
			}
			return false;
		}
		if ($this->name(true) !== $that->name(true)) {
			if ($debug) {
				$logger->debug("Database_Index::is_similar(" . $this->name() . " !== " . $that->name() . ") Names different");
			}
			return false;
		}
		if ($this->column_count() !== $that->column_count()) {
			if ($debug) {
				$logger->debug("Database_Index::is_similar(" . $this->column_count() . " !== " . $that->column_count() . ") ColumnCount different");
			}
			return false;
		}
		$thisCols = $this->columns();
		$thatCols = $that->columns();
		ksort($thisCols);
		ksort($thatCols);
		if (serialize($thisCols) !== serialize($thatCols)) {
			if ($debug) {
				$logger->debug("Database_Index::is_similar(\n" . serialize($thisCols) . " !== \n" . serialize($thatCols) . "\n) ColumnCount different");
			}
			return false;
		}
		return true;
	}

	/**
	 *
	 */
	function sql_index_type() {
		return $this->database->sql()->index_type($this->table, $this->name, $this->type, $this->column_sizes());
	}

	/**
	 *
	 * @return string
	 */
	function sql_index_add() {
		return $this->database->sql()->alter_table_index_add($this->table, $this);
	}

	/**
	 *
	 * @return string
	 */
	function sql_index_drop() {
		return $this->database->sql()->alter_table_index_drop($this->table, $this);
	}

	/**
	 *
	 * @return boolean
	 */
	function is_primary() {
		return $this->type === self::Primary;
	}

	/**
	 *
	 * @return boolean
	 */
	function is_index() {
		return $this->type === self::Index;
	}

	/**
	 *
	 * @return boolean
	 */
	function is_unique() {
		return $this->type === self::Unique;
	}

	/**
	 *
	 * @return string
	 */
	public function _debug_dump() {
		$vars = get_object_vars($this);
		$vars['database'] = $this->database->code_name();
		$vars['table'] = $this->table->name();
		return "Object:" . __CLASS__ . " (\n" . Text::indent(_dump($vars)) . "\n)";
	}
}
