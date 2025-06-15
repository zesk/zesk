<?php
declare(strict_types=1);
/**
 * @package    zesk
 * @subpackage test
 * @copyright  &copy; 2023 Market Acumen, Inc.
 * @author     kent
 */

namespace testZesk\Command;

use zesk\Command\Arguments;
use zesk\Command\Cache;
use zesk\Command\Cannon;
use zesk\Command\CONF2JSON;
use zesk\Command\CWD;
use zesk\Command\Globals;
use zesk\Command\Gremlins;
use zesk\Command\Help;
use zesk\Command\Host;
use zesk\Command\Included;
use zesk\Command\Info;
use zesk\Command\Licenses;
use zesk\Command\Maintenance;
use zesk\Command\Module;
use zesk\Command\ModuleVersion;
use zesk\Command\RunTime;
use zesk\Command\Shell;
use zesk\Command\Version;
use zesk\Command\Configuration;
use zesk\Directory;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\FilePermission;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\ProtocolException;
use zesk\Exception\SemanticsException;
use zesk\Exception\SystemException;
use zesk\Exception\UnsupportedException;
use zesk\File;
use zesk\JSON;
use zesk\System;
use testZesk\TestApplicationTestBase;

/**
 * Commands_Test
 */

/**
 * @coversDefaultClass \zesk\Command
 */
class CommandsTestBase extends TestApplicationTestBase
{
	public function initialize(): void
	{
		parent::initialize();
		$this->testApplication->configuration->set('testSettings', ['testing' => true]);
	}

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

	private static function commandTestArguments(): array
	{
		$versionInitLines = [
			'INFO: wrote /var/www/.cache/testApp/etc/version-schema.json',
			'INFO: wrote /var/www/.cache/testApp/etc/version.json',
			'',
		];
		return [
			Arguments::class     => [
				[
					[
						'a',
						'b',
						'c',
					],
					0,
					"[\"a\",\"b\",\"c\"]\n",
				],
				[
					[
						'a',
						'b',
						'dee',
					],
					0,
					"[\"a\",\"b\",\"dee\"]\n",
				],
			],
			Cache::class         => [
				[
					['print'],
					0,
					"zesk\\CacheItemPool\\FileCacheItemPool\n",
				],
				[
					[
						'--log',
						'-',
						'clear',
					],
					0,
					"NOTICE: /var/www/.cache/testAppCache/ is empty.\n",
				],
			],
			Version::class       => [
				[
					[
						function (CommandsTestBase $command) {
							$app = $command->testApplication;
							Directory::depend($app->path('etc'));
							foreach ([
								'etc/version.json',
								'etc/version-schema.json',
							] as $f) {
								$f = $app->path($f);
								File::unlink($f);
							}
							return '--init';
						},
					],
					0,
					implode("\n", $versionInitLines),
				],
				[
					[],
					0,
					'0.0.0.0',
				],
				[
					['--major'],
					0,
					"INFO: Updated version from 0.0.0.0 to 1.0.0.0\n",
				],
				[
					['--minor'],
					0,
					"INFO: Updated version from 1.0.0.0 to 1.1.0.0\n",
				],
				[
					['--maintenance'],
					0,
					"INFO: Updated version from 1.1.0.0 to 1.1.1.0\n",
				],
				[
					['--patch'],
					0,
					"INFO: Updated version from 1.1.1.0 to 1.1.1.1\n",
				],
				[
					[
						'--decrement',
						'--minor',
					],
					0,
					"INFO: Updated version from 1.1.1.1 to 1.0.0.0\n",
				],
				[
					[
						'--major',
						'--minor',
					],
					0,
					"INFO: Updated version from 1.0.0.0 to 2.1.0.0\n",
				],
			],
			Shell::class         => [
				[
					[
						'$app->optionInt("isSecondary")',
					],
					0,
					"# return 1\n",
				],
			],
			RunTime::class       => [
				[
					[''],
					0,
					"/0\.[01][0-9]{2} sec\n/",
				],
			],
			Module::class        => [
				/* State is for application load time */
				[
					[],
					0,
					self::allFalseModules,
				],
				[
					['--loaded'],
					0,
					"CSV:  true\nDiff: true\n",
				],
				[
					['CSV'],
					0,
					'',
				],
				[
					['NopeModule'],
					1,
					"ERROR: Failed loading module: NopeModule not found\n",
				],
				/* State is reset for application between calls */
				[
					['--loaded'],
					0,
					"CSV:  true\nDiff: true\n",
				],
			],
			Maintenance::class   => [
				/* Maintenance uses state on disk so call updates state */
				[
					[],
					1,
					'',
				],
				[
					['1'],
					0,
					"INFO: Maintenance enabled\n",
				],
				[
					[],
					0,
					'',
				],
				[
					['true'],
					0,
					"INFO: Maintenance enabled\n",
				],
				[
					[],
					0,
					'',
				],
				[
					['false'],
					0,
					"INFO: Maintenance disabled\n",
				],
				[
					[],
					1,
					'',
				],
				[
					['This is a maintenance message'],
					0,
					"INFO: Maintenance enabled with message \"This is a maintenance message\"\n",
				],
				[
					[],
					0,
					"This is a maintenance message\n",
				],
				[
					['true'],
					0,
					"INFO: Maintenance enabled\n",
				],
				[
					[],
					0,
					'',
				],
				[
					['false'],
					0,
					"INFO: Maintenance disabled\n",
				],
				[
					[],
					1,
					'',
				],
			],
			Licenses::class      => [
				[
					[],
					0,
					'',
				],
				[
					['--all'],
					0,
					File::contents(self::applicationPath('tests/test-data/license.txt')),
				],
				[
					[
						'--all',
						'--json',
					],
					0,
					JSON::encodePretty(json_decode(File::contents(self::applicationPath('tests/test-data/license.json')))),
				],
			],
			CWD::class           => [
				[
					[],
					0,
					"/var/www/app//\n",
				],
			],
			Configuration::class => [
				[
					['--loaded'],
					0,
					'#/var/www/app/tests/etc/test.json#',
				],
				[
					['--not-loaded'],
					0,
					'#/var/www/app/tests/etc/nope.json#',
				],
				[
					['--skipped'],
					0,
					'#/var/www/app/tests/etc/bad.json#',
				],
				[
					['--externals'],
					0,
					'#WEB_KEY.*\n.*ANOTHER_KEY#',
				],
				[
					['--missing-classes'],
					0,
					"Missing classes: [\n    \"testSettings\",\n    \"not\\\\AClass\"\n]\n",
				],
				[
					['--top-level-scalar'],
					0,
					'#\"API_KEY\"#',
				],
			],
			Globals::class       => [
				[
					[],
					0,
					'#debug-logger-config#',
				],
				[
					['testsettings'],
					0,
					'',
				],
				[
					[
						'--format',
						'json',
						'testsettings',
					],
					0,
					'[]',
				],
				[
					['testSettings'],
					0,
					File::contents(self::applicationPath('tests/test-data/testSettings.txt')),
				],
			],
			Gremlins::class      => [
				[
					[],
					0,
					"#/var/www/app/bin/zesk-command\.php#",
				],
			],
			Help::class          => [
				[
					[],
					0,
					'##',
				],
				[
					['--no-core'],
					0,
					'##',
				],
			],
			Host::class          => [
				[
					[],
					0,
					System::uname() . "\n",
				],
			],
			Included::class      => [
				[
					[],
					0,
					'#' . preg_quote(__FILE__, '#') . '#',
				],
			],
			Info::class          => [
				[
					[
						'--format',
						'json',
						'--computer-labels',
					],
					0,
					'#version\": \"1\.0\.0#',
				],
			],
			CONF2JSON::class     => [
				[
					[
						'--directory',
						self::applicationPath('tests/test-data/conf2json'),
					],
					0,
					'',
				],
				[
					[
						'--directory',
						self::applicationPath('tests/test-data/conf2json'),
					],
					0,
					'',
				],
				[
					[
						'--directory',
						self::applicationPath('tests/test-data/conf2json'),
						'--noclobber',
					],
					0,
					'#Will not overwrite#',
				],
			],
			ModuleVersion::class => [
				[
					[],
					0,
					"CSV : none\nDiff: none\n",
				],
				[
					['CSV'],
					0,
					"CSV: none\n",
				],
			],
			Cannon::class        => [
				[
					[
						'--dir',
						'./tests/test-data/cannon',
						'--dry-run',
						'--extensions',
						'test',
						'Foo',
						'Blaze',
					],
					0,
					File::contents(self::applicationPath('tests/test-data/cannon/foo-result.txt')),
				],
			],
		];
	}

	/**
	 *
	 */
	public static function dataIncludeClasses(): array
	{
		$result = [];
		foreach (self::commandTestArguments() as $class => $commandTests) {
			foreach ($commandTests as $commandTest) {
				[
					$testArguments,
					$expectedStatus,
					$expectedOutput,
				] = $commandTest;
				$result[] = [
					$class,
					$testArguments,
					$expectedStatus,
					$expectedOutput,
				];
			}
		}
		return $result;
	}

	/**
	 * Command test
	 *
	 * @param string $class
	 * @param array  $testArguments
	 * @param int    $expectedStatus
	 * @param string $expectedOutput
	 * @return void
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws NotFoundException
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws ProtocolException
	 * @throws UnsupportedException
	 * @throws SemanticsException
	 * @throws SystemException
	 * @dataProvider dataIncludeClasses
	 * @covers       \zesk\Command\Cannon \zesk\Command\ModuleVersion \zesk\Command\CONF2JSON \zesk\Command\Arguments
	 * @covers       \zesk\Command\Cache \zesk\Command\Version \zesk\Command\Shell \zesk\Command\RunTime \zesk\Command\Module
	 * @covers       \zesk\Command\Maintenance \zesk\Command\Licenses \zesk\Command\CWD \zesk\Command\Configuration \zesk\Command\Globals
	 * @covers       \zesk\Command\Gremlins
	 * @covers       \zesk\Command\Help \zesk\Command\Host \zesk\Command\Included \zesk\Command\Info
	 */
	public function test_command(string $class, array $testArguments, int $expectedStatus, string $expectedOutput): void
	{
		$this->testApplication->configure();
		$testArguments = $this->applyClosures($testArguments);
		$this->assertCommandClass($class, $testArguments, $expectedStatus, $expectedOutput);
	}

	/**
	 * @return void
	 * @depends      test_command
	 * @throws FilePermission
	 * @covers       \zesk\Command\CONF2JSON
	 */
	public function test_conf2jsonresult(): void
	{
		$resultFile = $this->application->path('tests/test-data/conf2json/conf2json.json');
		$expectedFile = $this->application->path('tests/test-data/conf2json/conf2json-expected.json');
		$this->assertFileEquals($expectedFile, $resultFile);
		File::unlink($resultFile);
	}
}
