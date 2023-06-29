<?php
declare(strict_types=1);
/**
 * Delete
 *
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\ORM\Database\Query;

use Throwable;
use zesk\Database\Base;
use zesk\Database\Exception\Duplicate;
use zesk\Database\Exception\NoResults;
use zesk\Database\Exception\TableNotFound;
use zesk\Exception\Semantics;
use zesk\ORM\Database\QueryTrait\Affected;
use zesk\ORM\Database\QueryTrait\Where;

/**
 *
 * @author kent
 *
 */
class Update extends Edit
{
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
	 * @param Base $db
	 */
	public function __construct(Base $db)
	{
		parent::__construct('UPDATE', $db);
	}

	/**
	 * Getter for ignore constraints
	 *
	 * @param boolean $set
	 * @return self
	 */
	public function setIgnoreConstraints(bool $set): self
	{
		$this->ignore_constraints = $set;
		return $this;
	}

	/**
	 * Getter for ignore constraints flag for update
	 *
	 * @return bool
	 */
	public function ignoreConstraints(): bool
	{
		return $this->ignore_constraints;
	}

	/**
	 * Convert this query to SQL
	 *
	 * @return string
	 */
	public function __toString()
	{
		try {
			return $this->toSQL();
		} catch (Throwable $e) {
			$this->application->logger->error($e);
			return '';
		}
	}

	/**
	 * Convert this query to SQL
	 *
	 * @return string
	 */
	public function toSQL(): string
	{
		return $this->database()->sqlDialect()->update([
			'table' => $this->table(), 'values' => $this->values, 'where' => $this->where,
			'low_priority' => $this->low_priority, 'ignore_constraints' => $this->ignore_constraints,
		]);
	}

	/**
	 *
	 * @return resource
	 */
	public function result()
	{
		return $this->result;
	}

	/**
	 * @return $this
	 * @throws Semantics
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
	 */
	public function execute(): self
	{
		$this->result = $this->database()->update($this->table(), $this->values, $this->where, [
			'low_priority' => $this->low_priority, 'ignore_constraints' => $this->ignore_constraints,
		]);
		$this->setAffectedRows($this->database()->affectedRows($this->result));
		return $this;
	}
}
