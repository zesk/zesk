<?php declare(strict_types=1);

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
	public function __construct($filename = null, $context = null) {
		parent::__construct($filename, "{filename} not found {context}", [
			"context" => $context,
		]);
	}
}
