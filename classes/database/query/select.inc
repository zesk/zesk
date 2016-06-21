<?php
/**
 * Select Query
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/database/query/select.inc $
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */
class Database_Query_Select extends Database_Query_Select_Base {
	/**
	 * What to select (array of alias => column)
	 * @var array|string
	 */
	protected $what = "*";
	/**
	 * Array of tables to query. First is main from, others are JOINed
	 * @var unknown
	 */
	protected $tables = null;
	/**
	 * Alias of main table
	 *
	 * @var string
	 */
	protected $alias = null;
	/**
	 * Where
	 * @var array
	 */
	protected $where = array();
	/**
	 * Order by clause
	 * @var array
	 */
	protected $order_by = null;
	/**
	 * Group by clause
	 * @var array
	 */
	protected $group_by = null;
	/**
	 * Offset
	 * @var integer
	 */
	protected $offset = 0;
	/**
	 * Limit
	 * @var unknown
	 */
	protected $limit = -1;
	/**
	 * Distinct query
	 * @var boolean
	 */
	protected $distinct = null;
	/**
	 * Array of alias => class
	 * @var array
	 */
	protected $join_objects = array();
	
	/**
	 * List of locale-specific conditions for outputting to the user
	 *
	 * @var array
	 */
	protected $conditions = array();
	/**
	 * Construct a new Select query
	 *
	 * @param Database $db
	 */
	function __construct(Database $db = null) {
		parent::__construct("SELECT", $db);
	}
	function __sleep() {
		return array_merge(parent::__sleep(), array(
			"what",
			"tables",
			"alias",
			"where",
			"order_by",
			"group_by",
			"offset",
			"limit",
			"distinct",
			"join_objects",
			"conditions"
		));
	}
	public function valid_column($column) {
		list($alias, $column) = pair($column, ".", $this->alias, $column);
		if ($alias === $this->alias) {
			$class = $this->class;
		} else {
			$class = avalue($this->join_objects, $alias);
			if (!$class) {
				return false;
			}
		}
		return Object::cache_object($class)->has_member($column);
	}
	/**
	 * Create an new query
	 *
	 * @param string $db
	 * @return Database_Query_Select
	 */
	public static function factory(Database $db = null) {
		return new Database_Query_Select($db);
	}
	
	/**
	 * Set as a distinct or non-distinct query
	 *
	 * @param boolean $set
	 * @return Database_Query_Select
	 */
	function distinct($set = true) {
		if ($set !== null) {
			$this->distinct = to_bool($set);
		}
		return $this;
	}
	public function class_alias($class = null) {
		if ($class === null || $this->class === $class) {
			return $this->alias;
		}
		$result = avalue(arr::flip_multiple($this->join_objects), $class, array());
		return last($result);
	}
	function alias($set = null) {
		if ($set !== null) {
			$this->alias = $set;
			return $this;
		}
		return $this->alias;
	}
	function columns() {
		return arr::unprefix(array_keys($this->what), "*");
	}
	function has_what($column) {
		return in_array($column, $this->columns());
	}
	/**
	 * Initialize or append the what clause
	 *
	 * ->what() returns the what clause
	 * ->what(null, "string") sets the what clause to a static string (no checking)
	 * ->what("string", null) deletes a member from the what clause
	 * ->what(return_column, table_column) adds "table_column as return_column" to what clause
	 * ->what(array(...), true) appends to the current what clause
	 * ->what(array(...)) replaces the current what clause
	 * ->what(object of type Database_Query_Select) replaces the current what clause
	 * ->what(object of type Database_Query_Select, true) appends the current what clause
	 *
	 * Passing an array should be of the form: "result name" => "table column reference"
	 *
	 * @param mixed $mixed
	 * @param mixed $value
	 * @return Database_Query_Select
	 */
	function what($mixed = null, $value = null) {
		if ($mixed === null && $value === null) {
			return $this->what;
		}
		if ($mixed === null && is_string($value)) {
			$this->what = $value;
			return $this;
		}
		if (is_string($this->what)) {
			$this->what = array();
		}
		if (is_string($mixed)) {
			if ($value === null) {
				$mixed = str::unprefix($mixed, "*");
				unset($this->what[$mixed]);
				unset($this->what["*$mixed"]);
				return $this;
			}
			$unmixed = str::unprefix($mixed, "*");
			unset($this->what[$unmixed]);
			unset($this->what["*$unmixed"]);
			$this->what[$mixed] = $value;
			return $this;
		}
		if (is_array($mixed)) {
			if ($value === true && is_array($this->what)) {
				foreach ($mixed as $k => $v) {
					$this->what($k, $v);
				}
				return $this;
			}
			$this->what = $mixed;
			return $this;
		}
		if ($mixed instanceof Database_Query_Select) {
			if ($value === true) {
				if (is_array($this->what)) {
					$this->what += $mixed->what;
				} else {
					$this->what = $mixed->what;
				}
			} else {
				$this->what = $mixed->what;
			}
			return $this;
		}
		throw new Exception_Parameter("Unknown parameter passed to Database_Query_Select::what(" . gettype($mixed) . ")");
	}
	
	/**
	 * Select from what
	 *
	 * @param string $table
	 * @param string $alias
	 * @return Database_Query_Select
	 */
	function from($table, $alias = "") {
		$this->tables[$alias] = $table;
		$this->alias = $alias;
		return $this;
	}
	
	/**
	 * Join tables
	 *
	 * @param string $sql
	 * @param string $join_id
	 * @return Database_Query_Select
	 */
	function join($sql, $join_id = null) {
		if (is_array($sql)) {
			$this->tables = array_merge($this->tables, $sql);
		} else if ($join_id !== null) {
			$this->tables[$join_id] = $sql;
		} else {
			$this->tables[] = $sql;
		}
		return $this;
	}
	
	/**
	 * Given a table alias, find the associated class
	 *
	 * @param string $alias
	 * @return string Class name associated with the alias, or null if not found
	 */
	function find_alias($alias) {
		if ($alias === $this->alias) {
			return $this->class;
		}
		return avalue($this->join_objects, $alias, null);
	}
	
	/**
	 * Join tables
	 *
	 * @param string $sql
	 * @param string $join_id
	 * @return Database_Query_Select
	 */
	function join_object($join_type, $class, $alias = null, array $on, $table = null) {
		$object = null;
		if ($class instanceof Object) {
			$object = $class;
			$class = get_class($class);
		} else {
			$object = Object::cache_object($class);
		}
		if (array_key_exists($alias, $this->join_objects)) {
			throw new Exception_Semantics(__CLASS__ . "::join_object: Same alias $alias added twice");
		}
		$this->join_objects[$alias] = $class;
		
		$sql = $this->sql();
		if ($table === null) {
			$table = $object->table();
		}
		if ($alias === null) {
			$alias = $class;
		}
		if ($object->database_name() !== $this->database_name()) {
			$cross_db_this = $this->database()->feature(Database::feature_cross_database_queries);
			$cross_db_object = $object->database()->feature(Database::feature_cross_database_queries);
			if ($cross_db_this !== true) {
				throw new Exception_Semantics("Database {name} ({class}) does not support cross-database queries, join is not possible", array(
					"name" => $this->database_name(),
					"class" => $this->class
				));
			}
			if ($cross_db_object !== true) {
				throw new Exception_Semantics("Database {name} ({class}) does not support cross-database queries, join is not possible", array(
					"name" => $object->database_name(),
					"class" => get_class($object)
				));
			}
			$table_as = $sql->database_table_as($object->database()->database_name(), $table, $alias);
		} else {
			$table_as = $sql->table_as($table, $alias);
		}
		return $this->join("$join_type JOIN $table_as ON " . $this->sql()->where_clause($on, null));
	}
	
	/**
	 * Link to another object
	 *
	 * @param mixed $class
	 *        	An object or a class name
	 * @param mixed $mixed
	 *        	Parameters to join with this object
	 *        Options are:
	 *         "path": The path to traverse to determine how this object links to the class specified. These are member names separated by dots.
	 *         "alias": Assign the alias specified to the class specified; intermediate table aliases are generated dynamically based on this name.
	 *         "type": Type of join, e.g. "INNER", or "LEFT OUTER", or "CROSS" or whatever database-specific join you want. Defaults to "INNER".
	 *         "require": Boolean value, when specified and when "type" is not specified, specifies "INNER" or "LEFT OUTER" for type. Defaults to false.
	 *
	 * @return Database_Query_Select
	 */
	function link($class, $mixed = null) {
		if (is_string($mixed)) {
			$mixed = array(
				"path" => $mixed
			);
		} else if (!is_array($mixed)) {
			$mixed = array();
		}
		$path = avalue($mixed, 'path', null);
		$object = Object::cache_object($this->class);
		if ($path === null) {
			$path = $object->link_default_path_to($class);
			if ($path === null) {
				throw new Exception_Semantics("No path to $class from $this->class, specify explicitly");
			}
			$mixed['path'] = $path;
		}
		return $object->link_walk($this, $mixed);
		
		//		return Object::cache_object($class)->link($this, $mixed);
	}
	
	/**
	 * Add where clause. Pass in false for $k to reset where to nothing.
	 *
	 * @param mixed $k
	 * @param string $v
	 * @return Database_Query_Select
	 */
	function where($k = null, $v = null) {
		if ($k === null && $v === null) {
			return $this->where;
		}
		if (is_array($k)) {
			$this->where = array_merge($this->where, $k);
		} else if ($k === null && is_string($v)) {
			$this->where[] = $v;
		} else if (!empty($k)) {
			$this->where[$k] = $v;
		} else if ($k === false) {
			$this->where = array();
		}
		return $this;
	}
	
	/**
	 * Set order by clause
	 *
	 * @param string $order_by
	 * @return Database_Query_Select
	 */
	function order_by($order_by = null) {
		if ($order_by === null) {
			return $this->order_by;
		}
		$this->order_by = $order_by;
		return $this;
	}
	
	/**
	 * Set group by clause
	 *
	 * @param string $group_by
	 * @return Database_Query_Select
	 */
	function group_by($group_by) {
		$this->group_by = $group_by;
		return $this;
	}
	
	/**
	 * Set limit
	 *
	 * @param integer $offset
	 * @param integer $limit
	 * @return Database_Query_Select
	 */
	function limit($offset = 0, $limit = null) {
		if ($limit === null) {
			$this->limit = $offset;
			$this->offset = 0;
		} else {
			$this->offset = $offset;
			$this->limit = $limit;
		}
		return $this;
	}
	
	/**
	 * Compile SQL statement
	 *
	 * @return string
	 */
	function __toString() {
		return $this->db->sql()->select(array(
			'what' => $this->what,
			'distinct' => $this->distinct,
			'tables' => $this->tables,
			'where' => $this->where,
			'group_by' => $this->group_by,
			'order_by' => $this->order_by,
			'offset' => $this->offset,
			'limit' => $this->limit
		));
	}
	function condition($add = null, $id = null) {
		if ($add !== null) {
			if ($id === null) {
				$this->conditions[] = $add;
			} else {
				$this->conditions[$id] = $add;
			}
			return $this;
		}
		return $this->conditions;
	}
	
	/**
	 *
	 * @return string
	 */
	function title() {
		/* @var $class Class_Object */
		$class_name = $this->class;
		$class = Object::cache_class($class_name, "class");
		$map = array(
			"noun" => $class->name,
			"nouns" => lang::plural($class->name)
		);
		if (count($this->conditions) === 0) {
			return __("Database_Query_Select-$class_name-title-all:=All {nouns}", $map);
		}
		$map['conditions'] = map(lang::conjunction($this->conditions, __("and")), $map);
		return __("Database_Query_Select-$class_name-title:={nouns} which {conditions}", $map);
	}
}
