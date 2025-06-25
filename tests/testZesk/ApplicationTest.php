<?php
declare(strict_types=1);
/**
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use zesk\Application;
use zesk\ArrayTools;
use zesk\CommandLoader;
use zesk\Directory;
use zesk\Exception\ClassNotFound;
use zesk\Exception\ConfigurationException;
use zesk\Exception\Deprecated;
use zesk\Exception\DirectoryCreate;
use zesk\Exception\DirectoryNotFound;
use zesk\Exception\DirectoryPermission;
use zesk\Exception\FileNotFound;
use zesk\Exception\FileParseException;
use zesk\Exception\FilePermission;
use zesk\Exception\NotFoundException;
use zesk\Exception\ParameterException;
use zesk\Exception\ParseException;
use zesk\Exception\SemanticsException;
use zesk\Exception\SystemException;
use zesk\Exception\UnsupportedException;
use zesk\File;
use zesk\JSON;
use zesk\Logger;
use zesk\PHP;
use zesk\Request;
use zesk\Response;

/**
 *
 * @author kent
 * @see    TestApplication
 * @see    TestRequest
 * @covers \zesk\Application
 * @coversDefaultClass \zesk\Application
 */
class ApplicationTest extends ApplicationTestBase
{
	/**
	 * @return void
	 * @throws ParameterException
	 * @throws ParseException
	 * @covers ::requestFactory
	 */
	public function test_requestFactory(): void
	{
		$request = $this->application->requestFactory();
		$this->assertNotNull($request);
	}

	public static function data_document_root_prefix(): array
	{
		return [
			[
				'/foobar',
				'/foobar',
			],
			[
				'foobar',
				'foobar',
			],
			[
				'',
				'',
			],
			[
				'antidis',
				'antidis/',
			],
			[
				'antidis',
				'antidis////',
			],
		];
	}

	/**
	 * @param $expected
	 * @param $set
	 * @return void
	 * @dataProvider data_document_root_prefix
	 * @covers ::setDocumentRootPrefix ::documentRootPrefix
	 */
	public function test_document_root_prefix($expected, $set): void
	{
		$this->application->setDocumentRootPrefix($set);
		$this->assertEquals($expected, $this->application->documentRootPrefix());
	}

	/**
	 * @return void
	 * @covers ::documentRoot ::setDocumentRoot
	 */
	public function test_site_root(): void
	{
		$box = $this->test_sandbox();
		$this->application->setDocumentRoot($box);
		$this->assertEquals($box, $this->application->documentRoot());
	}

	/**
	 * @return void
	 * @throws FileParseException
	 * @throws SemanticsException
	 * @throws SystemException
	 * @covers ::configureFiles
	 */
	public function test_load_globals(): void
	{
		$paths = [
			$this->test_sandbox(),
		];
		$file = $this->test_sandbox('test.conf');
		$this->application->configuration->DUDE = 'smooth';
		file_put_contents($file, "TEST_VAR=\"\$DUDE move\"\nVAR_2=\"Ha ha! \${TEST_VAR} ex-lax\"\nVAR_3=\"\${DUDE:-default value}\"\nVAR_4=\"\${DOOD:-default value}\"");
		$overwrite = false;
		$this->application->configureFiles([$file]);

		$globals = [
			'TEST_VAR' => 'smooth move',
			'VAR_2' => 'Ha ha! smooth move ex-lax',
			'VAR_3' => 'smooth',
			'VAR_4' => 'default value',
		];

		foreach ($globals as $v => $result) {
			$g = $this->application->configuration->$v;
			$this->assertEquals($g, $result);
		}
	}

	/**
	 * @return void
	 * @covers ::maintenance
	 */
	public function test_maintenance(): void
	{
		$this->application->maintenance();
	}

	/**
	 * @return void
	 * @throws DirectoryNotFound
	 * @throws FilePermission
	 * @covers ::zeskCommandPath ::addZeskCommandPath
	 * @covers \zesk\CommandLoader::collectCommandShortcuts
	 * @covers \zesk\CommandLoader::factory
	 */
	public function test_zeskCommandPath(): void
	{
		$file = $this->test_sandbox('testlike.php');
		$contents = file_get_contents($this->testApplication->zeskHome('tests/test-data/testlike.php'));
		file_put_contents($file, ArrayTools::map($contents, [
			'Testlike' => 'Testlike' . strtoupper(substr(md5(microtime()), 0, 8)),
		], false, '', ''));
		$loader = CommandLoader::factory()->setApplication($this->testApplication);
		$this->testApplication->addZeskCommandPath($this->test_sandbox());
		$pid = $this->testApplication->process->id();
		$className = 'TestCommand' . $pid;
		$randomShortcut = $this->randomHex();
		$shortcuts = [
			'test-command',
			$randomShortcut,
		];
		$testCommand = [];
		$testCommand[] = '<?php';
		$testCommand[] = 'namespace ' . __NAMESPACE__ . '\Command;';
		$testCommand[] = 'use zesk\Command\SimpleCommand;';
		$testCommand[] = "class $className extends SimpleCommand {";
		$testCommand[] = '	protected array $shortcuts = ' . PHP::dump($shortcuts) . ';';
		$testCommand[] = '	function run(): int {';
		$testCommand[] = '		echo getcwd();';
		$testCommand[] = '		return 0;';
		$testCommand[] = '	}';
		$testCommand[] = '}';

		// Add testlike.php
		// $shortcuts[] = 'test-like';

		File::put($this->test_sandbox('testCommand.php'), implode("\n", $testCommand));
		$allShortcuts = $loader->collectCommandShortcuts();

		$this->assertArrayHasKeys([
			'test-like',
			$randomShortcut,
			'test-command',
		], $allShortcuts);
		$this->assertEquals(__NAMESPACE__ . '\\Command\\' . $className, $allShortcuts['test-command']);
	}

	/**
	 * @return void
	 * @covers ::copyrightHolder
	 */
	public function testCopyright(): void
	{
		$app = $this->application;
		$this->assertStringContainsString('Market Acumen', $app->copyrightHolder());
	}

	/**
	 * @return void
	 * @covers ::setConsole ::console
	 */
	public function testConsole(): void
	{
		$app = $this->application;

		$this->assertTrue($app->console());
		$app->setConsole(false);
		$this->assertFalse($app->console());
		$app->setConsole(true);
	}

	/**
	 * @return void
	 * @covers ::load
	 */
	public function testLoad(): void
	{
		$app = $this->application;
		$ff = $this->test_sandbox('inc.php');
		file_put_contents($ff, '<?' . "php\nreturn true;");
		$this->assertTrue($app->load($ff));
	}

	/**
	 * @return void
	 * @throws ConfigurationException
	 * @throws NotFoundException
	 * @throws SemanticsException
	 * @throws SystemException
	 * @covers ::setConfiguration ::configure
	 */
	public function testAssertSettings(): void
	{
		$app = $this->application;
		$configuration = $app->configuration;
		$configuration->setPath([
			Logger::class,
			'utc_time',
		], true);
		foreach ([
			'active',
			'warning',
			'bail',
		] as $assertSetting) {
			$configuration->setPath([
				$app::class,
				Application::OPTION_ASSERT,
			], $assertSetting);
			$app->setConfiguration()->configure();
		}
	}

	/**
	 * @return void
	 * @throws ConfigurationException
	 * @throws NotFoundException
	 * @throws SemanticsException
	 * @throws SystemException
	 */
	public function testBadAssertSettings(): void
	{
		$app = $this->testApplication;
		$configuration = $app->configuration;
		$configuration->setPath([
			$app::class,
			Application::OPTION_ASSERT,
		], 'badsetting');
		$this->expectException(ConfigurationException::class);
		$app->configure();
	}

	/**
	 * @return void
	 * @covers ::setConfiguration ::configure ::deprecated
	 * @return void
	 * @throws ConfigurationException
	 * @throws Deprecated
	 * @throws NotFoundException
	 * @throws SemanticsException
	 * @throws SystemException
	 */
	public function testDeprecatedSettings(): void
	{
		$app = $this->application;
		$configuration = $app->configuration;
		foreach ([
			Application::DEPRECATED_BACKTRACE,
			Application::DEPRECATED_IGNORE,
			Application::DEPRECATED_LOG,
		] as $deprecatedSetting) {
			$configuration->setPath([
				$app::class,
				'deprecated',
			], $deprecatedSetting);
			$app->setConfiguration()->configure();
		}
		$configuration->setPath([
			$app::class,
			Application::OPTION_DEPRECATED,
		], Application::DEPRECATED_LOG);
		$app->setConfiguration()->configure();
		// Should log (no Exception)
		$app->deprecated('Adios');

		// Back to normal
		$configuration->setPath([
			$app::class,
			Application::OPTION_DEPRECATED,
		], Application::DEPRECATED_EXCEPTION);
		$app->setConfiguration()->configure();
	}

	/**
	 * @return void
	 * @covers ::profileTimer
	 */
	public function testProfileTimer(): void
	{
		$start = microtime(true);
		$app = $this->application;
		$app->profileTimer(__METHOD__, microtime(true) - $start);
	}

	protected function acmeWidgetRegistry(string $arg): ACMEThingamajig
	{
		return new ACMEThingamajig($arg);
	}

	/**
	 * @return void
	 * @throws ClassNotFound
	 * @covers \zesk\Application::singletonArgumentsStatic
	 */
	public function test_singletons(): void
	{
		SingletonSampler::$serialNo = $theId = $this->randomHex();

		$sampler = $this->application->singletonArgumentsStatic(SingletonSampler::class);
		$this->assertInstanceOf(SingletonSampler::class, $sampler);
		$this->assertEquals($theId, $sampler->id);

		$sampler2 = $this->application->singletonArgumentsStatic(SingletonSampler::class, ['notId']);
		$this->assertInstanceOf(SingletonSampler::class, $sampler2);
		$this->assertEquals($theId, $sampler2->id);
		$this->assertEquals($sampler, $sampler2);

		$sampler3 = $this->application->singletonArgumentsStatic(SingletonSampler::class, ['fandom']);
		$this->assertInstanceOf(SingletonSampler::class, $sampler3);
		$this->assertEquals($theId, $sampler3->id);
		$this->assertEquals($sampler, $sampler3);
	}

	/**
	 * @return void
	 * @covers \zesk\Application::registerRegistry
	 */
	public function test_add_registry(): void
	{
		$this->testApplication->registerRegistry('acmeWidget', $this->acmeWidgetRegistry(...));

		$acme = $this->testApplication->acmeWidgetRegistry('dude');
		$this->assertInstanceOf(ACMEThingamajig::class, $acme);
		$this->assertEquals('dude', $acme->name);
	}

	/**
	 * @return void
	 * @covers ::__call
	 */
	public function test_missing_factory(): void
	{
		$this->expectException(UnsupportedException::class);

		$this->testApplication->missingFactory();
	}

	/**
	 * @return void
	 * @throws SemanticsException
	 * @covers ::request
	 */
	public function test_missing_request(): void
	{
		$this->expectException(SemanticsException::class);

		$this->testApplication->request();
	}

	public function test_invalidPopRequest(): void
	{
		$this->expectException(SemanticsException::class);
		$request = $this->testApplication->requestFactory();
		$this->testApplication->popRequest($request);
	}

	/**
	 * @return void
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws SemanticsException
	 * @covers ::pushRequest ::popRequest ::request
	 */
	public function test_validRequest(): void
	{
		$request = $this->testApplication->requestFactory();
		$this->testApplication->pushRequest($request);
		$this->assertEquals($request, $this->testApplication->request());
		$this->testApplication->popRequest($request);
	}

	/**
	 * @return void
	 * @throws DirectoryNotFound
	 * @covers ::setDocumentRoot
	 */
	public function test_application_badDocRoot(): void
	{
		$notDir = $this->test_sandbox('not-a-directory');
		$this->expectException(DirectoryNotFound::class);
		$this->testApplication->setDocumentRoot($notDir);
	}

	/**
	 * @return void
	 * @throws DirectoryNotFound
	 * @covers ::addLocalePath
	 */
	public function test_application_badLocalePath(): void
	{
		$notDir = $this->test_sandbox('not-a-directory');
		$this->expectException(DirectoryNotFound::class);
		$this->testApplication->addLocalePath($notDir);
	}

	/**
	 * @return void
	 * @covers ::zeskCommandPath ::addZeskCommandPath
	 */
	public function test_application_badZCP(): void
	{
		$notDir = $this->test_sandbox('not-a-directory');
		$start = $this->testApplication->zeskCommandPath();

		try {
			$this->testApplication->addZeskCommandPath([
				$this->test_sandbox(),
				$notDir,
			]);
			$this->fail('Should throw directory not found');
		} catch (DirectoryNotFound) {
			$result = $this->testApplication->zeskCommandPath();
			$this->assertEquals($result, $start);
		}
	}

	/**
	 * @throws DirectoryNotFound
	 * @covers ::addZeskCommandPath ::zeskCommandPath
	 */
	public function test_application_sameZCP(): void
	{
		$this->testApplication->addZeskCommandPath($this->test_sandbox());
		$start = $this->testApplication->zeskCommandPath();
		$this->testApplication->addZeskCommandPath($start);
		$this->testApplication->addZeskCommandPath($start);
		$result = $this->testApplication->zeskCommandPath();
		$this->assertEquals($result, $start);
	}

	/**
	 * @return void
	 * @throws SemanticsException
	 * @covers ::command
	 */
	public function test_noCommand(): void
	{
		$this->expectException(SemanticsException::class);
		$this->testApplication->command();
	}

	/**
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws SemanticsException
	 * @throws SystemException
	 * @throws NotFoundException
	 * @covers ::localeFactory ::setLocale ::invokeHooks
	 */
	public function test_setLocale(): void
	{
		$this->testApplication->configure();
		$fr = $this->testApplication->localeFactory('FR');
		$this->assertArrayNotHasKey(Application::HOOK_LOCALE, $this->testApplication->hooksCalled);

		$this->testApplication->setLocale($fr);
		$this->assertArrayHasKey(Application::HOOK_LOCALE, $this->testApplication->hooksCalled);
		$this->assertEquals($fr, $this->testApplication->locale);
		$this->assertInArray('object', $this->testApplication->hooksCalled[Application::HOOK_LOCALE]);
		$this->assertInArray('static', $this->testApplication->hooksCalled[Application::HOOK_LOCALE]);
	}

	/**
	 * @throws ConfigurationException
	 * @throws SystemException
	 * @throws SemanticsException
	 * @throws NotFoundException
	 * @covers ::configure ::setCommand ::invokeHooks
	 */
	public function test_setCommand(): void
	{
		$newApplication = $this->testApplication;
		$newApplication->configure();

		$command = new TestCommand($newApplication);
		$command2 = new Test2Command($newApplication);

		$this->assertArrayNotHasKey(Application::HOOK_COMMAND, $newApplication->hooksCalled, implode(', ', array_keys($newApplication->hooksCalled)));
		$newApplication->setCommand($command);
		$this->assertArrayHasKey(Application::HOOK_COMMAND, $newApplication->hooksCalled, implode(', ', array_keys($newApplication->hooksCalled)));

		$this->assertEquals($command, $newApplication->command());
		$this->assertEquals($newApplication->hooksCalled[Application::HOOK_COMMAND], $command);

		$newApplication->setCommand($command2);
		$this->assertEquals($command2, $newApplication->command());
		$this->assertEquals($newApplication->hooksCalled[Application::HOOK_COMMAND], $command2);
	}

	/**
	 * @return void
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws DirectoryCreate
	 * @throws DirectoryNotFound
	 * @throws DirectoryPermission
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @throws NotFoundException
	 * @throws ParameterException
	 * @throws ParseException
	 * @throws SemanticsException
	 * @throws SystemException
	 * @covers ::documentRoot ::setDocumentRoot
	 * @covers ::configured ::isConfigured ::configureInclude
	 * @covers ::version ::setVersion
	 * @covers ::setMaintenance ::maintenance
	 */
	public function testApplicationBasics(): void
	{
		$publicRoot = $this->testApplication->documentRoot('public');
		Directory::depend($publicRoot);

		$newApplication = $this->testApplication;

		$this->assertTrue($newApplication->optionBool('isSecondary'));

		/* setApplicationRoot */
		$this->assertEquals($newApplication->path('.data/'), $newApplication->paths->data());


		$newApplication->modules->loadMultiple([
			'PHPUnit',
			'Doctrine',
			'Repository',
		]);

		/* maintenance Loading */
		$tempFile = $newApplication->paths->expand(TestApplication::TEST_MAINTENANCE_FILE);
		File::put($tempFile, JSON::encodePretty(['enabled' => false]));

		/* setDocumentRoot */
		$newApplication->setDocumentRoot($publicRoot, 'foo');

		/* id */
		$this->assertStringStartsWith($newApplication::class, $newApplication->id());

		/* version/setVersion */
		$this->assertEquals('1.0.0', $newApplication->version());
		$this->assertInstanceOf(Application::class, $newApplication->setVersion('2.0.0'));
		$this->assertEquals('2.0.0', $newApplication->version());

		/* isConfigured */
		$this->assertFalse($newApplication->isConfigured());

		$myConfigConf = $this->sandbox('newapp.conf');
		$myConfigJSON = $this->sandbox('newapp.json');
		File::put($myConfigConf, implode("\n", [
			'zesk___Application__version=3.0.0',
		]));
		File::put($myConfigJSON, JSON::encodePretty(['zesk\\Application' => ['version' => '4.0.0']]));

		/* configureInclude */
		$newApplication->configureInclude([$myConfigConf], true);
		$newApplication->configureInclude([$myConfigJSON], false);

		$this->assertEquals([
			$myConfigConf,
			$myConfigJSON,
		], $newApplication->includes());

		$this->assertFalse($newApplication->isConfigured());

		$newApplication->setMaintenance(false);

		/* maintenance/configure/configured/reconfigure */
		$this->assertFalse($newApplication->maintenance());
		$newApplication->configure();
		$this->assertFalse($newApplication->maintenance());

		$this->assertFalse($newApplication->configured());
		$this->assertTrue($newApplication->isConfigured());
		$this->assertTrue($newApplication->configured(true));

		$newApplication->configureInclude([]);
		$newApplication->reconfigure();

		$rootRequest = Request::factory($newApplication, 'https://www.example.com/home');
		$wantedIP = '10.0.0.71';
		$rootRequest->initializeFromSettings([Request::OPTION_REMOTE_IP => $wantedIP] + $rootRequest->variables());
		$deeperRequest = Request::factory($newApplication, $rootRequest);

		$this->assertEquals($wantedIP, $deeperRequest->remoteIP());
		$this->requestRoundTrip($rootRequest, 'home', '10.0.0.71');

		$dumpRoute = [
			'content' => '{route}',
			'map' => [
				'route',
			],
			'id' => 'dumpRoute',
			'_source' => $this->application->path('/tests/testZesk/TestApplication.router'),
			'weight' => 0.007,
			'class' => 'zesk\\Route\\Content',
		];
		$this->requestRoundTrip($rootRequest, 'dump/route', JSON::encodePretty($dumpRoute));

		$this->assertNotCount(0, $newApplication->controllers());

		$this->assertFalse($newApplication->maintenance());
		$newApplication->setMaintenance(true);
		$this->assertTrue($newApplication->maintenance());
		$newApplication->setMaintenance(false);
		$this->assertFalse($newApplication->maintenance());
		// TODO move to TestShareController.php
		// $this->assertEquals(['zesk' => $newApplication->zeskHome('share')], $newApplication->sharePath());
		$this->assertEquals($newApplication->path('.data/extra'), $newApplication->dataPath('extra'));
	}

	/**
	 * @throws ConfigurationException
	 * @throws SemanticsException
	 * @throws SystemException
	 * @throws NotFoundException
	 * @covers ::configure ::setMaintenance ::maintenance
	 */
	public function test_preventMaintenance(): void
	{
		$newApplication = $this->testApplication;
		$newApplication->configure();

		$newApplication->setOption('preventMaintenance', null);

		$newApplication->setMaintenance(false);
		$this->assertFalse($newApplication->maintenance());

		$newApplication->setOption('preventMaintenance', true);
		$newApplication->setMaintenance(false);
		$this->assertFalse($newApplication->maintenance());
		$this->expectException(SemanticsException::class);
		$newApplication->setMaintenance(true);
	}

	/**
	 * @throws ConfigurationException
	 * @throws SemanticsException
	 * @throws SystemException
	 * @throws NotFoundException
	 * @covers ::configure ::setMaintenance ::maintenance
	 */
	public function test_preventMaintenanceThrow(): void
	{
		$newApplication = $this->testApplication;
		$newApplication->configure();

		$newApplication->setOption('preventMaintenance', null);

		$newApplication->setMaintenance(false);
		$this->assertFalse($newApplication->maintenance());

		$newApplication->setOption('preventMaintenance', 'throw');
		$newApplication->setMaintenance(false);
		$this->assertFalse($newApplication->maintenance());
		$this->expectException(SemanticsException::class);
		$newApplication->setMaintenance(true);
	}

	private function requestRoundTrip(Request $rootRequest, string $uri, string $expected): void
	{
		$newApplication = $rootRequest->application;
		$anotherRequest = Request::factory($newApplication, $rootRequest);
		$anotherRequest->setPath($uri);
		$anotherResponse = $newApplication->main($anotherRequest);
		$this->assertEquals(Response::CONTENT_TYPE_HTML, $anotherResponse->contentType());
		$this->assertEquals($expected, $anotherResponse->content());
	}

	/**
	 * @return void
	 * @throws ClassNotFound
	 * @throws ConfigurationException
	 * @throws DirectoryCreate
	 * @throws DirectoryPermission
	 * @throws FilePermission
	 * @throws NotFoundException
	 * @throws SemanticsException
	 * @throws SystemException
	 * @covers ::cachePath ::cacheClear
	 */
	public function test_cacheClear(): void
	{
		$newApplication = $this->testApplication;
		$newApplication->configure();

		Directory::depend($newApplication->cachePath());

		File::put($newApplication->cachePath('hello'), 'hello');

		$newApplication->cacheClear();
		$newApplication->cacheClear();
	}

	/**
	 * @return void
	 * @covers \zesk\Application::applicationClass
	 */
	public function testApplicationClass(): void
	{
		$this->assertIsString($this->application->applicationClass());
		$this->assertTrue(class_exists($this->application->applicationClass()));
		$this->assertInstanceOf($this->application->applicationClass(), $this->application);
	}

	/**
	 * @return void
	 * @throws NotFoundException
	 * @throws FileNotFound
	 * @throws FilePermission
	 * @covers ::addCommandPath
	 */
	public function testCommandPath(): void
	{
		$this->application->addCommandPath($this->test_sandbox());
		$bin = Directory::path($this->test_sandbox('mstest'));
		File::put($bin, "#!/bin/bash\necho file1; echo file2;");
		File::chmod($bin, 493 /* 0o755 */);
		$ls = $this->application->paths->which('mstest');
		$this->assertEquals($bin, $ls);
	}

	/**
	 * @return void
	 * @covers ::console ::setConsole
	 */
	public function testConsole2(): void
	{
		$savedConsole = $this->application->console();
		$this->assertEquals($this->application, $this->application->setConsole(true));
		$this->assertTrue($this->application->console());
		$this->assertEquals($this->application, $this->application->setConsole(false));
		$this->assertFalse($this->application->console());
		$this->assertEquals($this->application, $this->application->setConsole($savedConsole));
		$this->assertEquals($savedConsole, $this->application->console());
	}

	/**
	 * @covers ::deprecated
	 * @throws Deprecated
	 */
	public function testDeprecated(): void
	{
		$this->application->setDeprecated(Application::DEPRECATED_EXCEPTION);
		$this->expectException(Deprecated::class);
		$this->application->deprecated('Should throw an exception');
	}

	/**
	 * @covers ::deprecated ::setDeprecated
	 */
	public function testDisableDeprecated(): void
	{
		$deprecatedList = [
			Application::DEPRECATED_IGNORE,
			Application::DEPRECATED_BACKTRACE,
			Application::DEPRECATED_LOG,
			Application::DEPRECATED_EXCEPTION,
		];

		$old = $this->application->setDeprecated(Application::DEPRECATED_IGNORE);
		$this->assertInArray($old, $deprecatedList);
		foreach ($deprecatedList as $deprecatedValue) {
			$this->application->setDeprecated($deprecatedValue);
		}
		$this->application->setDeprecated(Application::DEPRECATED_IGNORE);
		$this->application->deprecated('Should do nothing');
		$this->application->setDeprecated($old);
	}

	/**
	 * @return void
	 * @covers ::development :setDevelopment
	 */
	public function testDevelopment(): void
	{
		$app = $this->application;
		$old_value = $app->development();
		$app->setDevelopment(true);
		$this->assertTrue($app->development());
		$app->setDevelopment(false);
		$this->assertFalse($app->development());
		$app->setDevelopment($old_value);
	}

	/**
	 * @covers ::configure ::addThemePath ::index
	 * @covers \zesk\Response::setCacheForever
	 * @covers \zesk\Response::setCacheFor
	 *
	 * @return void
	 * @throws ConfigurationException
	 * @throws DirectoryNotFound
	 * @throws NotFoundException
	 * @throws SemanticsException
	 * @throws SystemException
	 */
	public function testApplicationIndex(): void
	{
		$themePath = $this->test_sandbox();

		file_put_contents(Directory::path($themePath, 'Exception.tpl'), 'Hello, whirled');

		$newApplication = $this->testApplication;
		$newApplication->configure();

		$this->assertInstanceOf(Application::class, $newApplication);
		$newApplication->addThemePath($themePath);

		$newApplication->configuration->setPath([
			Response::class,
			Response::OPTION_SKIP_HEADERS,
		], true);

		$this->expectOutputString("<!DOCTYPE html>\n<html><head></head>\n<body>Hello, whirled\n</body>\n</html>");
		$response = $newApplication->index();

		$response->setCacheForever();
		$response->setCacheFor(1);
	}
}
