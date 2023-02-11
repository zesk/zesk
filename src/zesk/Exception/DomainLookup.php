<?php
declare(strict_types=1);
/**
 *
 */
namespace zesk;

use Throwable;

/**
 *
 * @author kent
 *
 */
class Exception_DomainLookup extends Exception {
	/**
	 *
	 * @var string
	 */
	public $host = null;

	/**
	 *
	 * @param string $host
	 * @param string $message
	 * @param array $arguments
	 * @param int $code
	 * @param Throwable $previous
	 */
	public function __construct(string $host, string $message, array $arguments = [], int $code = 0, Throwable
	$previous =
	null) {
		$this->host = $host;
		if (!str_contains($message, '{host}')) {
			$message = "{host}: $message";
		}
		$arguments['host'] = $host;
		parent::__construct($message, $arguments, $code, $previous);
	}
}
