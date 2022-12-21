<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage system
 * @author kent
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk\Net\HTTP\Client;

use zesk\Exception as BaseException;
use Throwable;

/**
 * @todo subclass of zesk\Exception
 * @author kent
 *
 */
class Exception extends BaseException {
	/**
	 * Error code given by curl_errno
	 *
	 * @var integer
	 */
	public int $errno = 0;

	/**
	 * Mapped error code to error code string
	 *
	 * @var string
	 */
	public string $error_code = '';

	/**
	 * @param string $message
	 * @param array $arguments
	 * @param int $errno
	 * @param string $error_code
	 * @param Throwable|null $previous
	 */
	public function __construct(string $message, array $arguments = [], int $errno = 0, string $error_code = '', Throwable $previous = null) {
		parent::__construct($message, $arguments + [
			'errno' => $errno, 'error_code' => $error_code,
		], $errno, $previous);
		$this->errno = $errno;
		$this->error_code = $error_code;
	}
}
