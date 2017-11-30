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
class Module_Mail_Test extends Test_Unit {
	protected $load_modules = array(
		"Mail",
		"MySQL"
	);
	function initialize() {
		parent::initialize();
		$database = $this->application->database_factory();
		$this->assert_instanceof($database, "MySQL\\Database");
		/* @var $database \MySQL\Database */
		// 		$database->set_option(\MySQL\Database::attribute_default_charset, \MySQL\Database::default_character_set);
		// 		$database->set_option(\MySQL\Database::attribute_collation, \MySQL\Database::default_collation);
		$module = $this->application->modules->object("Mail");
		$classes = $module->classes();
		$this->log("Synchronizing schema of {classes}", array(
			"classes" => $classes
		));
		foreach ($classes as $class) {
			// TODO MySQL specific
			$this->application->object_database($class)->query("DROP TABLE IF EXISTS " . $this->application->object_table_name($class));
		}
		
		$this->schema_synchronize($classes);
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

