<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Exception
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Exception;

use Throwable;
use zesk\Exception;

/**
 *
 */
class ConnectionFailed extends Exception
{
	public string $host;

	public function __construct(string $host, string $message = '', array $arguments = [], Throwable $previous =
	null)
	{
		parent::__construct($message, $arguments, 0, $previous);
		$this->host = $host;
	}

	public function __toString(): string
	{
		return $this->host . ': ' . parent::__toString();
	}
}
