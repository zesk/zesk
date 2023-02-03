<?php
declare(strict_types=1);

namespace zesk\SQLite3;

use SQLite3Result;
use zesk\Database;
use zesk\Database\QueryResult as BaseQueryResult;

class QueryResult extends BaseQueryResult {
	public Database $database;

	public SQLite3Result $result;

	public static function factory(Database $database, SQLite3Result $results): self {
		return new self($database, $results);
	}

	/**
	 * @param \zesk\SQLite3\Database $database
	 * @param mixed $result
	 */
	public function __construct(Database $database, mixed $result) {
		$this->database = $database;
		$this->result = $result;
	}

	/**
	 *
	 */
	public function __destruct() {
		$this->free();
	}

	/**
	 * @return void
	 */
	public function free(): void {
		$this->result->finalize();
	}

	/**
	 * @return mixed
	 */
	public function resource(): mixed {
		return $this->result;
	}
}
