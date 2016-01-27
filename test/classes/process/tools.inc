<?php
/**
 * $URL: https://code.marketacumen.com/zesk/trunk/test/classes/process/tools.inc $
 *
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2008, Market Acumen, Inc.
 */

class Test_Process_Tools extends Test_Unit {
	function test_main() {
		$php_file = $this->test_sandbox("change-php-file.php");

		file_put_contents($php_file, "<?php\ndefine('TEST_PGT',true);\n");
		echo filemtime($php_file) . "\n";
		echo "Including $php_file\n";

		require_once $php_file;

		$db = Database::instance();
		$db->query("DROP TABLE IF EXISTS test_PGT");
		$db->query("CREATE TABLE test_PGT ( ID int(11) unsigned PRIMARY KEY NOT NULL AUTO_INCREMENT, PID int(11) NOT NULL )");
		$table = 'test_PGT';
		$where = array();
		$pid_field = 'PID';
//		Process_Tools::reset_dead_processes($table, $where, $pid_field);

		$this->assert(Process_Tools::process_code_changed() === false);
		sleep(1);
		file_put_contents($php_file, "<?php\ndefine('TEST_PGT',false);\n");
		clearstatcache();
		echo filemtime($php_file) . "\n";

		$this->assert(Process_Tools::process_code_changed() === true);

		unlink($php_file);

		$db->query("DROP TABLE IF EXISTS test_PGT");

		echo basename(__FILE__) . ": success\n";
	}
}
