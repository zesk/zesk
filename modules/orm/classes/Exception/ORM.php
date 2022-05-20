<?php declare(strict_types=1);

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
	protected $class = null;

	/**
	 * Create a new error
	 * @param string $class Class of this error
	 * @param string $message Optional message related to context of error
	 * @param array $arguments Additional arguments
	 * @param integer $code error code
	 * @param Exception $previous Previous error
	 */
	public function __construct($class, $message = null, $arguments = [], Exception $previous = null) {
		$this->class = $class;
		if (empty($message)) {
			$message = 'Class: {class}';
		}
		$arguments += [
			'class' => $class,
		];
		parent::__construct($message, $arguments, null, $previous);
	}

	public function variables(): array {
		return parent::variables() + [
			'class' => $this->class,
		];
	}
}
