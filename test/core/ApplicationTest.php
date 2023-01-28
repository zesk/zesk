<?php
declare(strict_types=1);

/**
 *
 */

namespace zesk;

class ACMETest {
	public string $name;

	public function __construct(string $name) {
		$this->name = $name;
	}
}

/**
 *
 * @author kent
 * @see TestApplication
 * @see TestRequest
 */
class ApplicationTest extends TestApplicationUnitTest {
	public function initialize(): void {
	}

	protected function acmeWidgetRegistry(Application $application, string $arg): ACMETest {
		return new ACMETest($arg);
	}

	public function test_add_registry(): void {
		$this->testApplication->registerRegistry('acmeWidget', $this->acmeWidgetRegistry(...));

		$acme = $this->testApplication->acmeWidgetRegistry('dude');
		$this->assertInstanceOf(ACMETest::class, $acme);
		$this->assertEquals('dude', $acme->name);
	}

	public function test_missing_factory(): void {
		$this->expectException(Exception_Unsupported::class);

		$this->testApplication->missingFactory();
	}

	public function test_missing_request(): void {
		$this->expectException(Exception_Semantics::class);

		$this->testApplication->request();
	}

	public function test_invalidPopRequest(): void {
		$this->expectException(Exception_Semantics::class);
		$request = $this->testApplication->requestFactory();
		$this->assertInstanceOf(Request::class, $request);
		$this->testApplication->popRequest($request);
	}

	public function test_validRequest(): void {
		$request = $this->testApplication->requestFactory();
		$this->assertInstanceOf(Request::class, $request);
		$this->testApplication->pushRequest($request);
		$this->assertEquals($request, $this->testApplication->request());
		$this->testApplication->popRequest($request);
	}

	public function test_application_badDocRoot(): void {
		$notDir = $this->test_sandbox('not-a-directory');
		$this->expectException(Exception_Directory_NotFound::class);
		$this->testApplication->setDocumentRoot($notDir);
	}

	public function test_application_badModulePath(): void {
		$notDir = $this->test_sandbox('not-a-directory');
		$this->expectException(Exception_Directory_NotFound::class);
		$this->testApplication->addModulePath($notDir);
	}

	public function test_application_badLocalePath(): void {
		$notDir = $this->test_sandbox('not-a-directory');
		$this->expectException(Exception_Directory_NotFound::class);
		$this->testApplication->addLocalePath($notDir);
	}

	public function test_application_badZCP(): void {
		$notDir = $this->test_sandbox('not-a-directory');
		$start = $this->testApplication->zeskCommandPath();

		try {
			$this->testApplication->addZeskCommandPath([$this->test_sandbox(), $notDir]);
			$this->fail('Should throw directory not found');
		} catch (Exception_Directory_NotFound) {
			$result = $this->testApplication->zeskCommandPath();
			$this->assertEquals($result, $start);
		}
	}

	public function test_application_sameZCP(): void {
		$this->testApplication->addZeskCommandPath($this->test_sandbox());
		$start = $this->testApplication->zeskCommandPath();
		$this->testApplication->addZeskCommandPath($start);
		$this->testApplication->addZeskCommandPath($start);
		$result = $this->testApplication->zeskCommandPath();
		$this->assertEquals($result, $start);
	}

	/**
	 * @return void
	 * @throws Exception_Semantics
	 */
	public function test_noCommand(): void {
		$this->expectException(Exception_Semantics::class);
		$this->testApplication->command();
	}

	public function test_setLocale(): void {
		$this->testApplication->configure();
		$fr = $this->testApplication->localeFactory('FR');
		$this->assertArrayNotHasKey(Application::class . '::setLocale', $this->testApplication->hooksCalled);

		$this->testApplication->setLocale($fr);
		$this->assertArrayHasKey(Application::class . '::setLocale', $this->testApplication->hooksCalled);
		$this->assertEquals($fr, $this->testApplication->locale);
	}

	public function test_singleton_hook(): void {
		$newApplication = $this->testApplication;
		$newApplication->configure();

		$this->assertArrayNotHasKey(Application::class . '::singleton_zesk_TestModel', $newApplication->hooksCalled);

		$command = $newApplication->modelSingleton(TestModel::class);
		$this->assertInstanceOf(TestModel::class, $command);

		$this->assertArrayHasKey(
			Application::class . '::singleton_zesk_TestModel',
			$newApplication->hooksCalled,
			'Keys: ' . implode(', ', array_keys($newApplication->hooksCalled))
		);
	}

	public function test_setCommand(): void {
		$newApplication = $this->testApplication;
		$newApplication->configure();

		$command = new TestCommand($newApplication);
		$command2 = new Test2Command($newApplication);

		$newApplication->setCommand($command);
		$this->assertEquals($command, $newApplication->command());
		$newApplication->setCommand($command);
		$this->assertEquals($command, $newApplication->command());

		$this->assertArrayHasKey(Application::class . '::command', $newApplication->hooksCalled);
		$this->assertEquals($newApplication->hooksCalled[Application::class . '::command'], $command);

		$newApplication->setCommand($command2);
		$this->assertEquals($command2, $newApplication->command());
		$newApplication->setCommand($command2);
		$this->assertEquals($command2, $newApplication->command());

		$this->assertArrayHasKey(Command::class . '::replacedWith', $newApplication->hooksCalled, implode(
			', ',
			array_keys($newApplication->hooksCalled)
		));
		$this->assertEquals($newApplication->hooksCalled[Command::class . '::replacedWith'], $command2);
		$this->assertArrayHasKey(Application::class . '::command', $newApplication->hooksCalled);
		$this->assertEquals($newApplication->hooksCalled[Application::class . '::command'], $command2);
	}

	/**
	 * @return void
	 * @throws Exception_Class_NotFound
	 * @throws Exception_Configuration
	 * @throws Exception_Directory_Create
	 * @throws Exception_Directory_NotFound
	 * @throws Exception_Directory_Permission
	 * @throws Exception_File_NotFound
	 * @throws Exception_File_Permission
	 * @throws Exception_Invalid
	 * @throws Exception_NotFound
	 * @throws Exception_Parameter
	 * @throws Exception_Parse
	 * @throws Exception_Unsupported
	 */
	public function test_application_basics(): void {
		$publicRoot = $this->testApplication->documentRoot('public');
		Directory::depend($publicRoot);

		$newApplication = $this->testApplication;

		$this->assertTrue($newApplication->optionBool('isSecondary'));
		$this->assertInstanceOf(Application::class, $newApplication);

		/* setApplicationRoot */
		$this->assertEquals($newApplication->path('cache/temp/'), $newApplication->paths->temporary());
		$this->assertEquals($newApplication->path('data/'), $newApplication->paths->data());


		/* autoloadPath */
		$paths = [];
		$this->assertEquals($paths, array_keys($newApplication->autoloadPath()));

		$newApplication->modules->loadMultiple(['PHPUnit', 'MySQL', 'Database']);
		$paths = [];
		$paths[] = $newApplication->zeskHome('modules/PHPUnit/classes');
		$paths[] = $newApplication->zeskHome('modules/MySQL/classes');
		$paths[] = $newApplication->zeskHome('modules/Database/classes');
		$this->assertEquals($paths, array_keys($newApplication->autoloadPath()));

		/* maintenance Loading */
		$tempFile = $newApplication->paths->expand(TestApplication::TEST_MAINTENANCE_FILE);
		File::put($tempFile, JSON::encodePretty(['enabled' => false]));

		/* setDocumentRoot */
		$newApplication->setDocumentRoot($publicRoot, 'foo');

		/* id */
		$this->assertEquals($newApplication::class, $newApplication->id());

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

		$this->assertEquals([$myConfigConf, $myConfigJSON], $newApplication->includes());

		$this->assertFalse($newApplication->isConfigured());

		/* maintenance/configure/configured/reconfigure */
		$this->assertFalse($newApplication->maintenance());
		$newApplication->configure();
		$this->assertFalse($newApplication->maintenance());

		$this->assertFalse($newApplication->configured());
		$this->assertTrue($newApplication->isConfigured());
		$this->assertTrue($newApplication->configured(true));

		$newApplication->configureInclude([]);
		$newApplication->reconfigure();

		$rootRequest = Request::factory($newApplication, 'http://www.example.com/home');
		$wantedIP = '10.0.0.71';
		$rootRequest->initializeFromSettings([Request::OPTION_REMOTE_IP => $wantedIP] + $rootRequest->variables());
		$deeperRequest = Request::factory($newApplication, $rootRequest);

		$this->assertEquals($wantedIP, $deeperRequest->remoteIP());
		$this->requestRoundTrip($rootRequest, 'home', '10.0.0.71');

		$this->requestRoundTrip($rootRequest, 'dump/route', "{\n    \"content\": \"{route}\",\n    \"map\": [\n        \"route\"\n    ],\n    \"_source\": \"/zesk/test/classes/TestApplication.router\",\n    \"weight\": 0.002,\n    \"class\": \"zesk\\\\Route_Content\"\n}");

		$this->assertNotCount(0, $newApplication->controllers());

		$this->assertFalse($newApplication->maintenance());
		$this->assertTrue($newApplication->setMaintenance(true));
		$this->assertTrue($newApplication->maintenance());
		$this->assertTrue($newApplication->setMaintenance(false));
		$this->assertFalse($newApplication->maintenance());

		$newApplication->setOption('preventMaintenance', true);
		$this->assertFalse($newApplication->maintenance());
		$this->assertFalse($newApplication->setMaintenance(true));
		$this->assertTrue($newApplication->setMaintenance(false));
		$this->assertFalse($newApplication->maintenance());

		$newApplication->setOption('preventMaintenance', false);
		$this->assertTrue($newApplication->setMaintenance(true));
		$this->assertTrue($newApplication->maintenance());
		$newApplication->setOption('preventMaintenance', 'throw');
		$this->assertTrue($newApplication->maintenance());
		$this->assertFalse($newApplication->setMaintenance(true));
		$this->assertTrue($newApplication->setMaintenance(false));
		$this->assertFalse($newApplication->maintenance());
		$this->assertFalse($newApplication->setMaintenance(true));
		$this->assertFalse($newApplication->maintenance());

		$this->assertEquals(['zesk' => $newApplication->zeskHome('share')], $newApplication->sharePath());
		$this->assertEquals($newApplication->path('data/extra'), $newApplication->dataPath('extra'));
	}

	private function requestRoundTrip(Request $rootRequest, string $uri, string $expected): void {
		$newApplication = $rootRequest->application;
		$anotherRequest = Request::factory($newApplication, $rootRequest);
		$anotherRequest->setPath($uri);
		$anotherResponse = $newApplication->main($anotherRequest);
		$this->assertEquals(Response::CONTENT_TYPE_HTML, $anotherResponse->contentType());
		$this->assertEquals($expected, $anotherResponse->content());
	}

	public function test_application_index(): void {
		$themePath = $this->test_sandbox();

		file_put_contents(path($themePath, 'Exception.tpl'), 'Hello, whirled');

		$newApplication = $this->testApplication;
		$newApplication->configure();

		$this->assertInstanceOf(Application::class, $newApplication);
		$newApplication->addThemePath($themePath);

		$newApplication->configuration->setPath([Response::class, Response::OPTION_SKIP_HEADERS], true);

		$this->expectOutputString("<!DOCTYPE html>\n<html><head></head>\n<body>Hello, whirled\n</body>\n</html>");
		$response = $newApplication->index();
		$this->assertInstanceOf(Response::class, $response);

		$response->setCacheForever();
	}

	public function test_cacheClear(): void {
		$newApplication = $this->testApplication;
		$newApplication->configure();

		Directory::depend($newApplication->cachePath());

		File::put($newApplication->cachePath('hello'), 'hello');

		$newApplication->cacheClear();
		$newApplication->cacheClear();
	}

	public function test_setTemporary_not(): void {
		$this->expectException(Exception_Directory_NotFound::class);
		$this->application->paths->setTemporary('./cache/foo');
	}

	public function test_setTemporary_yes(): void {
		$oldTemp = $this->testApplication->paths->temporary();
		$path = './cache/foo';
		Directory::depend($this->testApplication->paths->expand($path));
		$this->testApplication->paths->setTemporary($path);
		Directory::delete($this->testApplication->paths->expand($path));
		Directory::depend($oldTemp);
		$this->testApplication->paths->setTemporary($oldTemp);
	}

	public function test_pid(): void {
		$this->testApplication->process->id();
	}

	public function test_running(): void {
		$process = $this->testApplication->process;
		$pid = $this->testApplication->process->id();
		$this->assertIsInteger($pid);
		$this->assertTrue($process->alive($pid));
		$this->assertFalse($process->alive(32766));
	}

	public function test_zeskCommandPath(): void {
		$file = $this->test_sandbox('testlike.php');
		$contents = file_get_contents($this->testApplication->zeskHome('test/test-data/testlike.php'));
		file_put_contents($file, $contents);
		$loader = CommandLoader::factory()->setApplication($this->testApplication);
		$this->testApplication->addZeskCommandPath($this->test_sandbox());
		$pid = $this->testApplication->process->id();
		$className = 'TestCommand' . $pid;
		$randomShortcut = $this->randomHex();
		$shortcuts = ['test-command', $randomShortcut];
		$testCommand = [];
		$testCommand[] = '<?' . "php\n namespace zesk;";
		$testCommand[] = "class $className extends Command_Base {";
		$testCommand[] = '	protected array $shortcuts = ' . PHP::dump($shortcuts) . ';';
		$testCommand[] = '	function run(): int {';
		$testCommand[] = '		echo getcwd();';
		$testCommand[] = '		return 0;';
		$testCommand[] = '	}';
		$testCommand[] = '}';

		File::put($this->test_sandbox('testCommand.php'), implode("\n", $testCommand));
		$allShortcuts = $loader->collectCommandShortcuts();

		$this->assertArrayHasKeys($shortcuts, $allShortcuts);
		$this->assertEquals(__NAMESPACE__ . '\\' . $className, $allShortcuts['test-command']);
		$this->assertEquals(__NAMESPACE__ . '\\' . $className, $allShortcuts[$randomShortcut]);
	}
}
