<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk\ORM;

use zesk\Database;
use zesk\Database_Exception;
use zesk\Database_Exception_Duplicate;
use zesk\Database_Exception_SQL;
use zesk\Database_Exception_Table_NotFound;
use zesk\Exception_Class_NotFound;
use zesk\Exception_Deprecated;
use zesk\Exception_Semantics;
use zesk\ORM\QueryTrait\Affected;
use zesk\ORM\QueryTrait\Where;

/**
 *
 * @author kent
 *
 */
class Database_Query_Delete extends Database_Query {
	use Where;
	use Affected;

	/**
	 * Whether to use truncate instead of DELETE when deleting a table
	 *
	 * @var bool
	 */
	protected bool $truncate = false;

	/**
	 *
	 * @var mixed
	 */
	protected mixed $result = null;

	/**
	 * Construct a delete query
	 *
	 * @param Database $db
	 */
	public function __construct(Database $db) {
		parent::__construct('DELETE', $db);
	}

	/**
	 * @param bool $set
	 * @return $this
	 * @throws Exception_Semantics
	 */
	public function setTruncate(bool $set): self {
		$this->truncate = $set;
		$this->_validate_truncate();
		return $this;
	}

	/**
	 * @return void
	 * @throws Exception_Semantics
	 */
	private function _validate_truncate(): void {
		if ($this->truncate === true && count($this->where) > 0) {
			throw new Exception_Semantics('Truncate not allowed with a where clause {where}', [
				'where' => $this->where,
			]);
		}
	}

	/**
	 * @param $set
	 * @return bool
	 * @throws Exception_Deprecated
	 * @throws Exception_Semantics
	 */
	public function truncate($set = null): bool {
		if ($set !== null) {
			zesk()->deprecated('setter');
			$this->setTruncate($set);
		}
		return $this->truncate;
	}

	/**
	 *
	 * @return string
	 */
	public function __toString() {
		try {
			return $this->toSQL();
		} catch (\Throwable $e) {
			PHP::log($e);
			return '';
		}
	}

	/**
	 *
	 * @return string
	 */
	public function toSQL(): string {
		$table = $this->application->ormRegistry($this->class)->table();
		return $this->sql()->delete($table, $this->where, [
			'truncate' => $this->truncate,
		]);
	}

	/**
	 *
	 * @return mixed
	 */
	public function result(): mixed {
		return $this->result;
	}

	/**
	 * @return mixed
	 * @throws Database_Exception
	 * @throws Database_Exception_Duplicate
	 * @throws Database_Exception_SQL
	 * @throws Database_Exception_Table_NotFound
	 * @throws Exception_Semantics
	 */
	public function execute(): self {
		$this->_validate_truncate();
		$db = $this->database();
		$sql = $this->__toString();
		$this->result = $db->query($sql);
		$this->setAffectedRows($db->affectedRows($this->result));
		return $this;
	}
}
