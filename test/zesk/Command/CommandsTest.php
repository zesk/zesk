<?php
declare(strict_types=1);
/**
 * @package zesk
 * @subpackage test
 * @copyright &copy; 2023 Market Acumen, Inc.
 * @author kent
 */

namespace zesk\Command;

use zesk\Directory;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\Semantics;
use zesk\Exception\Unsupported;
use zesk\File;
use zesk\System;
use zesk\TestApplicationUnitTest;

/**
 * Commands_Test
 */
class CommandsTest extends TestApplicationUnitTest {
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
			Arguments::class => [
				[['a', 'b', 'c'], 0, "[\"a\",\"b\",\"c\"]\n"], [['a', 'b', 'dee'], 0, "[\"a\",\"b\",\"dee\"]\n"],
			], Cache::class => [
				[['print'], 0, "zesk\\CacheItemPool\\FileCacheItemPool\n"],
				[['--log', '-', 'clear'], 0, "NOTICE: /zesk/cache/testApp/cache/ is empty.\nDEBUG: No module cache clear hooks\n"],
			], Version::class => [
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
			], Shell::class => [
				[['$app->optionInt("isSecondary")'], 0, "# return 1\n"],
			], RunTime::class => [
				[[''], 0, "/0\.[01][0-9]{2} sec\n/"],
			], Module::class => [
				/* State is for application load time */
				[[], 0, self::allFalseModules],
				[['--loaded'], 0, "CSV : true\nDiff: true\n"],
				[['CSV'], 0, ''],
				[['NopeModule'], 1, "ERROR: Failed loading module: NopeModule: NopeModule was not found in /zesk/modules\n",
				], /* State is reset for application between calls */
				[['--loaded'], 0, "CSV : true\nDiff: true\n"],
			], Maintenance::class => [
				/* Maintenance uses state on disk so call updates state */ [[], 1, ''],
				[['1'], 0, "INFO: Maintenance enabled\n"], [[], 0, ''], [['true'], 0, "INFO: Maintenance enabled\n"],
				[[],
					0, '', ],
				[['false'], 0, "INFO: Maintenance disabled\n"], [[], 1, ''], [
					['This is a maintenance message'], 0,
					"INFO: Maintenance enabled with message \"This is a maintenance message\"\n",
				], [[], 0, "This is a maintenance message\n"], [['true'], 0, "INFO: Maintenance enabled\n"], [[], 0, ''],
				[['false'], 0, "INFO: Maintenance disabled\n"], [[], 1, ''],
			], Licenses::class => [
				[[], 0, ''], [['--all'], 0, File::contents(self::applicationPath('test/test-data/license.txt'))],
				[['--all', '--json'], 0, File::contents(self::applicationPath('test/test-data/license.json'))],
			], CWD::class => [
				[[], 0, "/zesk/\n"],
			], Configuration::class => [
				[['--loaded'], 0, '#/zesk/test/etc/test.json#'], [['--not-loaded'], 0, '#/zesk/test/etc/nope.json#'],
				[['--skipped'], 0, '#/zesk/test/etc/bad.json#'], [['--externals'], 0, '#WEB_KEY.*\n.*ANOTHER_KEY#'],
				[['--missing-classes'], 0, "Missing classes: [\n    \"testSettings\",\n    \"not\\\\AClass\"\n]\n"],
				[['--top-level-scalar'], 0, '#HOME.*\n.*API_KEY#'],
			], Globals::class => [
				[[], 0, '#debug-logger-config#'], [['testsettings'], 0, ''],
				[['--format', 'json', 'testsettings'], 0, '[]'],
				[['testSettings'], 0, File::contents(self::applicationPath('test/test-data/testSettings.txt'))],
			], Gremlins::class => [
				[[], 0, "#/zesk/bin/zesk-command\.php#"],
			], Help::class => [
				[[], 0, '##'], [['--no-core'], 0, '##'],
			], Host::class => [
				[[], 0, System::uname() . "\n"],
			], Included::class => [
				[[], 0, '#' . preg_quote(__FILE__, '#') . '#'],
			], Info::class => [
				[['--format', 'json', '--computer-labels'], 0, '#version\": \"1\.0\.0#'],
			], CONF2JSON::class => [
				[['--directory', self::applicationPath('test/test-data/conf2json')], 0, ''],
				[['--directory', self::applicationPath('test/test-data/conf2json')], 0, ''], [
					['--directory', self::applicationPath('test/test-data/conf2json'), '--noclobber'], 0,
					'#Will not overwrite#',
				],
			], ModuleVersion::class => [
				[[], 0, "CSV : none\nDiff: none\n"], [['CSV'], 0, "CSV: none\n"],
			], Cannon::class => [
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
	 * @param array $testArguments
	 * @param int $expectedStatus
	 * @param string $expectedOutput
	 * @return void
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws NotFoundException
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws Semantics
	 * @throws Unsupported
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
