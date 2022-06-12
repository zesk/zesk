<?php
declare(strict_types=1);
/**
 * Edit
 *
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
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
	protected bool $low_priority = false;

	/**
	 *
	 * @var string
	 */
	protected string $default_alias = '';

	/**
	 * Table to update/insert
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
	 * @return string|null
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
	 * @param string $alias Optional table alias
	 * @return self
	 */
	public function class_table(string $class, string $alias = ''): self {
		$object_class = $this->application->class_ormRegistry($class);
		/* @var $object_class Class_ORM */
		if (count($this->table) === 0) {
			$this->default_alias = "$alias";
		}
		$this->table["$alias"] = $object_class->table;
		$this->setValidColumns($object_class->columnNames(), $alias);
		return $this;
	}

	/**
	 * Internal function to check validity of a column
	 *
	 * @param string $name
	 * @return boolean
	 */
	private function validColumn(string $name): bool {
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
	 * @return self
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
	private function check_column(string $name): void {
		if (!$this->validColumn($name)) {
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
	 * @param array|null $values Deprecated parameter
	 * @return array
	 * @throws Exception_Semantics
	 */
	public function values(array $values = null): array {
		if ($values === null) {
			return $this->values;
		}
		$this->application->deprecated(__METHOD__ . ' as setter');
		$this->setValues($values);
		return $this->values;
	}

	/**
	 * Pass multiple values to be inserted/updated
	 *
	 * @param array $values When null, return the current values to be set (array)
	 * @return self
	 * @throws Exception_Semantics
	 */
	public function setValues(array $values): self {
		return $this->value($values);
	}

	/**
	 * Setter for low priority state of this query
	 *
	 * @param boolean $low_priority
	 * @return self
	 */
	public function setLowPriority(bool $low_priority): self {
		$this->low_priority = $low_priority;
		return $this;
	}

	/**
	 * Get low priority state of this query
	 *
	 * @return boolean
	 */
	public function lowPriority(): bool {
		return $this->low_priority;
	}

	/**
	 * Saves the valid columns associated with a table alias.
	 *
	 * @param array $columns
	 * @param string $alias
	 * @return $this
	 */
	public function setValidColumns(array $columns, string $alias = ''): self {
		$this->valid_columns[$alias] = $columns;
		return $this;
	}

	/**
	 * Getter/setter for low priority state of this query
	 *
	 * @param boolean $low_priority
	 * @return boolean|Database_Query_Edit
	 * @deprecated 2022-05
	 */
	public function low_priority($low_priority = null): bool|self {
		$this->application->deprecated(__METHOD__);
		if ($low_priority === null) {
			return $this->lowPriority();
		}
		return $this->setLowPriority(toBool($low_priority));
	}

	/**
	 * Saves the valid columns associated with a table alias.
	 *
	 * Right now just stores it.
	 *
	 * @param array $columns
	 * @param string $alias Optional alias for the table mapping. Blank for default mapping.
	 * @return self
	 * @deprecated 2022-05
	 */
	public function valid_columns(array $columns, string $alias = ''): self {
		$this->application->deprecated(__METHOD__);
		$this->setValidColumns($columns, $alias);
		return $this;
	}
}
