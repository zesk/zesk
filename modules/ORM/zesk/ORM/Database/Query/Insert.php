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
use zesk\Database\Exception\SQLException;
use zesk\Database\Exception\TableNotFound;
use zesk\Database\QueryResult;
use zesk\Exception\Semantics;
use zesk\PHP;

/**
 *
 * @author kent
 *
 */
class Insert extends Edit
{
	/**
	 * This is a REPLACE command
	 *
	 * @var boolean
	 */
	protected bool $replace = false;

	/**
	 *
	 * @var ?Select
	 */
	protected ?Select $select = null;

	/**
	 * INSERT INTO {$this->into}
	 *
	 * @var string
	 */
	protected string $into;

	/**
	 * Result
	 *
	 * @var mixed
	 */
	protected mixed $result;

	/**
	 * Construct a new insert query
	 *
	 * @param Base $db
	 */
	public function __construct(Base $db)
	{
		parent::__construct('INSERT', $db);
	}

	/**
	 * Getter for "into" which table
	 *
	 * @return string
	 */
	public function into(): string
	{
		return $this->into;
	}

	/**
	 * Setter for "into" which table
	 *
	 * @param string $set Into table name
	 * @return self
	 */
	public function setInto(string $set): self
	{
		$this->setTable($set);
		$this->into = $set;
		return $this;
	}

	/**
	 * Get replace mode
	 *
	 * @return bool
	 */
	public function replace(): bool
	{
		return $this->replace;
	}

	/**
	 * @param bool $set
	 * @return $this
	 */
	public function setReplace(bool $set): self
	{
		$this->replace = $set;
		return $this;
	}

	/**
	 * Set to insert mode
	 *
	 * @return self
	 */
	public function insert(): self
	{
		$this->replace = false;
		return $this;
	}

	/**
	 * Insert from a SELECT query
	 *
	 * @param Select $query
	 * @return self
	 */
	public function select(Select $query): self
	{
		$this->select = $query;
		return $this;
	}

	/**
	 * Convert this query to SQL
	 *
	 * @return string
	 * @throws 0
	 */
	public function __toString()
	{
		try {
			return $this->toSQL();
		} catch (Throwable $t) {
			PHP::log($t);
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
		$options = [
			'table' => $this->into, 'low_priority' => $this->low_priority,
		];
		if ($this->replace) {
			$options['verb'] = 'REPLACE';
		}

		if ($this->select) {
			return $this->sql()->insertSelect($this->into, $this->select->what(), strval($this->select), $options);
		}
		return $this->sql()->insert($this->into, $this->values(), $options);
	}

	/**
	 * @return int|QueryResult
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws SQLException
	 * @throws TableNotFound
	 */
	private function _execute(): QueryResult|int
	{
		if ($this->select) {
			$sql = $this->__toString();
			return $this->database()->query($sql);
		}
		$options = [
			'table' => $this->into, 'values' => $this->values,
		];
		if ($this->replace) {
			$options['verb'] = 'REPLACE';
		}
		$this->result = $this->db->insert($this->into, $this->values, $options);
		return $this->result;
	}

	/**
	 * Execute the insert and retrieve the ID created
	 *
	 * @return int
	 * @throws Duplicate
	 * @throws Semantics
	 * @throws NoResults
	 * @throws SQLException
	 * @throws TableNotFound
	 */
	public function id(): int
	{
		if ($this->low_priority) {
			throw new Semantics('Can not execute query as low priority and retrieve id: ' . $this->__toString());
		}
		if ($this->select) {
			throw new Semantics('Can not execute query as select and retrieve id: ' . $this->__toString());
		}
		return $this->_execute();
	}

	/**
	 * @return $this
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws SQLException
	 * @throws TableNotFound
	 */
	public function execute(): self
	{
		$this->_execute();
		return $this;
	}

	/**
	 * Returns the inserted ID or null if non-primary key insert
	 *
	 * @return ?int
	 */
	public function result(): ?int
	{
		return $this->result;
	}
}
