<?php
declare(strict_types=1);

/**
 * Base class for all exceptions in Zesk.
 * Maybe add some tracking/reporting or some other functionality to it later.
 * @version $URL$
 * @package zesk
 * @subpackage system
 * @author $Author: kent $
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk;

use Error;
use Throwable;

class Exception extends \Exception {
	use Exceptional;

	/**
	 * @param \Exception $e
	 * @return array
	 */
	public static function exceptionVariables(Throwable $e): array {
		return method_exists($e, 'variables') ? $e->variables() :
			($e instanceof Error ? self::phpExceptionVariables($e, 'error') : self::phpExceptionVariables($e));
	}

	/**
	 * @param \Exception $e
	 * @return array
	 */
	public static function phpExceptionVariables(Throwable $e, string $prefix = 'exception'): array {
		return [
			'throwableClass' => $e::class,
			"{$prefix}Class" => $e::class,
			'throwableCode' => $e->getCode(),
			"{$prefix}Code" => $e->getCode(),
			'message' => $e->getMessage(),
			'rawMessage' => $e->getMessage(),
			'file' => $e->getFile(),
			'line' => $e->getLine(),
			'trace' => $e->getTrace(),
			'backtrace' => $e->getTraceAsString(),
			/* 2022-12 Deprecated - overlaps */
			'class' => $e::class,
			'code' => $e->getCode(),
		];
	}
}
