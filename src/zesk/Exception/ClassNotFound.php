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
class ClassNotFound extends Exception {
	/**
	 * Class which wasn't found
	 *
	 * @var string
	 */
	public string $class = '';

	/**
	 * Construct a new exception
	 *
	 * @param string $message
	 *            Class not found
	 * @param array $arguments
	 *            Arguments to assist in examining this exception
	 * @param null|Throwable $previous
	 *            Previous exception which may have spawned this one
	 */
	public function __construct(?string $class, string $message = '', array $arguments = [], Throwable $previous =
	null) {
		$this->class = $class === null ? 'null' : $class;
		parent::__construct("{class} not found. $message", [
			'class' => $this->class,
		] + $arguments, 0, $previous);
	}

	/**
	 * Retrieve variables for a Template
	 *
	 * @return array
	 */
	public function variables(): array {
		return parent::variables() + [
			'class' => $this->class,
		];
	}
}
