<?php declare(strict_types=1);

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
	 *        	Class not found
	 * @param array $arguments
	 *        	Arguments to assist in examining this exception
	 * @param int $code
	 *        	An integer error code value, if applicable
	 * @param \Exception|null $previous
	 *        	Previous exception which may have spawned this one
	 */
	public function __construct(string $message = '', array $arguments = [], int $code = 0, \Exception $previous = null) {
		$this->arguments = $arguments;
		$this->raw_message = $message;
		$map_message = strval(map($message, $this->arguments));
		parent::__construct($map_message, intval($code), $previous);
	}

	/**
	 * Retrieve variables for a Template
	 *
	 * @return array
	 */
	public function variables(): array {
		return [
			'exception_class' => get_class($this),
			'class' => get_class($this),
			'code' => $this->getCode(),
			'message' => $this->getMessage(),
			'file' => $this->getFile(),
			'line' => $this->getLine(),
			'trace' => $this->getTrace(),
			'backtrace' => $this->getTraceAsString(),
			'raw_message' => $this->raw_message,
			'arguments' => $this->arguments,
			'previous' => $this->getPrevious(),
		];
	}

	/**
	 * Used by zesk\Logger::log
	 *
	 * @see Logger::log
	 * @return array
	 */
	public function log_variables(): array {
		return $this->variables();
	}

	/**
	 * Used by zesk\Logger::log
	 *
	 * @see Logger::log
	 * @return string
	 */
	public function log_message(): string {
		return $this->getMessage();
	}
}
