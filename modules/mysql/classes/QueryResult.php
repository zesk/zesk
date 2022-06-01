<?php declare(strict_types=1);

namespace MySQL;

use zesk\Database\QueryResult as BaseQueryResult;
use zesk\Database;

class QueryResult extends BaseQueryResult {
	public Database $database;

	public ?\mysqli_result $resource;

	public bool $result;

	public function __construct(Database $database, mixed $results) {
		$this->database = $database;
		if (is_bool($results)) {
			$this->result = $results;
			$this->resource = null;
		} else {
			$this->result = true;
			$this->resource = $results;
		}
	}

	public function __destruct() {
		$this->free();
	}

	public function free(): void {
		if ($this->resource) {
			mysqli_free_result($this->resource);
			$this->resource = null;
		}
	}

	/**
	 * @return mixed
	 */
	public function resource(): mixed {
		return $this->resource;
	}
}
