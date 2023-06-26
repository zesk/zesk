<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Database
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Database;

use Throwable;
use zesk\Exception as BaseException;

class Exception extends BaseException {
	/**
	 *
	 * @var Base
	 */
	public Base $database;

	/**
	 *
	 * @param Base $database
	 * @param string $message
	 * @param array $arguments
	 * @param mixed|null $code
	 * @param Exception|null $previous
	 * @return void
	 */
	public function __construct(Base $database, string $message, array $arguments = [], int $code = 0, Throwable $previous = null) {
		$this->database = $database;
		parent::__construct($message, $arguments, $code, $previous);
	}

	/**
	 *
	 * @return Base
	 */
	public function database(): Base {
		return $this->database;
	}
}
