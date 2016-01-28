<?php

class Module_PHP_MO extends Module {
	private static function _load() {
		$inc = path(Module::path('php-mo'), "lib/php-mo.php");
		if (!is_readable($inc)) {
			throw new Exception_Configuration("php-mo", "Need to run: zesk update php-mo");
		}
		require_once $inc;
	}
	public static function mo_to_po($input_file, $output_file) {
		self::_load();
		return phpmo_convert($input_file, $output_file);
	}
	public static function to_po($contents) {
		self::_load();
		$mo = file::temporary(".mo");
		$po = file::temporary(".po");
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
