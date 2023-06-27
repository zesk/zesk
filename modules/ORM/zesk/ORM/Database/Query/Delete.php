<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk\ORM\Database\Query;

use Throwable;
use zesk\Database\Base;
use zesk\Database\Exception\Duplicate;
use zesk\Database\Exception\NoResults;
use zesk\Database\Exception\TableNotFound;
use zesk\Exception\Semantics;
use zesk\ORM\Database\Query;
use zesk\ORM\Database\QueryTrait\Affected;
use zesk\ORM\Database\QueryTrait\Where;
use zesk\PHP;

/**
 *
 * @author kent
 *
 */
class Delete extends Query
{
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
	 * @param Base $db
	 */
	public function __construct(Base $db)
	{
		parent::__construct('DELETE', $db);
	}

	/**
	 * @param bool $set
	 * @return $this
	 * @throws Semantics
	 */
	public function setTruncate(bool $set): self
	{
		$this->truncate = $set;
		$this->_validateTruncate();
		return $this;
	}

	/**
	 * @return void
	 * @throws Semantics
	 */
	private function _validateTruncate(): void
	{
		if ($this->truncate === true && count($this->where) > 0) {
			throw new Semantics('Truncate not allowed with a where clause {where}', [
				'where' => $this->where,
			]);
		}
	}

	/**
	 * @return bool
	 */
	public function truncate(): bool
	{
		return $this->truncate;
	}

	/**
	 *
	 * @return string
	 */
	public function __toString()
	{
		try {
			return $this->toSQL();
		} catch (Throwable $e) {
			PHP::log($e);
			return '';
		}
	}

	/**
	 *
	 * @return string
	 */
	public function toSQL(): string
	{
		$table = $this->application->ormRegistry($this->class)->table();
		return $this->sql()->delete($table, $this->where, [
			'truncate' => $this->truncate,
		]);
	}

	/**
	 *
	 * @return mixed
	 */
	public function result(): mixed
	{
		return $this->result;
	}

	/**
	 * @return mixed
	 * @throws Semantics
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
	 */
	public function execute(): self
	{
		$this->_validateTruncate();
		$db = $this->database();
		$sql = $this->__toString();
		$this->result = $db->query($sql);
		$this->setAffectedRows($db->affectedRows($this->result));
		return $this;
	}
}
