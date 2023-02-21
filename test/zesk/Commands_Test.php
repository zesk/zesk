<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @copyright &copy; 2023 Market Acumen, Inc.
 * @author kent
 */

namespace zesk;

/**
 * Commands_Test
 */
class Commands_Test extends TestApplicationUnitTest {
	public const allFalseModules = 'CSV: false
Cron: false
Daemon: false
Database: false
Diff: false
Git: false
Job: false
Locale: false
Login: false
Mail: false
MySQL: false
Net: false
ORM: false
PHPUnit: false
Preference: false
Repository: false
SQLite3: false
Server: false
Session: false
World: false';

	private static function commandTestArguments(): array {
		$versionInitLines = [
			'INFO: wrote /zesk/cache/testApp/etc/version-schema.json',
			'INFO: wrote /zesk/cache/testApp/etc/version.json',
			'',
		];
		return [
			Command_Arguments::class => [
				[['a', 'b', 'c'], 0, "[\"a\",\"b\",\"c\"]\n"], [['a', 'b', 'dee'], 0, "[\"a\",\"b\",\"dee\"]\n"],
			], Command_Cache::class => [
				[['print'], 0, "zesk\\CacheItemPool_File\n"],
				[['clear'], 0, "NOTICE: /zesk/cache/testApp/cache/ is empty.\nNOTICE: No module cache clear hooks\n"],
			], Command_Version::class => [
				[[function () {
					Directory::depend(self::testApplication()->path('etc'));
					foreach (['etc/version.json', 'etc/version-schema.json'] as $f) {
						$f = self::testApplication()->path($f);
						File::unlink($f);
					}
					return '--init';
				}], 0, implode("\n", $versionInitLines), ],
				[[], 0, '0.0.0.0'],
				[['--major'], 0, "INFO: Updated version from 0.0.0.0 to 1.0.0.0\n"],
				[['--minor'], 0, "INFO: Updated version from 1.0.0.0 to 1.1.0.0\n"],
				[['--maintenance'], 0, "INFO: Updated version from 1.1.0.0 to 1.1.1.0\n"],
				[['--patch'], 0, "INFO: Updated version from 1.1.1.0 to 1.1.1.1\n"],
				[['--decrement', '--minor'], 0, "INFO: Updated version from 1.1.1.1 to 1.0.0.0\n"],
				[['--major', '--minor'], 0, "INFO: Updated version from 1.0.0.0 to 2.1.0.0\n"],
			], Command_Shell::class => [
				[['intval($app->option("isSecondary"))'], 0, "# return 1\n"],
			], Command_RunTime::class => [
				[[''], 0, "/0\.[01][0-9]{2} sec\n/"],
			], Command_Module::class => [
				/* State is for application load time */
				[[], 0, self::allFalseModules],
				[['--loaded'], 0, "CSV : true\nDiff: true\n"],
				[['CSV'], 0, ''],
				[['NopeModule'], 1, "ERROR: Failed loading module: NopeModule: NopeModule was not found in /zesk/modules\n",
				], /* State is reset for application between calls */
				[['--loaded'], 0, "CSV : true\nDiff: true\n"],
			], Command_Maintenance::class => [
				/* Maintenance uses state on disk so call updates state */ [[], 1, ''],
				[['1'], 0, "INFO: Maintenance enabled\n"], [[], 0, ''], [['true'], 0, "INFO: Maintenance enabled\n"],
				[[],
					0, '', ],
				[['false'], 0, "INFO: Maintenance disabled\n"], [[], 1, ''], [
					['This is a maintenance message'], 0,
					"INFO: Maintenance enabled with message \"This is a maintenance message\"\n",
				], [[], 0, "This is a maintenance message\n"], [['true'], 0, "INFO: Maintenance enabled\n"], [[], 0, ''],
				[['false'], 0, "INFO: Maintenance disabled\n"], [[], 1, ''],
			], Command_Licenses::class => [
				[[], 0, ''], [['--all'], 0, File::contents(self::applicationPath('test/test-data/license.txt'))],
				[['--all', '--json'], 0, File::contents(self::applicationPath('test/test-data/license.json'))],
			], Command_CWD::class => [
				[[], 0, "/zesk/\n"],
			], Command_Configuration::class => [
				[['--loaded'], 0, '#/zesk/test/etc/test.json#'], [['--not-loaded'], 0, '#/zesk/test/etc/nope.json#'],
				[['--skipped'], 0, '#/zesk/test/etc/bad.json#'], [['--externals'], 0, '#WEB_KEY.*\n.*ANOTHER_KEY#'],
				[['--missing-classes'], 0, "Missing classes: [\n    \"testSettings\",\n    \"not\\\\AClass\"\n]\n"],
				[['--top-level-scalar'], 0, '#HOME.*\n.*API_KEY#'],
			], Command_Globals::class => [
				[[], 0, '#debug-logger-config#'], [['testsettings'], 0, ''],
				[['--format', 'json', 'testsettings'], 0, '[]'],
				[['testSettings'], 0, File::contents(self::applicationPath('test/test-data/testSettings.txt'))],
			], Command_Gremlins::class => [
				[[], 0, "#/zesk/bin/zesk-command\.php#"],
			], Command_Help::class => [
				[[], 0, '##'], [['--no-core'], 0, '##'],
			], Command_Host::class => [
				[[], 0, System::uname() . "\n"],
			], Command_Included::class => [
				[[], 0, '#' . preg_quote(__FILE__, '#') . '#'],
			], Command_Info::class => [
				[['--format', 'json', '--computer-labels'], 0, '#version\": \"1\.0\.0#'],
			], Command_CONF2JSON::class => [
				[['--directory', self::applicationPath('test/test-data/conf2json')], 0, ''],
				[['--directory', self::applicationPath('test/test-data/conf2json')], 0, ''], [
					['--directory', self::applicationPath('test/test-data/conf2json'), '--noclobber'], 0,
					'#Will not overwrite#',
				],
			], Command_Module_Version::class => [
				[[], 0, "CSV : none\nDiff: none\n"], [['CSV'], 0, "CSV: none\n"],
			], Command_Cannon::class => [
				[
					['--dir', './test/test-data/cannon', '--dry-run', '--extensions', 'test', 'Foo', 'Blaze'], 0,
					File::contents(self::applicationPath('test/test-data/cannon/foo-result.txt')),
				],
			],
		];
	}

	/**
	 *
	 */
	public static function dataIncludeClasses(): array {
		$result = [];
		foreach (self::commandTestArguments() as $class => $commandTests) {
			foreach ($commandTests as $commandTest) {
				[$testArguments, $expectedStatus, $expectedOutput] = $commandTest;
				$result[] = [$class, $testArguments, $expectedStatus, $expectedOutput];
			}
		}
		return $result;
	}

	/**
	 * Command test
	 *
	 * @param string $class
	 * @return void
	 * @dataProvider dataIncludeClasses
	 */
	public function test_command(string $class, array $testArguments, int $expectedStatus, string $expectedOutput): void {
		$this->testApplication->configure();
		$testArguments = $this->applyClosures($testArguments);
		$this->assertCommandClass($class, $testArguments, $expectedStatus, $expectedOutput);
	}

	/**
	 * @return void
	 * @depends test_command
	 */
	public function test_conf2jsonresult(): void {
		$resultFile = $this->application->path('test/test-data/conf2json/conf2json.json');
		$expectedFile = $this->application->path('test/test-data/conf2json/conf2json-expected.json');
		$this->assertFileEquals($expectedFile, $resultFile);
		File::unlink($resultFile);
	}
}
