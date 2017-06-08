<?php

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
	function __construct($filename = null) {
		parent::__construct($filename, "{filename} not found");
	}
}
