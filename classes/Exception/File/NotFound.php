<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

/**
 *
 * @author kent
 *
 */
class Exception_File_NotFound extends Exception_FileSystem {
	public function __construct($filename = null, $message = "", array $arguments = [], $code = 0) {
		parent::__construct($filename, $message === "" ? "{filename} not found" : $message, [
				"filename" => $filename,
			] + $arguments, $code);
	}
}
