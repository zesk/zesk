<?php
declare(strict_types=1);
/**
 * Select Query
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/Database/Query/Select.php $
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2010, Market Acumen, Inc.
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Database_Query_Select extends Database_Query_Select_Base {
	/**
	 * What to select (array of alias => column)
	 *
	 * @var array
	 */
	protected array $what = [];

	/**
	 * $what or $what_sql are valid, never both
	 *
	 * @var string
	 */
	protected string $what_sql = "*";

	/**
	 * Array of tables to query. First is main from, others are JOINed
	 * @var array
	 */
	protected array $tables = [];

	/**
	 * Alias of main table
	 *
	 * @var string
	 */
	protected string $alias = "";

	/**
	 * Where
	 * @var array
	 */
	protected array $where = [];

	/**
	 * Having - like where for postprocessing in database based on functions
	 *
	 * @var array
	 */
	protected array $having = [];

	/**
	 * Order by clause
	 * @var array
	 */
	protected array $order_by = [];

	/**
	 * Group by clause
	 * @var array
	 */
	protected array $group_by = [];

	/**
	 * Offset
	 * @var integer
	 */
	protected int $offset = 0;

	/**
	 * Limit
	 * @var integer
	 */
	protected int $limit = -1;

	/**
	 * Distinct query
	 * @var boolean
	 */
	protected bool $distinct = false;

	/**
	 * Array of alias => class
	 * @var array
	 */
	protected array $join_objects = [];

	/**
	 * List of locale-specific conditions for outputting to the user
	 *
	 * @var array
	 */
	protected array $conditions = [];

	/**
	 * This is here solely for debugging purposes only.
	 *
	 * @var string
	 */
	protected string $generated_sql = "";

	/**
	 * Construct a new Select query
	 *
	 * @param Database $db
	 */
	public function __construct(Database $db) {
		parent::__construct("SELECT", $db);
	}

	/**
	 *
	 * @return self
	 */
	public function duplicate(): self {
		return clone $this;
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Database_Query_Select_Base::__sleep()
	 */
	public function __sleep(): array {
		return array_merge(parent::__sleep(), [
			"what",
			"what_sql",
			"tables",
			"alias",
			"where",
			"having",
			"order_by",
			"group_by",
			"offset",
			"limit",
			"distinct",
			"join_objects",
			"conditions",
		]);
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \zesk\Database_Query_Select_Base::copy_from()
	 */
	public function copy_from(Database_Query_Select $query): self {
		parent::_copy_from_base($query);

		$this->what = $query->what;
		$this->what_sql = $query->what_sql;
		$this->tables = $query->tables;
		$this->alias = $query->alias;
		$this->where = $query->where;
		$this->having = $query->having;
		$this->order_by = $query->order_by;
		$this->group_by = $query->group_by;
		$this->offset = $query->offset;
		$this->limit = $query->limit;
		$this->distinct = $query->distinct;
		$this->join_objects = $query->join_objects;
		$this->conditions = $query->conditions;

		return $this;
	}

	/**
	 * Given Foo.Whatever, is it a valid reference/colun?
	 *
	 * @param string $column_reference
	 * @return boolean
	 */
	public function valid_column(string $column_reference): bool {
		[$alias, $column] = pair($column_reference, ".", $this->alias, $column);
		if ($alias === $this->alias) {
			$class = $this->class;
		} else {
			$class = avalue($this->join_objects, $alias);
			if (!$class) {
				return false;
			}
		}
		return $this->orm_registry($class)->has_member($column);
	}

	/**
	 * Create a new query
	 *
	 * @param Database $db
	 * @return Database_Query_Select
	 */
	public static function factory(Database $db): self {
		return new Database_Query_Select($db);
	}

	/**
	 * Set as a distinct or non-distinct query
	 *
	 * @param mixed $set
	 * @return bool
	 */
	public function distinct(mixed $set = true): mixed {
		if ($set !== null) {
			$this->application->deprecated("setter");
			return $this->setDistinct(to_bool($set));
		}
		return $this->distinct;
	}

	/**
	 * @param bool $set
	 * @return $this
	 */
	public function setDistinct(bool $set = true): self {
		$this->distinct = $set;
		return $this;
	}

	public function class_alias($class = null) {
		if ($class === null || $this->class === $class) {
			return $this->alias;
		}
		$result = avalue(ArrayTools::flip_multiple($this->join_objects), $class, []);
		return last($result);
	}

	/**
	 * @return string
	 */
	public function alias($set = null) {
		if ($set !== null) {
			$this->application->deprecated("setter");
			return $this->setAlias(strval($set));
		}
		return $this->alias;
	}

	/**
	 * @param string $set
	 * @return self
	 */
	public function setAlias(string $set): self {
		$this->alias = $set;
		return $this;
	}

	/**
	 * @return array
	 */
	public function columns(): array {
		return ArrayTools::unprefix(array_keys($this->what), "*");
	}

	/**
	 * @param $column
	 * @return bool
	 */
	public function hasWhat(string $column): bool {
		return in_array($column, $this->columns());
	}

	/**
	 * Sets the WHAT to a string and replcaes any existing what clause
	 *
	 * @param string $what
	 * @return void
	 */
	public function setWhatString(string $what): self {
		$this->what_sql = $what;
		$this->what = [];
		return $this;
	}

	/**
	 * Sets the what clause to empty
	 *
	 * @return $this
	 */
	public function clearWhat(): self {
		$this->what = [];
		$this->what_sql = "";
		return $this;
	}

	/**
	 * @param iterable $what Keys are alias, values are member
	 * @return $this
	 */
	public function addWhatIterable(iterable $what): self {
		foreach ($what as $alias => $member) {
			$this->addWhat($alias, $member);
		}
		return $this;
	}

	/**
	 * @param string $member
	 * @return $this
	 */
	public function removeWhat(string $alias): self {
		$alias = StringTools::unprefix($alias, "*");
		unset($this->what[$alias]);
		unset($this->what["*$alias"]);
		return $this;
	}

	public function addWhat(string $alias, string $member = ""): self {
		$cleaned_alias = StringTools::unprefix($alias, "*");
		unset($this->what[$cleaned_alias]);
		unset($this->what["*$cleaned_alias"]);
		$this->what[$alias] = $member === "" ? $alias : $member;
		$this->what_sql = "";
		return $this;
	}

	/**
	 * @param Database_Query_Select $query
	 * @return $this
	 */
	public function addWhatSelect(Database_Query_Select $query): self {
		$this->what += $query->what;
		return $this;
	}

	/**
	 * This is too confusing, refactor with setWhat 2022
	 *
	 * Initialize or append the what clause
	 *
	 * ->what() returns the what clause
	 * ->what(null, "string") sets the what clause to a static string (no checking)
	 * ->what("string", null) deletes a member from the what clause
	 * ->what(return_column, table_column) adds "table_column as return_column" to what clause
	 * ->what(array(...), true) appends to the current what clause
	 * ->what(array(...)) replaces the current what clause
	 * ->what(false) sets the what clause to empty
	 * ->what(object of type Database_Query_Select) replaces the current what clause
	 * ->what(object of type Database_Query_Select, true) appends the current what clause
	 *
	 * Passing an array should be of the form: "result name" => "table column reference"
	 *
	 * @param ?mixed $mixed
	 * @param ?mixed $value
	 * @return mixed
	 * @throws Exception_Parameter
	 */
	public function what(mixed $mixed = null, mixed $value = null): mixed {
		if ($mixed === null && $value === null) {
			return $this->what;
		}
		$this->application->deprecated("Setter what() no longer supported");
		if ($mixed === null && is_string($value)) {
			return $this->setWhatString($value);
		}
		if ($mixed === false && $value === null) {
			return $this->clearWhat();
		}
		if ($this->what_sql !== "") {
			$this->what_sql = "";
			$this->what = [];
		}
		if (is_string($mixed)) {
			if ($value === null) {
				return $this->removeWhat($mixed);
			}
			return $this->addWhat($mixed, $value);
		}
		if (is_array($mixed)) {
			if ($value !== true) {
				$this->clearWhat();
			}
			return $this->addWhatIterable($mixed);
		}
		if ($mixed instanceof Database_Query_Select) {
			if ($value !== true) {
				$this->clearWhat();
			}
			return $this->addWhatSelect($mixed);
		}

		throw new Exception_Parameter("Unknown parameter passed to Database_Query_Select::what(" . gettype($mixed) . ")");
	}

	/**
	 * Append "what" fields for an entire ORM, with $prefix before it, using alias $alias
	 *
	 * @param string $class Class to add what fields for; if not supplied uses the class associated with the query
	 * @param string $alias the alias associated with the class query, uses default (X) if not supplied
	 * @param string $prefix Prefix all output field names with this string, blank for nothing
	 * @param string $object_mixed Pass to class_table_columns for dynamic table objects
	 * @param array $object_options Pass to class_table_columns for dynamic table objects
	 * @return Database_Query_Select
	 */
	public function ormWhat(string $class = null, string $alias = null, string $prefix = null, mixed $object_mixed = null, array $object_options = []): self {
		if ($class === null) {
			$class = $this->orm_class();
		}
		if ($alias === null) {
			$alias = $this->class_alias($class);
		}
		$columns = $this->application->orm_registry($class, $object_mixed, $object_options)->columns();
		$what = [];
		foreach ($columns as $column) {
			$this->addWhat($prefix . $column, "$alias.$column");
		}
		$this->objects_prefixes[$prefix] = [$alias, $class, ];
		return $this;
	}

	/**
	 * Sets "from" table and main alias
	 *
	 * @param string $table
	 * @param string $alias
	 * @return Database_Query_Select
	 */
	public function from(string $table, string $alias = ""): self {
		$this->tables[$alias] = $table;
		$this->setAlias($alias);
		return $this;
	}

	/**
	 * Join tables
	 *
	 * @param string $sql
	 * @param string $join_id
	 * @return Database_Query_Select
	 */
	public function join($sql, $join_id = null): self {
		if (is_array($sql)) {
			return $this->addJoinIterable($sql);
		}
		return $this->addJoin($sql, is_string($join_id) ? $join_id : "");
	}

	/**
	 * Join tables
	 *
	 * @param string $sql
	 * @param string $join_id
	 * @return Database_Query_Select
	 */
	public function addJoinIterable(array $join_sql): self {
		$this->tables = array_merge($this->tables, $join_sql);
		return $this;
	}

	/**
	 * Join tables
	 *
	 * @param string $sql
	 * @param string $join_id
	 * @return Database_Query_Select
	 */
	public function addJoin(string $join_sql, string $join_id = ""): self {
		if ($join_id !== "") {
			$this->tables[$join_id] = $join_sql;
		} else {
			$this->tables[] = $join_sql;
		}
		return $this;
	}

	/**
	 * Given a table alias, find the associated class
	 *
	 * @param string $alias
	 * @return string Class name associated with the alias, or null if not found
	 */
	public function find_alias($alias) {
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
	public function join_object($join_type, $class, $alias, array $on, $table = null) {
		$object = null;
		if ($class instanceof ORM) {
			$object = $class;
			$class = get_class($class);
		} else {
			$object = $this->orm_registry($class);
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
		/*
		 * $object->database_name() is sometimes blank, sometimes "default" here so it uses the more complex
		 * database name to join tables here, which is not what we want.
		 *
		 * Using $object->database()->code_name() means it fetches it from the actual database.
		 *
		 * You can also try and fix this with logic:
		 *
		 * empty "database_name" means value of configuration zesk\Database::default
		 */
		if ($object->database()->code_name() !== $this->database_name()) {
			$cross_db_this = $this->database()->feature(Database::FEATURE_CROSS_DATABASE_QUERIES);
			$cross_db_object = $object->database()->feature(Database::FEATURE_CROSS_DATABASE_QUERIES);
			if ($cross_db_this !== true) {
				throw new Exception_Semantics("Database {name} ({class}) does not support cross-database queries, join is not possible", [
					"name" => $this->database_name(),
					"class" => $this->class,
				]);
			}
			if ($cross_db_object !== true) {
				throw new Exception_Semantics("Database {name} ({class}) does not support cross-database queries, join is not possible", [
					"name" => $object->database_name(),
					"class" => get_class($object),
				]);
			}
			$table_as = $sql->database_table_as($object->database()->database_name(), $table, $alias);
		} else {
			$table_as = $sql->table_as($table, $alias);
		}
		return $this->join("$join_type JOIN $table_as ON " . $this->sql()->where_clause($on));
	}

	/**
	 * Link to another object
	 *
	 * @param mixed $class
	 *            An object or a class name
	 * @param string|array $mixed
	 *            Parameters to join with this object
	 *        Options are:
	 *         "path": The path to traverse to determine how this object links to the class specified. These are member names separated by dots.
	 *         "alias": Assign the alias specified to the class specified; intermediate table aliases are generated dynamically based on this name.
	 *         "type": Type of join, e.g. "INNER", or "LEFT OUTER", or "CROSS" or whatever database-specific join you want. Defaults to "INNER".
	 *         "require": Boolean value, when specified and when "type" is not specified, specifies "INNER" or "LEFT OUTER" for type. Defaults to false.
	 *
	 * @return Database_Query_Select
	 */
	public function link(string $class, string|array $mixed = []) {
		if (is_string($mixed)) {
			$mixed = [
				"path" => $mixed,
			];
		}
		$path = $mixed['path'] ?? null;
		$object = $this->orm_registry($this->class);
		if ($path === null) {
			$target_class = $this->application->objects->resolve($class);
			$path = $object->link_default_path_to($target_class);
			if ($path === null) {
				throw new Exception_Semantics("No path to {target_class} (resolved from {class}) from $this->class, specify explicitly", [
					"class" => $class,
					"target_class" => $target_class,
				]);
			}
			$mixed['path'] = $path;
		}
		return $object->link_walk($this, $mixed);

		//		return ORM::cache_object($class)->link($this, $mixed);
	}

	/**
	 * Get/set/append having clause. Does no validation.
	 *
	 * @param array $add
	 * @param boolean $replace
	 * @return self|array
	 */
	public function having(array $add = null, $replace = false) {
		if ($add !== null) {
			$this->having = $replace ? $add : $add + $this->having;
			return $this;
		}
		return $this->having;
	}

	/**
	 * @param string $member
	 * @param mixed $value
	 * @return $this
	 */
	public function addWhere(string $member, mixed $value): self {
		$this->where[$member] = $value;
		return $this;
	}

	/**
	 * @param string $sql
	 * @return $this
	 */
	public function addWhereSQL(string $sql): self {
		$this->where[] = $sql;
		return $this;
	}

	/**
	 * @param array $where
	 * @return $this
	 */
	public function appendWhere(array $where): self {
		$this->where = array_merge($this->where, $where);
		return $this;
	}

	/**
	 * @return $this
	 */
	public function clearWhere(): self {
		$this->where = [];
		return $this;
	}

	/**
	 * Add where clause. Pass in false for $k to reset where to nothing.
	 *
	 * @param mixed $k
	 * @param string $v
	 * @return Database_Query_Select
	 */
	public function where($k = null, $v = null) {
		if ($k === null && $v === null) {
			return $this->where;
		}
		$this->application->deprecated("where setter");
		if (is_array($k)) {
			return $this->appendWhere($k);
		} elseif ($k === null && is_string($v)) {
			return $this->addWhereSQL($v);
		} elseif (!empty($k)) {
			return $this->addWhere($k, $v);
			$this->where[$k] = $v;
		} elseif ($k === false) {
			return $this->clearWhere();
		}
		return $this;
	}

	/**
	 * Set order by clause
	 *
	 * @param array $order_by
	 * @return Database_Query_Select
	 */
	public function orderBy(array $order_by): self {
		$this->order_by = $order_by;
		return $this;
	}

	/**
	 * Set group by clause
	 *
	 * @param array $group_by
	 * @return Database_Query_Select
	 */
	public function groupBy(array $group_by): self {
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
	public function limit($offset = 0, $limit = null) {
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
	public function __toString() {
		return $this->generated_sql = $this->db->sql()->select([
			'what' => $this->what_sql ?: $this->what,
			'distinct' => $this->distinct,
			'tables' => $this->tables,
			'where' => $this->where,
			'having' => $this->having,
			'group_by' => $this->group_by,
			'order_by' => $this->order_by,
			'offset' => $this->offset,
			'limit' => $this->limit,
		]);
	}

	public function condition($add = null, $id = null) {
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
	public function title() {
		/* @var $class Class_ORM */
		$class_name = $this->class;
		$locale = $this->application->locale;
		$class = $this->application->class_orm_registry($class_name);
		$map = [
			"noun" => $class->name,
			"nouns" => $locale->plural($class->name),
		];
		if (count($this->conditions) === 0) {
			return $locale->__("Database_Query_Select-$class_name-title-all:=All {nouns}", $map);
		}
		$map['conditions'] = map($locale->conjunction($this->conditions, $locale->__("and")), $map);
		return $locale->__("Database_Query_Select-$class_name-title:={nouns} which {conditions}", $map);
	}

	/**
	 * Set group by clause
	 *
	 * @param string $group_by
	 * @return Database_Query_Select
	 * @deprecated 2022-01
	 */
	public function group_by(array|string $group_by) {
		return $this->groupBy(to_list($group_by));
	}

	/**
	 * Set order by clause
	 *
	 * @param string $order_by
	 * @return Database_Query_Select
	 * @deprecated 2022-01
	 */
	public function order_by($order_by = null) {
		if ($order_by === null) {
			return $this->order_by;
		}
		return $this->orderBy(to_list($order_by));
	}

	/**
	 * Does a column alias exist in the query?
	 *
	 * @param $column
	 * @return bool
	 * @deprecated 2022-01
	 */
	public function has_what(string $column): bool {
		$this->application->deprecated("old name");
		return $this->hasWhat($column);
	}
}
