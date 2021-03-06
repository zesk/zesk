<?php
/**
 * @package zesk
 * @subpackage webapp
 * @author kent
 * @copyright &copy; 2019 Market Acumen, Inc.
 */
namespace zesk\WebApp;

class Parser {
	public function __construct($path) {
		if (!is_file($path)) {
			throw new \zesk\Exception_File_NotFound($path);
		}
	}
}
