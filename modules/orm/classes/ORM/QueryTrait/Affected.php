<?php
declare(strict_types=1);

namespace zesk\ORM\QueryTrait;

use zesk\Exception_Semantics;

trait Affected {
	/**
	 * Store affected rows after execute
	 *
	 * @var integer
	 */
	protected int $affected_rows = -1;

	/**
	 * @return int
	 * @throws Exception_Semantics
	 */
	public function affectedRows(): int {
		if ($this->affected_rows < 0) {
			throw new Exception_Semantics('No query would affect rows');
		}
		return $this->affected_rows;
	}

	/**
	 * Internally set and validate the value
	 *
	 * @param int $value
	 * @throws Exception_Semantics
	 */
	protected function setAffectedRows(int $value): void {
		if ($value < 0) {
			throw new Exception_Semantics('Not permitted to set a negative affected rows {value}', [
				'value' => $value,
			]);
		}
		$this->affected_rows = $value;
	}
}
