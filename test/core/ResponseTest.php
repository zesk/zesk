<?php
declare(strict_types=1);
/**
 *
 */

namespace zesk;

/**
 *
 * @author kent
 * @see TestApplication
 * @see TestRequest
 */
class ResponseTest extends TestApplicationUnitTest {
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

		$this->assertInstanceOf(Application::class, $newApplication);
		$this->assertInstanceOf(TestApplication::class, $newApplication);

		/* setApplicationRoot */

		$newApplication->modules->loadMultiple(['PHPUnit', 'MySQL', 'Database']);

		$this->assertFalse($newApplication->isConfigured());
		$newApplication->configure();
		$this->assertTrue($newApplication->isConfigured());

		$rootRequest = Request::factory($newApplication, 'http://www.example.com/home');
		$wantedIP = '10.0.0.2';
		$rootRequest->initializeFromSettings([Request::OPTION_REMOTE_IP => $wantedIP] + $rootRequest->variables());

		$response = $this->requestRoundTrip($rootRequest, 'home', $wantedIP);

		$this->assertIsInteger($response->id());
		$this->assertEquals(0, $response->id());
		$this->assertEquals(HTTP::STATUS_OK, $response->status());
		$this->assertEquals('OK', $response->statusMessage());


		$this->assertTrue($response->isHTML());
		$this->assertFalse($response->isJSON());

		$this->assertEquals($response, $response->makeJSON());
		$this->assertFalse($response->isHTML());
		$this->assertTrue($response->isJSON());

		$this->assertEquals($response, $response->makeHTML());
		$this->assertTrue($response->isHTML());
		$this->assertFalse($response->isJSON());

		$this->assertEquals($response, $response->setContentType(Response::CONTENT_TYPE_JSON));
		$this->assertEquals('application/json', $response->contentType());
		$this->assertFalse($response->isHTML());
		$this->assertTrue($response->isJSON());


		$this->assertEquals($response, $response->setContentType('text/html'));
		$this->assertTrue($response->isHTML());
		$this->assertFalse($response->isJSON());

		$this->assertEquals($response, $response->noCache());
		$this->assertEquals($response, $response->setOutputHandler(Response::CONTENT_TYPE_HTML));

		$jsonResponse = [];
		$this->assertEquals($jsonResponse, ArrayTools::filterKeys($response->toJSON(), null, ['elapsed']));

		$response->setCacheFor(100);
		$response->cacheSave($this->testApplication->cacheItemPool(), $rootRequest->url());

		$id = $response->id_counter();
		$this->assertEquals($id + 1, $response->id_counter());

		$this->assertEquals('', $response->title());
		$this->assertEquals($response, $response->setTitle('Hello, world'));
		$this->assertEquals('Hello, world', $response->title());

		$this->assertEquals([], $response->htmlAttributes());
		$sampleAttributes = [
			'class' => 'nojs', 'data-application' => 'thingamabob',
		];
		$this->assertEquals($response, $response->setHTMLAttributes($sampleAttributes));
		$this->assertEquals($sampleAttributes, $response->htmlAttributes());

		$sampleAttributes = [
			'class' => 'bodystyle', 'data-application' => 'poughkeepsie',
		];

		$this->assertEquals([], $response->bodyAttributes());
		$this->assertEquals($response, $response->setBodyAttributes($sampleAttributes));
		$this->assertEquals($sampleAttributes, $response->bodyAttributes());

		$sampleKeywords = 'buy moar stuff';

		try {
			$this->assertEquals('', $response->metaKeywords());
			$this->fail('should throw ' . Exception_Key::class);
		} catch (Exception_Key) {
		}

		$this->assertEquals($response, $response->setMetaKeywords($sampleKeywords));
		$this->assertEquals($sampleKeywords, $response->metaKeywords());

		$sampleDesc = 'a website where you can purchase more things for your closets';

		try {
			$this->assertEquals('', $response->metaDescription());
			$this->fail('should throw ' . Exception_Key::class);
		} catch (Exception_Key) {
		}
		$this->assertEquals($response, $response->setMetaDescription($sampleDesc));
		$this->assertEquals($sampleDesc, $response->metaDescription());

		$this->assertEquals('page', $response->pageTheme());
		$this->assertEquals($response, $response->setPageTheme('text'));
		$this->assertEquals('text', $response->pageTheme());

		$nocache = false;
		$this->assertEquals($response, $response->javascript(['https://www.example.com/main.js'], [
			'nocache' => $nocache, 'id' => 'main',
		]));
		$this->assertEquals($response, $response->javascript(['https://www.example.com/last.js'], [
			'nocache' => $nocache, 'after' => 'main',
		]));
		$this->assertEquals($response, $response->javascript('https://www.example.com/first.js', [
			'before' => 'main', 'nocache' => $nocache,
		]));
		$this->assertEquals($response, $response->javascript('https://www.example.com/not-last.js', [
			'before' => 'last', 'nocache' => $nocache,
		]));
		$this->assertEquals($response, $response->inlineJavaScript('alert(\'boo!\');', [
			'before' => 'first', 'nocache' => $nocache,
		]));
		$expectedScripts = [];
		$expectedScripts[] = '<script>alert(\'boo!\');</script>';
		$expectedScripts[] = '<script src="https://www.example.com/first.js"></script>';
		$expectedScripts[] = '<script src="https://www.example.com/main.js" id="main"></script>';
		$expectedScripts[] = '<script src="https://www.example.com/not-last.js"></script>';
		$expectedScripts[] = '<script src="https://www.example.com/last.js"></script>';
		$scripts = $response->html()->scripts();
		$html = [];
		foreach ($scripts as $script) {
			$html[] = HTML::tag($script['name'], $script['attributes'], $script['content']);
		}
		$this->assertEquals($expectedScripts, $html);

		$shortcutIcon = 'https://www.example.coim/icon.png';
		// Has side effects (adds "icon")
		$this->assertEquals('', $response->html()->shortcutIcon());
		$this->assertEquals($response, $response->html()->setShortcutIcon($shortcutIcon));
		$this->assertEquals($shortcutIcon, $response->html()->shortcutIcon());
	}

	private function requestRoundTrip(Request $rootRequest, string $uri, string $expected): Response {
		$newApplication = $rootRequest->application;
		$anotherRequest = Request::factory($newApplication, $rootRequest);
		$anotherRequest->setPath($uri);
		$anotherResponse = $newApplication->main($anotherRequest);
		$this->assertEquals(Response::CONTENT_TYPE_HTML, $anotherResponse->contentType());
		$this->assertEquals($expected, $anotherResponse->content());
		return $anotherResponse;
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

		// Add testlike.php
		$shortcuts[] = 'test-like';

		File::put($this->test_sandbox('testCommand.php'), implode("\n", $testCommand));
		$allShortcuts = $loader->collectCommandShortcuts();

		$this->assertArrayHasKeys($shortcuts, $allShortcuts);
		$this->assertEquals(__NAMESPACE__ . '\\' . $className, $allShortcuts['test-command']);
		$this->assertEquals(__NAMESPACE__ . '\\' . $className, $allShortcuts[$randomShortcut]);
	}
}
