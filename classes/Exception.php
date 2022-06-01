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
		return method_exists($e, 'variables') ? $e->variables() : [
			'exception_class' => get_class($e),
			'class' => get_class($e),
			'code' => $e->getCode(),
			'message' => $e->getMessage(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'trace' => $e->getTrace(),
			'backtrace' => $e->getTraceAsString(),
		];
	}

	/**
	 * @param \Exception $e
	 * @return array
	 * @deprecated 2022-05
	 */
	public static function exception_variables(\Exception $e): array {
		return self::exceptionVariables($e);
	}
}
