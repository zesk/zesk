<?php
declare(strict_types=1);

namespace zesk;

class Exception_Configuration extends Exception {
	/**
	 *
	 * @var string
	 */
	public string|arrau $name = '';

	/**
	 *
	 * @param string $name
	 * @param string $message
	 * @param array $arguments
	 * @param Exception $previous
	 */
	public function __construct(array|string $name, string $message, array $arguments = [], Exception $previous =
	null) {
		$this->name = is_array($name) ? implode(Configuration::key_separator, $name) : $name;
		parent::__construct("Configuration error: {name}: $message", [
			'name' => $name,
		] + $arguments, 0, $previous);
	}
}
