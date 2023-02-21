<?php
declare(strict_types=1);

namespace zesk\Database;

abstract class QueryResult {
	abstract public function __construct(Base $database, mixed $resource);

	/**
	 * @return mixed
	 */
	abstract public function resource(): mixed;

	/**
	 * @return void
	 */
	abstract public function free(): void;
}
