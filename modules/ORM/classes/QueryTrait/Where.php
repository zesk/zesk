<?php declare(strict_types=1);

namespace zesk\ORM\QueryTrait;

trait Where {
	/**
	 * Where
	 * @var array
	 */
	protected array $where = [];

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
	 * @return self
	 * @deprecated 2022-05
	 */
	public function where(array|string $k = null, mixed $v = null): self {
		if ($k === null && $v === null) {
			return $this->where;
		}
		$this->application->deprecated('where setter');
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
}
