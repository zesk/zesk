<?php
declare(strict_types=1);

/**
 * Base class for all exceptions in Zesk.
 * Maybe add some tracking/reporting or some other functionality to it later.
 * @version $URL$
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */

namespace zesk;

class Exception extends \Exception {
	use Exceptional;

	/**
	 * @param \Exception $e
	 * @return array
	 */
	public static function exceptionVariables(\Exception $e): array {
		return method_exists($e, 'variables') ? $e->variables() : self::phpExceptionVariables($e);
	}

	/**
	 * @param \Exception $e
	 * @return array
	 */
	public static function phpExceptionVariables(\Exception $e): array {
		return [
			'exceptionClass' => $e::class,
			'class' => $e::class,
			'code' => $e->getCode(),
			'message' => $e->getMessage(),
			'rawMessage' => $e->getMessage(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'trace' => $e->getTrace(),
			'backtrace' => $e->getTraceAsString(),
		];
	}
}
