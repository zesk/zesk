<?php
declare(strict_types=1);
/**
 * Delete
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
class Database_Query_Update extends Database_Query_Edit {
	/**
	 * Update where clause
	 *
	 * @var array
	 */
	protected array $where = [];

	/**
	 *
	 * @var resource
	 */
	private $result = null;

	/**
	 *
	 * @var boolean
	 */
	protected bool $ignore_constraints = false;

	/**
	 * Create a new UPDATE query
	 *
	 * @param Database $db
	 */
	public function __construct(Database $db) {
		parent::__construct('UPDATE', $db);
	}

	/**
	 * Getter/setter for ignore constraints flag for update
	 *
	 * @param boolean $set
	 * @return self
	 */
	public function setIgnoreConstraints(bool $set) {
		$this->ignore_constraints = $set;
		return $this;
	}

	/**
	 * Getter/setter for ignore constraints flag for update
	 *
	 * @return bool
	 */
	public function ignoreConstraints(): bool {
		return $this->ignore_constraints;
	}

	/**
	 * Convert this query to SQL
	 *
	 * @return string
	 * @throws Exception_Unimplemented
	 */
	public function __toString() {
		return $this->database()->sql()->update([
			'table' => $this->table(),
			'values' => $this->values,
			'where' => $this->where,
			'low_priority' => $this->low_priority,
		]);
	}

	/**
	 * Return the number of affected rows after query has run
	 *
	 * @return integer
	 */
	final public function affected_rows(): int {
		return $this->database()->affected_rows($this->result);
	}

	/**
	 *
	 * @return resource
	 */
	public function result() {
		return $this->result;
	}

	/**
	 * @return self
	 * @throws Exception_Deprecated
	 * @deprecated 2018-02 Use "execute()->result()" instead.
	 *
	 */
	public function exec(): self {
		zesk()->deprecated();
		return $this->execute();
	}

	/**
	 * Run this query
	 *
	 * @return $this
	 */
	public function execute(): self {
		$this->result = $this->database()->update($this->table(), $this->values, $this->where, [
			'low_priority' => $this->low_priority,
			'ignore_constraints' => $this->ignore_constraints,
		]);
		return $this;
	}

	/**
	 * Add where clause.
	 * Once traits are standard, make this a trait for SELECT/INSERT
	 *
	 * @trait
	 *
	 * @param string|array $k
	 * @param string|array $v
	 * @return $this
	 */
	public function where($k, $v = null) {
		if (is_array($k)) {
			$this->where = array_merge($this->where, $k);
		} elseif ($k === null && is_string($v)) {
			$this->where[] = $v;
		} elseif (!empty($k)) {
			$this->where[$k] = $v;
		}
		return $this;
	}
}
