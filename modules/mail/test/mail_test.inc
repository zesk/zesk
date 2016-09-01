<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/mail/test/mail_test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * @sandbox true
 */
class Mail_Test extends Test_Unit {
	function initialize() {
		$module = $this->load_module('mail');
		$classes = $module->classes();
		Database_Schema::objects_synchronize($classes);
		foreach ($classes as $class) {
			// TODO MySQL specific
			Object::cache_object($class)->database()->query("TRUNCATE " . Object::class_table_name($class));
		}
	}
	
	/**
	 */
	function test_files() {
		$test_path = dirname(__FILE__) . '/test-data';
		$ff = dir::ls($test_path, '/.*\.*txt$/', false);
		mail::debug($this->option_bool('debug'));
		foreach ($ff as $i => $f) {
			$this->log("Testing mail parsing $f");
			$f = path($test_path, $f);
			$m = Mail_Message::import_file($f);
			$this->assert($m instanceof Mail_Message, "Parsing $f");
			$this->log("Done parsing $f");
		}
	}
}

