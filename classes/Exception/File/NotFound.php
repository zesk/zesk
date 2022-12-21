<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

use Throwable;

/**
 *
 * @author kent
 *
 */
class Exception_File_NotFound extends Exception_FileSystem {
	/**
	 * @param $filename
	 * @param $message
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
