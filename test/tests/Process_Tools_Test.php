<?php declare(strict_types=1);
/**
 *
 * @test_sandbox true
 * @package zesk
 * @subpackage test
 * @author Kent Davidson <kent@marketacumen.com>
 * @copyright Copyright &copy; 2022, Market Acumen, Inc.
 */
namespace zesk;

class Process_Tools_Test extends UnitTest {
	protected array $load_modules = [
		'MySQL',
	];

	/**
	 * @todo this
	 */
	public function DISABLED_test_reset_dead_processes(): void {
		$db = $this->application->database_registry();
		$db->query('DROP TABLE IF EXISTS test_PGT');
		$db->query('CREATE TABLE test_PGT ( ID int(11) unsigned PRIMARY KEY NOT NULL AUTO_INCREMENT, PID int(11) NOT NULL )');
		$table = 'test_PGT';
		$where = [];
		$pid_field = 'PID';
		//		Process_Tools::reset_dead_processes($this->application, $table, $where, $pid_field);
	}

	/**
	 * @todo this
	 */
	public function alt_test_code_changed(): void {
		$php_file = $this->test_sandbox('change-php-file.php');

		file_put_contents($php_file, "<?php\ndefine('TEST_PGT',true);\n");
		echo filemtime($php_file) . "\n";
		echo "Including $php_file\n";

		require_once $php_file;

		$this->assert(Process_Tools::process_code_changed($this->application) === false);
		sleep(1);
		file_put_contents($php_file, "<?php\ndefine('TEST_PGT',false);\n");
		clearstatcache();
		echo filemtime($php_file) . "\n";

		$this->assert(Process_Tools::process_code_changed($this->application) === true);

		unlink($php_file);

		$db = $this->application->database_registry();

		$db->query('DROP TABLE IF EXISTS test_PGT');

		echo basename(__FILE__) . ": success\n";
	}

	public function test_code_changed(): void {
		$result = Process_Tools::process_code_changed($this->application);
		$this->assert_false($result, 'Process code did not change: ' . implode(',', Process_tools::process_code_changed_files($this->application)));

		$files = get_included_files();
		$include = $this->test_sandbox('process_code_changed.php');
		File::put($include, "<?php\n");

		$this->assert_not_in_array($files, $include, 'test file should not be already included');

		require_once $include;

		$files = get_included_files();

		$this->assert_in_array($files, $include, 'test file is already included');

		$result = Process_Tools::process_code_changed($this->application);

		$this->assert_false($result, 'New include does not mean process code changed');

		sleep(1);

		File::put($include, "<?php\n//Hello");

		$result = Process_Tools::process_code_changed($this->application);

		$this->assert_true($result, 'Changed include means process code changed');
	}
}
