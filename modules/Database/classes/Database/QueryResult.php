<?php declare(strict_types=1);

namespace zesk\Database;

use zesk\Database as Database;

abstract class QueryResult {
	abstract public function __construct(Database $database, mixed $resource);

	/**
	 * @return mixed
	 */
	abstract public function resource(): mixed;

	/**
	 * @return void
	 */
	abstract public function free(): void;
}
