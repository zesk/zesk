<?php
/**
 * 
 */
use zesk\Application;
use zesk\File;
use zesk\Exception_Configuration;
use zesk\Exception_Syntax;

/**
 * 
 * @author kent
 *
 */
class Module_PHP_MO extends zesk\Module {
	
	/**
	 * 
	 * @throws Exception_Configuration
	 */
	public function initialize() {
		$inc = path($this->application->path(), $this->option("destination"), "php-mo.php");
		if (!is_readable($inc)) {
			throw new Exception_Configuration("php-mo", "Need to run: zesk update php-mo");
		}
		require_once $inc;
	}
	
	/**
	 * Convert .mo file to a .po file
	 * 
	 * @param string $input_file
	 * @param string $output_file
	 */
	public function mo_to_po($input_file, $output_file) {
		return phpmo_convert($input_file, $output_file);
	}
	
	/**
	 * 
	 * @param unknown $contents
	 * @throws Exception_Syntax
	 * @return string
	 */
	public function to_po($contents) {
		$mo = File::temporary(".mo");
		$po = File::temporary(".po");
		file_put_contents($mo, $contents);
		$result = phpmo_convert($mo, $po);
		unlink($mo);
		if ($result) {
			$contents = file_get_contents($po);
			unlink($po);
			return $contents;
		}
		throw new Exception_Syntax("Unable to convert file - ambigious");
	}
}

/*
 * External library reference
 */
if (false) {
	/**
	 *
	 * @param string $input_file
	 * @param string $output_file
	 */
	function phpmo_convert($input_file, $output_file) {
	}
}

