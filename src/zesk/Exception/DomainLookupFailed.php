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
 * @author kent
 *
 */
class DomainLookupFailed extends Exception
{
	/**
	 *
	 * @var string
	 */
	public string $host;

	/**
	 *
	 * @param string $host
	 * @param string $message
	 * @param array $arguments
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct(string $host, string $message, array $arguments = [], int $code = 0, Throwable
	$previous =
	null)
	{
		$this->host = $host;
		if (!str_contains($message, '{host}')) {
			$message = "{host}: $message";
		}
		$arguments['host'] = $host;
		parent::__construct($message, $arguments, $code, $previous);
	}
}
