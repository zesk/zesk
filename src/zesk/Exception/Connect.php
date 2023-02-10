<?php declare(strict_types=1);

/**
 *
 */
namespace zesk;

use Throwable;

/**
 *
 */
class Exception_Connect extends Exception {
	public string $host;

	public function __construct(string $host, string $message = '', array $arguments = [], Throwable $previous =
	null) {
		parent::__construct($message, $arguments, 0, $previous);
		$this->host = $host;
	}

	public function __toString(): string {
		return $this->host . ': ' . parent::__toString();
	}
}
