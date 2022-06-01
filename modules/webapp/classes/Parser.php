<?php declare(strict_types=1);
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk\WebApp;

class Parser {
	public function __construct($path) {
		if (!is_file($path)) {
			throw new \zesk\Exception_File_NotFound($path);
		}
	}
}
