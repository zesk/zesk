<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

/**
 *
 */
abstract class Exception_ORM extends Exception {
	/**
	 * Class of object where error occurred
	 * @var string
	 */
	protected string $class;

	/**
	 * Create a new error
	 * @param string|object $class
	 * @param string|null $message
	 * @param $arguments
	 * @param Exception|null $previous
	 */
	public function __construct(string|object $class, string $message = null, $arguments = [], Exception $previous =
	null) {
		$this->class = is_object($class) ? $class::class : $class;
		if (empty($message)) {
			$message = 'Class: {class}';
		}
		$arguments += [
			'class' => $class,
		];
		parent::__construct($message, $arguments, 0, $previous);
	}

	public function variables(): array {
		return parent::variables() + [
			'class' => $this->class,
		];
	}
}
