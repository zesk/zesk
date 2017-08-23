<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/modules/mail/test/mail_test.inc $
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 * @sandbox true
 */
namespace zesk;

/**
 * 
 * @author kent
 *
 */
class Mail_Test extends Test_Unit {
	protected $load_modules = array(
		"Mail",
		"MySQL",
	);
	function initialize() {
		parent::initialize();
		$module = $this->application->modules->object("Mail");
		$classes = $module->classes();
		$this->application->schema_synchronize(null, $classes);
		foreach ($classes as $class) {
			// TODO MySQL specific
			$this->application->object_database($class)->query("TRUNCATE " . $this->application->object_table_name($class));
		}
	}
	
	/**
	 */
	function test_files() {
		$test_path = dirname(__FILE__) . '/test-data';
		$ff = Directory::ls($test_path, '/.*\.*txt$/', false);
		Mail::debug($this->option_bool('debug'));
		foreach ($ff as $i => $f) {
			$this->log("Testing mail parsing $f");
			$f = path($test_path, $f);
			$m = Mail_Message::import_file($this->application, $f);
			$this->assert($m instanceof Mail_Message, "Parsing $f");
			$this->log("Done parsing $f");
		}
	}
}

