<?php
/**
 *
 */
namespace zesk\WebApp;

class Parser {
	public function __construct($path) {
		if (!is_file($path)) {
			throw new \zesk\Exception_File_NotFound($path);
		}
	}
}
