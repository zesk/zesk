<?php
declare(strict_types=1);
/**
 *
 * @test_sandbox true
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright Copyright &copy; 2023, Market Acumen, Inc.
 */
namespace zesk;

class ProcessTools_Test extends UnitTest {
	protected array $load_modules = [
		'MySQL',
	];

	/**
	 * @todo this
	 */
	public function DISABLED_test_reset_dead_processes(): void {
		$db = $this->application->databaseRegistry();
		$db->query('DROP TABLE IF EXISTS test_PGT');
		$db->query('CREATE TABLE test_PGT ( ID int(11) unsigned PRIMARY KEY NOT NULL AUTO_INCREMENT, PID int(11) NOT NULL )');
		$table = 'test_PGT';
		$where = [];
		$pid_field = 'PID';
		//		ProcessTools::reset_dead_processes($this->application, $table, $where, $pid_field);
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

		$this->assertFalse(ProcessTools::includesChanged($this->application));
		sleep(1);
		file_put_contents($php_file, "<?php\ndefine('TEST_PGT',false);\n");
		clearstatcache();
		echo filemtime($php_file) . "\n";

		$this->assertTrue(ProcessTools::includesChanged($this->application));

		unlink($php_file);

		$db = $this->application->databaseRegistry();

		$db->query('DROP TABLE IF EXISTS test_PGT');
	}

	public function test_code_changed(): void {
		$result = ProcessTools::includesChanged($this->application);
		if ($result) {
			$changed = ProcessTools::includesChangedFiles($this->application);
			$appCache = $this->application->cachePath('test');
			foreach ($changed as $changedFile) {
				$this->assertStringStartsWith($appCache, $changedFile);
			}
		} else {
			$this->assertEquals([], ProcessTools::includesChangedFiles($this->application));
		}

		$files = get_included_files();
		$include = $this->test_sandbox('includesChanged.php');
		File::put($include, "<?php\n");

		$this->assertNotInArray($include, $files, 'test file should not be already included');

		require_once $include;

		$files = get_included_files();

		$this->assertInArray($include, $files, 'test file should be included now');

		$result = ProcessTools::includesChanged($this->application);

		$this->assertFalse($result, 'New include does not mean process code changed');

		sleep(1);

		File::put($include, "<?php\n//Hello");

		$result = ProcessTools::includesChanged($this->application);

		$this->assertTrue($result, 'Changed include means process code changed');
	}
}
