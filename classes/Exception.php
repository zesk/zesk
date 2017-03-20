<?php

/**
 * Base class for all exceptions in Zesk.
 * Maybe add some tracking/reporting or some other functionality to it later.
 * @version $URL$
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2011, Market Acumen, Inc.
 */
namespace zesk;

class Exception extends \Exception {
	/**
	 * Raw message without variables substituted.
	 * Allows logging of messages before substitutions.
	 *
	 * @var string
	 */
	public $raw_message = '';
	
	/**
	 * Arguments for message.
	 * Uses map()
	 *
	 * @see map()
	 * @var array
	 */
	public $arguments = array();
	
	/**
	 * Construct a new exception
	 *
	 * @param string $message
	 *        	Class not found
	 * @param array $arguments
	 *        	Arguments to assist in examining this exception
	 * @param integer $code
	 *        	An integer error code value, if applicable
	 * @param Exception $previous
	 *        	Previous exception which may have spawned this one
	 */
	function __construct($message = null, $arguments = array(), $code = null, \Exception $previous = null) {
		/* Support previous invokation style ($message, $code, $previous) */
		if (is_array($arguments)) {
			$this->arguments = $arguments;
		} else {
			$previous = $code;
			$code = $arguments;
			$this->arguments = array();
		}
		$this->raw_message = $message;
		$map_message = strval(map($message, $this->arguments));
		if (PHP_VERSION_ID < 50400) {
			parent::__construct($map_message);
		} else {
			parent::__construct($map_message, intval($code), $previous);
		}
	}
	
	/**
	 * Retrieve variables for a Template
	 *
	 * @return array
	 */
	function variables() {
		return array(
			'exception_class' => get_class($this),
			'code' => $this->getCode(),
			'file' => $this->getFile(),
			'line' => $this->getLine(),
			'message' => $this->getMessage(),
			'raw_message' => $this->raw_message,
			'arguments' => $this->arguments,
			'previous' => $this->getPrevious(),
			'trace' => $this->getTrace(),
			'trace_dump' => $this->getTraceAsString()
		) + $this->arguments;
	}
	
	/**
	 * Used by zesk\Logger::log
	 *
	 * @see zesk\Logger::log
	 * @return array
	 */
	public function log_variables() {
		return $this->variables();
	}
	
	/**
	 * Used by zesk\Logger::log
	 *
	 * @see zesk\Logger::log
	 * @return array
	 */
	public function log_message() {
		return $this->getMessage();
	}
	
	/**
	 * @todo is this used?
	 * @param \Exception $e
	 * @return array
	 */
	public static function exception_variables(\Exception $e) {
		return array(
			"class" => get_class($e),
			"code" => $e->getCode(),
			"message" => $e->getMessage(),
			"file" => $e->getFile(),
			"line" => $e->getLine(),
			"backtrace" => $e->getTraceAsString()
		);
	}
}
