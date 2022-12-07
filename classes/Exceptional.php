<?php
declare(strict_types=1);

namespace zesk;

trait Exceptional {
	/**
	 * Raw message without variables substituted.
	 * Allows logging of messages before substitutions.
	 *
	 * @var string
	 */
	public string $raw_message = '';

	/**
	 * Arguments for message.
	 * Uses map()
	 *
	 * @see map()
	 * @var array
	 */
	public array $arguments = [];

	/**
	 * Construct a new exception
	 *
	 * @param string $message
	 *            Class not found
	 * @param array $arguments
	 *            Arguments to assist in examining this exception
	 * @param int $code
	 *            An integer error code value, if applicable
	 * @param \Exception|null $previous
	 *            Previous exception which may have spawned this one
	 */
	public function __construct(string $message = '', array $arguments = [], int $code = 0, \Throwable $previous = null) {
		$this->arguments = $arguments;
		$this->raw_message = $message;
		$map_message = strval(map($this->raw_message, $this->arguments));
		parent::__construct($map_message, $code, $previous);
	}

	/**
	 * @return string
	 */
	public function getRawMessage(): string {
		return $this->raw_message;
	}

	/**
	 * Retrieve variables for a Template
	 *
	 * @return array
	 */
	public function variables(): array {
		return [
			'rawMessage' => $this->raw_message, 'arguments' => $this->arguments,
		] + Exception::phpExceptionVariables($this);
	}

	/**
	 * Used by zesk\Logger::log
	 *
	 * @return array
	 * @see Logger::log
	 */
	public function logVariables(): array {
		return $this->variables();
	}

	/**
	 * Used by zesk\Logger::log
	 *
	 * @return string
	 * @see Logger::log
	 */
	public function logMessage(): string {
		return $this->getMessage();
	}
}
