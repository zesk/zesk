<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage Exception
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */

namespace zesk\Exception;

use Throwable;

/**
 *
 * @author kent
 *
 */
class FileNotFound extends FileSystemException {
	/**
	 * @param string $path
	 * @param string $message
	 * @param array $arguments
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct(string $path = '', $message = '', array $arguments = [], int $code = 0, Throwable
	$previous = null) {
		parent::__construct($path, $message === '' ? '{path} not found' : $message, [
			'path' => $path,
		] + $arguments, $code, $previous);
	}
}
