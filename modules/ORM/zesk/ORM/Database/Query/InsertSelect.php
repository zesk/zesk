<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage database
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\ORM\Database\Query;

use zesk\Database\Base;
use zesk\Database\Exception\Duplicate;
use zesk\Database\Exception\NoResults;
use zesk\Database\Exception\TableNotFound;
use zesk\Database\QueryResult;
use zesk\Exception\Semantics;

/**
 * @see Database_Query_Insert
 * @author kent
 *
 */
class InsertSelect extends Select {
	/**
	 * Low priority insert/replace
	 *
	 * @var boolean
	 */
	protected bool $low_priority = false;

	/**
	 *
	 * @var string
	 */
	private string $into = '';

	/**
	 *
	 * @var array
	 */
	protected array $what = [];

	/**
	 *
	 * @var string
	 */
	protected string $verb = 'INSERT';

	/**
	 * Create a new query
	 *
	 * @param Base $db
	 * @return self
	 */
	public static function factory(Base $db): self {
		return new self($db);
	}

	/**
	 *
	 * @param Select $query
	 * @return self
	 */
	public static function fromSelect(Select $query): self {
		$new = self::factory($query->database());
		$new->copyFrom($query);
		return $new;
	}

	/**
	 * Getter for low priority state of this query
	 * @return boolean
	 */
	public function lowPriority(): bool {
		return $this->low_priority;
	}

	/**
	 * Setter for low priority state of this query
	 *
	 * @param boolean $low_priority
	 * @return self
	 */
	public function setLowPriority(bool $low_priority = true): self {
		$this->low_priority = $low_priority;
		return $this;
	}

	/**
	 * Getter for replace verb
	 *
	 * @return bool
	 */
	public function replace(): bool {
		return $this->verb === 'REPLACE';
	}

	/**
	 * Getter/setter for replace verb
	 *
	 * @param boolean $replace
	 * @return self
	 */
	public function setReplace(bool $replace = true): self {
		$this->verb = $replace ? 'REPLACE' : 'INSERT';
		return $this;
	}

	/**
	 *
	 * @param string $table
	 * @return self
	 */
	public function into(string $table): self {
		$this->into = $table;
		return $this;
	}

	/**
	 * @param string $what
	 * @return self
	 * @throws Semantics
	 */
	public function setWhatString(string $what): self {
		throw new Semantics("{class} must have an associative array for what (passed in \"$what\")", ['class' => __CLASS__]);
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		try {
			return $this->toSQL();
		} catch (Semantics $e) {
			$this->application->logger->error($e->getRawMessage(), $e->variables());
			return '';
		}
	}

	/**
	 * @throws Semantics
	 * @return string
	 */
	public function toSQL(): string {
		return $this->sql()->insertSelect($this->into, $this->what, $this->selectToSQL(), [
			'verb' => $this->verb, 'low_priority' => $this->low_priority,
		]);
	}

	/**
	 * @return QueryResult
	 * @throws Duplicate
	 * @throws NoResults
	 * @throws TableNotFound
	 */
	public function execute(): QueryResult {
		return $this->database()->query($this->__toString());
	}
}
