<?php
declare(strict_types=1);
/**
 * Edit
 *
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
abstract class Database_Query_Edit extends Database_Query {
	/**
	 * Low priority update/insert
	 *
	 * @var boolean
	 */
	protected $low_priority = false;

	/**
	 *
	 * @var string
	 */
	protected string $default_alias = '';

	/**
	 * Table we're update/insert
	 *
	 * @var array
	 */
	protected array $table = [];

	/**
	 * Name => Value of things we're updating/inserting
	 *
	 * @var array
	 */
	protected array $values = [];

	/**
	 * Array of columns valid for this table
	 *
	 * @var array
	 */
	protected array $valid_columns = [];

	/**
	 * Get/Set the table for this query
	 *
	 * @param string $table Table to include in the query.
	 * @param string $alias Optional alias. Blank ("") is the default table.
	 * @return Database_Query_Edit
	 */
	public function table(): ?string {
		return $this->table[$this->default_alias] ?? null;
	}

	public function defaultAlias(): string {
		return $this->default_alias;
	}

	/**
	 * Get/Set the table for this query
	 *
	 * @param string $table Table to include in the query.
	 * @param string $alias Optional alias. Blank ("") is the default table.
	 * @return Database_Query_Edit
	 */
	public function setTable(string $table, string $alias = ''): self {
		if (count($this->table) === 0) {
			$this->default_alias = $alias;
		}
		$this->table["$alias"] = $table;
		return $this;
	}

	/**
	 * Get/Set the table for this query
	 *
	 * @param string $class ORM Class name
	 * @param string|null $alias Optional table alias
	 * @return Database_Query_Edit
	 */
	public function class_table($class, string $alias = '') {
		$object_class = $this->application->class_orm_registry($class);
		/* @var $object_class Class_ORM */
		if (count($this->table) === 0) {
			$this->default_alias = "$alias";
		}
		$this->table["$alias"] = $object_class->table;
		$this->valid_columns($object_class->column_names(), $alias);
		return $this;
	}

	/**
	 * Internal function to check validity of a column
	 *
	 * @param string $name
	 * @return boolean
	 */
	private function valid_column(string $name): bool {
		$clean_name = ltrim($name, '*');
		[$alias, $clean_name] = pair($clean_name, '.', $this->default_alias, $clean_name);
		$columns = $this->valid_columns[$alias] ?? null;
		if (!is_array($columns) || !in_array($clean_name, $columns)) {
			return false;
		}
		return true;
	}

	/**
	 * Add a name/value pair to be updated in this query
	 *
	 * @param string|array $name Alternately, pass an array as this value to update multiple values
	 * @param mixed $value When $name is a string, this is the value set
	 * @return Database_Query_Edit|Database_Query_Update
	 * @throws Exception_Semantics
	 */
	public function value(array|string $name, mixed $value = null): self {
		if (is_array($name)) {
			foreach ($name as $k => $v) {
				$this->value($k, $v);
			}
			return $this;
		}
		$this->check_column($name);
		$this->values[$name] = $value;
		return $this;
	}

	/**
	 * Internal function to check a column for validity.
	 * If not, throw an exception.
	 *
	 * @param string $name
	 * @throws Exception_Semantics
	 */
	private function check_column($name): void {
		if (!$this->valid_column($name)) {
			throw new Exception_Semantics('Column {name} is not in table {table} (columns are {columns})', [
				'name' => $name,
				'table' => $this->table,
				'columns' => $this->valid_columns,
				'Database_Query_Edit' => $this,
			]);
		}
	}

	/**
	 * Pass multiple values to be inserted/updated
	 *
	 * @param array $values When null, return the current values to be set (array)
	 * @return Database_Query_Edit|Database_Query_Update|array
	 * @throws Exception_Semantics
	 */
	public function values(array $values = null) {
		if ($values === null) {
			return $this->values;
		}
		return $this->value($values);
	}

	/**
	 * Getter/setter for low priority state of this query
	 *
	 * @param boolean $low_priority
	 * @return boolean|Database_Query_Edit
	 */
	public function low_priority($low_priority = null) {
		if ($low_priority === null) {
			return $this->low_priority;
		}
		$this->low_priority = to_bool($low_priority);
		return $this;
	}

	/**
	 * Saves the valid columns associated with a table alias.
	 *
	 * Right now just stores it.
	 *
	 * @param array $columns
	 * @param string|null $alias Optional alias for the table mapping. Blank for default mapping.
	 * @return self
	 */
	public function valid_columns(array $columns, $alias = null) {
		$this->valid_columns["$alias"] = $columns;
		return $this;
	}
}
