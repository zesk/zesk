<?php
declare(strict_types=1);

namespace zesk\ORM\Database\QueryTrait;

trait Where
{
	/**
	 * Where
	 * @var array
	 */
	protected array $where = [];

	/**
	 * @param string $member
	 * @param mixed $value
	 * @return static
	 */
	public function addWhere(string $member, mixed $value): static
	{
		$this->where[$member] = $value;
		return $this;
	}

	/**
	 * @param string $sql
	 * @return static
	 */
	public function addWhereSQL(string $sql): static
	{
		$this->where[] = $sql;
		return $this;
	}

	/**
	 * @param array $where
	 * @return static
	 */
	public function appendWhere(array $where): static
	{
		$this->where = array_merge($this->where, $where);
		return $this;
	}

	/**
	 * @return static
	 */
	public function clearWhere(): static
	{
		$this->where = [];
		return $this;
	}
}
