<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Exception_Class_NotFound extends Exception {
	/**
	 * Class which wasn't found
	 *
	 * @var string
	 */
	public $class = null;

	/**
	 * Construct a new exception
	 *
	 * @param string $message
	 *            Class not found
	 * @param array $arguments
	 *            Arguments to assist in examining this exception
	 * @param ?\Exception $previous
	 *            Previous exception which may have spawned this one
	 */
	public function __construct($class, $message = null, $arguments = [], \Exception $previous = null) {
		parent::__construct("$class not found. $message", [
			'class' => $class,
		] + toArray($arguments), 0, $previous);
		$this->class = $class;
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
