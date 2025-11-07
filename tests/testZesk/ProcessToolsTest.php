<?php
declare(strict_types=1);
/**
 *
 * @test_sandbox true
 * @package      zesk
 * @subpackage   test
 * @author       kent
 * @copyright    Copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Exception;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\FilePermission;
use zesk\Exception\NotFoundException;
use zesk\Exception\SemanticsException;
use zesk\Exception\SystemException;
use zesk\File;
use zesk\ProcessTools;

/**
 * @coversDefaultClass \zesk\ProcessTools
 * @uses ProcessTools
 */
class ProcessToolsTest extends TestCase
{
	protected bool $configureApplication = true;

	//There was 1 failure:
	//
	//1) testZesk\ProcessToolsTest::test_code_changed
	//Failed asserting that '/var/www/app/cache/test/3063/testlike.php' starts with "/var/www/.cache/test".
	//
	///var/www/app/tests/testZesk/ProcessToolsTest.php:67

	/**
	 * @return void
	 * @throws ClassNotFound
	 * @throws FilePermission
	 * @throws Exception
	 * @throws ConfigurationException
	 * @throws NotFoundException
	 * @throws SemanticsException
	 * @throws SystemException
	 * @covers ::includesChanged
	 * @covers ::includesChangedFiles
	 */
	public function test_code_changed(): void
	{
		$this->setUp();
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
