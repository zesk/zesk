<?php
declare(strict_types=1);
/**
 * Delete
 *
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

use zesk\ORM\QueryTrait\Affected;
use zesk\ORM\QueryTrait\Where;

/**
 *
 * @author kent
 *
 */
class Database_Query_Update extends Database_Query_Edit {
	use Where;
	use Affected;

	/**
	 *
	 * @var resource
	 */
	private mixed $result;

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
	 * Getter for ignore constraints
	 *
	 * @param boolean $set
	 * @return self
	 */
	public function setIgnoreConstraints(bool $set) {
		$this->ignore_constraints = $set;
		return $this;
	}

	/**
	 * Getter for ignore constraints flag for update
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
			'ignore_constraints' => $this->ignore_constraints,
		]);
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
	 * @return $this
	 * @throws Exception_Semantics
	 */
	public function execute(): self {
		$this->result = $this->database()->update($this->table(), $this->values, $this->where, [
			'low_priority' => $this->low_priority,
			'ignore_constraints' => $this->ignore_constraints,
		]);
		$this->setAffectedRows($this->database()->affectedRows($this->result));
		return $this;
	}
}
