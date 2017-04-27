<?php

/**
 * 
 * @author kent
 *
 */
class Command_PHP_MO extends zesk\Command_File_Convert {
	/**
	 * 
	 * @var string
	 */
	protected $source_extension_pattern = "po";
	
	/**
	 * 
	 * @var string
	 */
	protected $destination_extension = "mo";
	
	/**
	 * 
	 * @var string
	 */
	protected $configuration_file = "php-mo";
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Command_File_Convert::convert_file()
	 */
	protected function convert_file($file, $new_file) {
		$this->log("Converting $file to $new_file");
		return $this->application->modules->object("php-mo")->mo_to_po($file, $new_file);
	}
	
	/**
	 * 
	 * {@inheritDoc}
	 * @see Command_File_Convert::convert_raw()
	 */
	protected function convert_raw($content) {
		return $this->default_convert_raw($content);
	}
}
