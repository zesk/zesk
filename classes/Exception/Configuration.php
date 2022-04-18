<?php declare(strict_types=1);
namespace zesk;

class Exception_Configuration extends Exception {
	/**
	 *
	 * @var string
	 */
	public string $name = "";

	/**
	 *
	 * @param unknown $name
	 * @param unknown $message
	 * @param array $arguments
	 * @param Exception $previous
	 */
	public function __construct($name, $message, array $arguments = [], Exception $previous = null) {
		$this->name = $name;
		parent::__construct("Configuration error: {name}: $message", [
			"name" => $name,
		] + $arguments, 0, $previous);
	}
}
