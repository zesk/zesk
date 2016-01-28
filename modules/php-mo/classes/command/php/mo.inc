<?php

class Command_PHP_MO extends Command_File_Convert {
	protected $source_extension_pattern = "po";
	protected $destination_extension = "mo";
	protected $configuration_file = "php-mo";
	
	protected function convert_file($file, $new_file) {
		$this->log("Converting $file to $new_file");
		return Module_PHP_MO::mo_to_po($file, $new_file);
	}
	
	protected function convert_raw($content) {
		return $this->default_convert_raw($content);
	}
}
