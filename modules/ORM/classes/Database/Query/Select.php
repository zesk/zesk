<?php
declare(strict_types=1);
/**
 * Select Query
 *
 * @version $URL: https://code.marketacumen.com/zesk/trunk/classes/Database/Query/Select.php $
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\ORM;

use zesk\Exception_Class_NotFound;
use zesk\Exception_Configuration;
use zesk\Exception_Deprecated;
use zesk\Exception_Key;
use zesk\Exception_Unimplemented;
use zesk\ORM\QueryTrait\Where;
use zesk\Database;
use zesk\ArrayTools;
use zesk\StringTools;
use zesk\Exception_Semantics;
use zesk\RuntimeException;

/**
 *
 * @author kent
 *
 */
class Database_Query_Select extends Database_Query_Select_Base {
	use Where;

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
	protected string $what_sql = '*';

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
	protected string $alias = '';

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
	protected string $generated_sql = '';

	/**
	 * Construct a new Select query
	 *
	 * @param Database $db
	 */
	public function __construct(Database $db) {
		parent::__construct('SELECT', $db);
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
			'what', 'what_sql', 'tables', 'alias', 'where', 'having', 'order_by', 'group_by', 'offset', 'limit',
			'distinct', 'join_objects', 'conditions',
		]);
	}

	/**
	 * @param Database_Query_Select $query
	 * @return $this
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
	 * Given "Foo.Whatever", is it a valid reference/column?
	 *
	 * @param string $column_reference
	 * @return boolean
	 */
	public function validColumn(string $column_reference): bool {
		[$alias, $column] = pair($column_reference, '.', $this->alias, $column_reference);
		if ($alias === $this->alias) {
			$class = $this->class;
		} else {
			$class = $this->join_objects[$alias] ?? null;
			if (!$class) {
				return false;
			}
		}
		return $this->ormRegistry($class)->hasMember($column);
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
	 * @return bool
	 */
	public function distinct(): bool {
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

	/**
	 * Get the class alias for this object
	 * @param string $class
	 * @return string
	 * @throws Exception_ORMNotFound
	 */
	public function class_alias(string $class = ''): string {
		if ($class === '' || $this->class === $class) {
			return $this->alias;
		}
		$reverse_joins = ArrayTools::valuesFlipAppend($this->join_objects);
		if (array_key_exists($class, $reverse_joins)) {
			return $reverse_joins[$class];
		}

		throw new Exception_ORMNotFound($class, '{class} is not a member of any joins in {this}', [
			'this' => get_class($this),
		]);
	}

	/**
	 * @return string
	 */
	public function alias(): string {
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
		return ArrayTools::valuesRemovePrefix(array_keys($this->what), '*');
	}

	/**
	 * @param string $column
	 * @return bool
	 */
	public function hasWhat(string $column): bool {
		return in_array($column, $this->columns());
	}

	/**
	 * Sets the WHAT to a string and replaces any existing what clause
	 *
	 * @param string $what
	 * @return self
	 */
	public function setWhatString(string $what): self {
		$this->what_sql = $what;
		$this->what = [];
		return $this;
	}

	/**
	 * Sets what clause to empty
	 *
	 * @return $this
	 */
	public function clearWhat(): self {
		$this->what = [];
		$this->what_sql = '';
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
	 * @param string $alias
	 * @return $this
	 */
	public function removeWhat(string $alias): self {
		$alias = StringTools::removePrefix($alias, '*');
		unset($this->what[$alias]);
		unset($this->what["*$alias"]);
		return $this;
	}

	/**
	 * @param string $alias
	 * @param string $member
	 * @return $this
	 */
	public function addWhat(string $alias, string $member = ''): self {
		$cleaned_alias = StringTools::removePrefix($alias, '*');
		unset($this->what[$cleaned_alias]);
		unset($this->what["*$cleaned_alias"]);
		$this->what[$alias] = $member === '' ? $alias : $member;
		$this->what_sql = '';
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
	 * Return what clause string or array
	 * @return string|array
	 */
	public function what(): string|array {
		return $this->what;
	}

	/**
	 * Append "what" fields for an entire ORM, with $prefix before it, using alias $alias
	 *
	 * @param ?string $class Class to add what fields for; if not supplied uses the class associated with the query
	 * @param ?string $alias the alias associated with the class query, uses default (X) if not supplied
	 * @param ?string $prefix Prefix all output field names with this string, blank for nothing
	 * @param ?string $object_mixed Pass to class_table_columns for dynamic table objects
	 * @param array $object_options Pass to class_table_columns for dynamic table objects
	 * @return Database_Query_Select
	 * @throws Exception_ORMNotFound
	 */
	public function ormWhat(string $class = null, string $alias = null, string $prefix = null, mixed $object_mixed = null, array $object_options = []): self {
		if ($class === null) {
			$class = $this->ormClass();
		}
		if ($alias === null) {
			$alias = $this->class_alias($class);
		}
		$columns = $this->application->ormRegistry($class, $object_mixed, $object_options)->columns();
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
	public function from(string $table, string $alias = ''): self {
		$this->tables[$alias] = $table;
		$this->setAlias($alias);
		return $this;
	}

	/**
	 * Join tables
	 *
	 * @param array|string $sql
	 * @param string $join_id
	 * @return Database_Query_Select
	 */
	public function join(array|string $sql, string $join_id = ''): self {
		if (is_array($sql)) {
			return $this->addJoinIterable($sql);
		}
		return $this->addJoin($sql, $join_id);
	}

	/**
	 * @param array $join_sql
	 * @return $this
	 */
	public function addJoinIterable(array $join_sql): self {
		$this->tables = array_merge($this->tables, $join_sql);
		return $this;
	}

	/**
	 * Join tables
	 *
	 * @param string $join_sql
	 * @param string $join_id
	 * @return $this
	 */
	public function addJoin(string $join_sql, string $join_id = ''): self {
		if ($join_id !== '') {
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
	 * @return string Class name associated with the alias, or "" if not found
	 */
	public function findAlias(string $alias): string {
		if ($alias === $this->alias) {
			return $this->class;
		}
		return $this->join_objects[$alias] ?? '';
	}

	/**
	 * Join tables
	 *
	 * @param string $join_type
	 * @param ORMBase|string $class
	 * @param string $alias
	 * @param array $on
	 * @param string $table
	 * @return $this
	 * @throws Exception_ORMNotFound
	 * @throws Exception_Semantics
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Key
	 */
	public function join_object(string $join_type, ORMBase|string $class, string $alias, array $on, string $table = ''): self {
		if (is_string($class)) {
			$object = $this->ormRegistry($class);
		} else {
			$object = $class;
			$class = $object::class;
		}
		if (array_key_exists($alias, $this->join_objects)) {
			throw new Exception_Semantics(__CLASS__ . "::join_object: Same alias $alias added twice");
		}
		$this->join_objects[$alias] = $class;

		$sql = $this->sql();
		if ($table === '') {
			$table = $object->table();
		}
		if ($alias === '') {
			$alias = $class;
		}
		/*
		 * $object->databaseName() is sometimes blank, sometimes "default" here, so it uses the more complex
		 * database name to join tables here, which is not what we want.
		 *
		 * Using $object->database()->codeName() means it fetches it from the actual database.
		 *
		 * You can also try and fix this with logic:
		 *
		 * empty "database_name" means value of configuration zesk\Database::default
		 */
		if ($object->database()->codeName() !== $this->databaseName()) {
			$cross_db_this = $this->database()->feature(Database::FEATURE_CROSS_DATABASE_QUERIES);
			$cross_db_object = $object->database()->feature(Database::FEATURE_CROSS_DATABASE_QUERIES);
			if ($cross_db_this !== true) {
				throw new Exception_Semantics('Database {name} ({class}) does not support cross-database queries, join is not possible', [
					'name' => $this->databaseName(), 'class' => $this->class,
				]);
			}
			if ($cross_db_object !== true) {
				throw new Exception_Semantics('Database {name} ({class}) does not support cross-database queries, join is not possible', [
					'name' => $object->databaseName(), 'class' => $object::class,
				]);
			}
			$table_as = $sql->database_table_as($object->database()->databaseName(), $table, $alias);
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
	 * @return $this
	 * @throws Exception_Configuration
	 * @throws Exception_Semantics
	 */
	public function link(string $class, string|array $mixed = []): self {
		if (is_string($mixed)) {
			$mixed = [
				'path' => $mixed,
			];
		}
		$path = $mixed['path'] ?? null;
		$object = $this->ormRegistry($this->class);
		if ($path === null) {
			$target_class = $this->application->objects->resolve($class);

			try {
				$path = $object->link_default_path_to($target_class);
			} catch (Exception_ORMNotFound) {
				throw new RuntimeException("No path to {target_class} (resolved from {class}) from $this->class, specify explicitly", [
					'class' => $class, 'target_class' => $target_class,
				]);
			}
			$mixed['path'] = $path;
		}
		return $object->linkWalk($this, $mixed);

		//		return ORM::cache_object($class)->link($this, $mixed);
	}

	/**
	 * Get/set/append having clause. Does no validation.
	 *
	 * @return array
	 */
	public function having(): array {
		return $this->having;
	}

	/**
	 * Get/set/append having clause. Does no validation.
	 *
	 * @param array $add
	 * @param boolean $replace
	 * @return self
	 */
	public function addHaving(array $add, bool $replace = false): self {
		$this->having = $replace ? $add : $add + $this->having;
		return $this;
	}

	/**
	 * Set order by clause
	 *
	 * @param array $order_by
	 * @return Database_Query_Select
	 */
	public function setOrderBy(array $order_by): self {
		$this->order_by = $order_by;
		return $this;
	}

	/**
	 * Set group by clause
	 *
	 * @param array $group_by
	 * @return Database_Query_Select
	 */
	public function setGroupBy(array $group_by): self {
		$this->group_by = $group_by;
		return $this;
	}

	/**
	 * Set limit
	 *
	 * @param int $offset
	 * @param int $limit
	 * @return Database_Query_Select
	 * @deprecated 2022-05
	 */
	public function limit(int $offset = 0, int $limit = -1): self {
		if ($limit < -0) {
			$this->limit = $offset;
			$this->offset = 0;
		} else {
			$this->offset = $offset;
			$this->limit = $limit;
		}
		return $this;
	}

	/**
	 * Set offset and limit for paging
	 *
	 * @param int $offset
	 * @param int $limit
	 * @return Database_Query_Select
	 */
	public function setOffsetLimit(int $offset = 0, int $limit = -1): self {
		$this->offset = $offset;
		$this->limit = $limit;
		return $this;
	}

	/**
	 * Compile SQL statement
	 *
	 * @return string
	 */
	public function __toString(): string {
		try {
			return $this->toSQL();
		} catch (Exception_Semantics $e) {
			PHP::log($e);
			return '';
		}
	}

	/**
	 * Compile SQL statement
	 *
	 * @return string
	 * @throws Exception_Semantics
	 */
	public function toSQL(): string {
		return $this->generated_sql = $this->db->sql()->select([
			'what' => $this->what_sql ?: $this->what, 'distinct' => $this->distinct, 'tables' => $this->tables,
			'where' => $this->where, 'having' => $this->having, 'group_by' => $this->group_by,
			'order_by' => $this->order_by, 'offset' => $this->offset, 'limit' => $this->limit,
		]);
	}

	/**
	 * Simple way of storing locale conditions of the query for display
	 *
	 * @param string $add
	 * @param string $id Option key for this condition
	 * @return $this
	 */
	public function addCondition(string $add, string $id = ''): self {
		if ($id === '') {
			$this->conditions[] = $add;
		} else {
			$this->conditions[$id] = $add;
		}
		return $this;
	}

	/**
	 * @return array
	 */
	public function conditions(): array {
		return $this->conditions;
	}

	/**
	 *
	 * @return string
	 */
	public function title(): string {
		/* @var $class Class_Base */
		$class_name = $this->class;
		$locale = $this->application->locale;
		$class = $this->application->class_ormRegistry($class_name);
		$map = [
			'noun' => $class->name, 'nouns' => $locale->plural($class->name),
		];
		if (count($this->conditions) === 0) {
			return $locale->__("Database_Query_Select-$class_name-title-all:=All {nouns}", $map);
		}
		$map['conditions'] = map($locale->conjunction($this->conditions, $locale->__('and')), $map);
		return $locale->__("Database_Query_Select-$class_name-title:={nouns} which {conditions}", $map);
	}

	/**
	 * Set group by clause
	 *
	 * @param array|string $group_by
	 * @return self
	 * @throws Exception_Deprecated
	 * @deprecated 2022-01
	 */
	public function group_by(array|string $group_by): self {
		$this->application->deprecated(__METHOD__);
		return $this->setGroupBy(toList($group_by));
	}

	/**
	 * Set order by clause
	 *
	 * @param array|string|null $order_by
	 * @return array|$this
	 * @throws Exception_Deprecated
	 */
	public function order_by(array|string $order_by = null): array|self {
		$this->application->deprecated(__METHOD__);
		if ($order_by === null) {
			return $this->order_by;
		}
		return $this->setOrderBy(toList($order_by));
	}

	/**
	 * @param string|null $add
	 * @param string $id
	 * @return $this|array
	 * @throws Exception_Deprecated
	 * @deprecated 2022-05
	 */
	public function condition(string $add = null, string $id = ''): self|array {
		$this->application->deprecated(__METHOD__);
		return ($add !== null) ? $this->addCondition($add, $id) : $this->conditions();
	}
}
