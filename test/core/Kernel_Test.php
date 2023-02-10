<?php
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
declare(strict_types=1);

namespace zesk;

class SingletonSampler {
	public static string $serialNo = '';

	public string $id;

	public function __construct(string $id) {
		$this->id = $id;
	}

	public static function singleton(): self {
		return new self(self::$serialNo);
	}
}

class Kernel_Test extends UnitTest {
	/**
	 * @var int
	 */
	public int $order = 0;

	/**
	 * @var bool
	 */
	public bool $_hook_was_called = false;

	public function test_patterns(): void {
		for ($i = 0; $i < 255; $i++) {
			$this->assertTrue(preg_match('/^' . PREG_PATTERN_IP4_DIGIT . '$/', strval($i)) !== 0);
		}
		for ($i = -255; $i < 0; $i++) {
			$this->assertFalse(preg_match('/^' . PREG_PATTERN_IP4_DIGIT . '$/', strval($i)) !== 0);
		}
		for ($i = 256; $i < 32767; $i++) {
			$this->assertFalse(preg_match('/^' . PREG_PATTERN_IP4_DIGIT . '$/', strval($i)) !== 0);
		}
		for ($i = 1; $i < 255; $i++) {
			$this->assertTrue(preg_match('/^' . PREG_PATTERN_IP4_DIGIT1 . '$/', strval($i)) !== 0);
		}
		for ($i = -255; $i < 1; $i++) {
			$this->assertFalse(preg_match('/^' . PREG_PATTERN_IP4_DIGIT1 . '$/', strval($i)) !== 0);
		}
		for ($i = 256; $i < 32767; $i++) {
			$this->assertFalse(preg_match('/^' . PREG_PATTERN_IP4_DIGIT1 . '$/', strval($i)) !== 0);
		}
	}

	public static function _test_hook_order_1st(Kernel_Test $test): void {
		$test->assertEquals($test->order, 0);
		$test->order++;
	}

	public static function _test_hook_order_2nd(Kernel_Test $test): void {
		$test->assertEquals($test->order, 1);
		$test->order++;
	}

	public function test_hook_order(): void {
		$hooks = $this->application->hooks;
		// Nothing registered
		$this->order = 0;
		$hooks->call('test_hook_order', $this);
		$this->assertEquals($this->order, 0);

		// Add hooks
		$hooks->add('test_hook_order', __CLASS__ . '::_test_hook_order_1st');
		$hooks->add('test_hook_order', __CLASS__ . '::_test_hook_order_2nd');

		// Test ordering
		$this->order = 0;
		$hooks->call('test_hook_order', $this);
		$this->assertEquals($this->order, 2);

		// Test clearing
		$hooks->remove('test_hook_order');

		$this->order = 0;
		$hooks->call('test_hook_order', $this);
		$this->assertEquals($this->order, 0);

		// Test "first"
		$hooks->add('test_hook_order', __CLASS__ . '::_test_hook_order_2nd');
		$hooks->add('test_hook_order', __CLASS__ . '::_test_hook_order_1st', [
			'first' => true,
		]);

		// Test ordering
		$this->order = 0;
		$hooks->call('test_hook_order', $this);
		$this->assertEquals($this->order, 2);
	}

	public function test_singletons(): void {
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

	public function test_setk(): void {
		$k = 'a';
		$k1 = 'b';
		$v = md5(microtime());
		$this->application->configuration->setPath([
			$k, 'b',
		], $v);
		$this->application->configuration->setPath([
			$k, 'c',
		], $v);
		$this->application->configuration->setPath([
			$k, 'd',
		], $v);

		$this->assertEquals([
			'b' => $v, 'c' => $v, 'd' => $v,
		], $this->application->configuration->getPath($k));
	}

	public function test_class_hierarchy(): void {
		$app = $this->application;

		$nsprefix = __NAMESPACE__ . '\\';
		$this->assertEquals($app->classes->hierarchy(__NAMESPACE__ . '\\A'), ArrayTools::prefixValues(toList('A;Hookable;Options'), $nsprefix));
		$this->assertEquals($app->classes->hierarchy(__NAMESPACE__ . '\\B'), ArrayTools::prefixValues(toList('B;A;Hookable;Options'), $nsprefix));
		$this->assertEquals($app->classes->hierarchy(__NAMESPACE__ . '\\C'), ArrayTools::prefixValues(toList('C;B;A;Hookable;Options'), $nsprefix));
		$this->assertEquals($app->classes->hierarchy(__NAMESPACE__ . '\\' . 'HTML'), toList(__NAMESPACE__ . '\\' . 'HTML'));
		$this->assertEquals($app->classes->hierarchy(new A($this->application)), ArrayTools::prefixValues(toList('A;Hookable;Options'), __NAMESPACE__ . '\\'));
	}

	public function add_hook_was_called($arg): void {
		$this->assertEquals(2, $arg);
		$this->assertInstanceOf(Application::class, $this->application);
		$this->application->setOption('hook_was_called', true);
	}

	public function hook_was_called(): bool {
		return $this->application->optionBool('hook_was_called');
	}

	public function _addHook(string $name = 'null'): string {
		$closure = function ($arg): void {
			assert($this instanceof self);
			$this->add_hook_was_called($arg);
		};
		$closure->bindTo($this);
		$this->application->hooks->add($name, $closure);
		return $name;
	}

	public function test_application_class(): void {
		$this->assertIsString($this->application->applicationClass());
		$this->assertTrue(class_exists($this->application->applicationClass()));
		$this->assertInstanceOf($this->application->applicationClass(), $this->application);
	}

	public function test_autoload_extension(): void {
		$this->application->autoloader->addExtension('dude');
	}

	public function test_autoload_path(): void {
		$testDir = Directory::depend($this->test_sandbox('lower-prefix'));

		$this->application->autoloader->addPath($testDir, [
			Autoloader::OPTION_LOWER => true,
			Autoloader::OPTION_CLASS_PREFIX => 'zesk\\AutoloaderTest\\', Autoloader::OPTION_EXTENSIONS => ['txt'],
		]);
		File::put("$testDir/hooboy.txt", '<' . "?php\nnamespace zesk\\AutoloaderTest;\nclass HooBoy {}\n");
		$this->assertTrue(class_exists('zesk\\AutoloaderTest\\HooBoy'));
	}

	/**
	 * @param null|string $expected
	 * @param string $class
	 * @param array $extensions
	 * @return void
	 * @dataProvider data_autoloadSearch
	 */
	public function test_autoloadSearch(null|string $expected, string $class, array $extensions, array $modules): void {
		if (count($modules)) {
			$this->application->modules->loadMultiple($modules);
		}
		$autoloader = $this->application->autoloader;
		$tried_path = [];
		$this->assertEquals($expected, $autoloader->search($class, $extensions, $tried_path));
	}

	public static function data_autoloadSearch(): array {
		return [
			//			[ZESK_ROOT . 'zesk/Kernel.php', Kernel::class, ['php'], []],
			//			[ZESK_ROOT . 'zesk/Controller/Theme.php', Controller_Theme::class, ['php', 'sql'], []],
			[ZESK_ROOT . 'modules/ORM/zesk/ORM/Class/User.sql', 'zesk\\ORM\\Class_User', ['sql', 'php'], ['ORM']],
			[ZESK_ROOT . 'modules/ORM/zesk/ORM/Class/User.php', 'zesk\\ORM\\Class_User', ['php', 'sql'], ['ORM']], [
				ZESK_ROOT . 'modules/ORM/zesk/ORM/Class/User.sql', 'zesk\\ORM\\Class_User', ['other', 'inc', 'sql', ],
				['ORM'],
			], [null, 'zesk\\ORM\\User', ['other', 'none', ], ['ORM']],
		];
	}

	public function provider_clean_function() {
		return [
			[
				'', '',
			], [
				'  z e s k \\-O~b@j%e^c t!@#$%', '__z_e_s_k___O_b_j_e_c_t_____',
			], [
				'bunch,of-random-chars', 'bunch_of_random_chars',
			],
		];
	}

	/**
	 * @dataProvider provider_clean_function
	 *
	 * @param string $name
	 * @param string $expected
	 */
	public function test_clean_function($name, $expected): void {
		$result = PHP::cleanFunction($name);
		$this->assertEquals($result, $expected, 'PHP::cleanFunction');
	}

	public function provider_clean_class() {
		return [
			[
				'', '',
			], [
				'  z e s k \\-O~b@j%e^c t!@#$%', '__z_e_s_k_\\_O_b_j_e_c_t_____',
			],
		];
	}

	/**
	 * @dataProvider provider_clean_class
	 *
	 * @param string $name
	 * @param string $expected
	 */
	public function test_clean_class($name, $expected): void {
		$result = PHP::cleanClass($name);
		$this->assertEquals($result, $expected, "PHP::clean_class($name) = $result !== $expected");
	}

	public function test_command_path(): void {
		$add = null;
		$this->application->addCommandPath($this->test_sandbox());
		$bin = path($this->test_sandbox('mstest'));
		File::put($bin, "#!/bin/bash\necho file1; echo file2;");
		File::chmod($bin, 493 /* 0o755 */);
		$ls = $this->application->paths->which('mstest');
		$this->assertEquals($bin, $ls);
	}

	public function test_console(): void {
		$savedConsole = $this->application->console();
		$this->assertEquals($this->application, $this->application->setConsole(true));
		$this->assertEquals(true, $this->application->console());
		$this->assertEquals($this->application, $this->application->setConsole(false));
		$this->assertEquals(false, $this->application->console());
		$this->assertEquals($this->application, $this->application->setConsole($savedConsole));
		$this->assertEquals($savedConsole, $this->application->console());
	}

	/**
	 *
	 */
	public function test_deprecated(): void {
		$this->expectException(Exception_Deprecated::class);
		$this->application->deprecated();
	}

	/**
	 *
	 */
	public function test_disable_deprecated(): void {
		$old = $this->application->setDeprecated(Application::DEPRECATED_IGNORE);
		$this->application->deprecated();
		$this->application->setDeprecated($old);
	}

	public function test_development(): void {
		$app = $this->application;
		$old_value = $app->development();
		$app->setDevelopment(true);
		$this->assertTrue($app->development());
		$app->setDevelopment(false);
		$this->assertFalse($app->development());
		$app->setDevelopment($old_value);
	}

	public function test_factory(): void {
		$class = __NAMESPACE__ . '\\Model';
		$object = $this->application->objects->factory($class, $this->application, [
			'a' => 123,
		]);
		$this->assertEquals(123, $object->a);
		$this->assertEquals(null, $object->B);
		$this->assertEquals(null, $object->A);
	}

	public function test_find_directory(): void {
		$this->assertEquals(['/etc/apt'], Directory::findAll(['/', '/etc'], 'apt'));
	}

	public function test_find_file(): void {
		$this->assertEquals('/etc/group', File::findFirst(['/', '/etc'], 'group'));
	}

	public function test_get(): void {
		$configuration = new Configuration();
		$configuration->a = 'b';
		$result = $configuration->toArray();
		$this->assertEquals('b', $result['a']);
	}

	/**
	 *
	 */
	public static function data_has(): array {
		return [
			[true, Application::class], [true, [Application::class, 'modules']], [true, Kernel::class], [
				true, [Application::class, Application::OPTION_HOME_PATH],
			], [true, Options::class], [false, md5('HOME')], [true, 'HOME'], [false, 'HoMe'], [false, '0192830128301283123'],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_has
	 */
	public function test_has($expected, $key): void {
		$this->assertEquals($expected, $this->application->configuration->pathExists($key), implode('::', toList($key)));
	}

	/**
	 * @return array[]
	 */
	public static function data_hasHook(): array {
		return [
			[true, Hooks::HOOK_EXIT], [true, Hooks::HOOK_CONFIGURED], [false, 'HOME'], [false, md5('HOME')],
			[false, '0192830128301283123'],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_hasHook
	 */
	public function test_hasHook($expected, $hook): void {
		$this->assertEquals($expected, $this->application->hooks->has($hook));
	}

	/**
	 * @return void
	 */
	public function test_hook(): void {
		$this->_addHook();
		$this->assertFalse($this->hook_was_called());
		$this->application->hooks->call('null', 2);
		$this->assertTrue($this->hook_was_called());
	}

	public function test_hook_array(): void {
		$hook = 'random';
		$arguments = [];
		$default = null;
		$this->application->hooks->callArguments($hook, $arguments, $default);
	}

	public function test_load_globals(): void {
		$paths = [
			$this->test_sandbox(),
		];
		$file = $this->test_sandbox('test.conf');
		$this->application->configuration->DUDE = 'smooth';
		file_put_contents($file, "TEST_VAR=\"\$DUDE move\"\nVAR_2=\"Ha ha! \${TEST_VAR} ex-lax\"\nVAR_3=\"\${DUDE:-default value}\"\nVAR_4=\"\${DOOD:-default value}\"");
		$overwrite = false;
		$this->application->loader->loadFile($file);

		$globals = [
			'TEST_VAR' => 'smooth move', 'VAR_2' => 'Ha ha! smooth move ex-lax', 'VAR_3' => 'smooth',
			'VAR_4' => 'default value',
		];

		foreach ($globals as $v => $result) {
			$g = $this->application->configuration->$v;
			$this->assertEquals($g, $result);
		}
	}

	public function test_maintenance(): void {
		$this->application->maintenance();
	}

	public function test_module_path(): void {
		$this->application->addModulePath($this->test_sandbox());
	}

	/**
	 * @param $expected
	 * @param $input
	 * @param array $map
	 * @param bool $insensitive
	 * @return void
	 */
	public function test_mapDefaults(): void {
		// Require defaults to work
		$this->assertEquals('brackets', map('{what}', ['what' => 'brackets']));
		$this->assertEquals('{what}', map('{what}', ['What' => 'brackets']));
		$this->assertEquals('bracketsNoCase', map('{what}', ['What' => 'bracketsNoCase'], true));
	}

	public static function data_map(): array {
		return [
			['ala{B}{c}{C}[D][d]', '{a}{B}{c}{C}[D][d]', ['a' => 'ala'], false, '{', '}'],
			['ala{b}{c}{c}[D][d]', '{a}{B}{c}{C}[D][d]', ['a' => 'ala'], true, '{', '}'],
			['ala[B]{c}{C}[D][d]', '[a][B]{c}{C}[D][d]', ['a' => 'ala'], false, '[', ']'],
			['ala[b]{c}{C}[d][d]', '[a][B]{c}{C}[D][d]', ['a' => 'ala'], true, '[', ']'],
		];
	}

	/**
	 * @param string|array $expected
	 * @param string|array $test
	 * @param array $map
	 * @param bool $case
	 * @param string $prefix
	 * @param string $suffix
	 * @return void
	 * @dataProvider data_map
	 */
	public function test_map(string|array $expected, string|array $test, array $map, bool $case, string $prefix, string $suffix): void {
		$this->assertEquals($expected, map($test, $map, $case, $prefix, $suffix));
	}

	/**
	 * @param string $expected
	 * @param string $test
	 * @param string $prefix
	 * @param string $suffix
	 * @return void
	 * @dataProvider data_mapClean
	 */
	public function test_mapClean(string $expected, string $test, string $prefix, string $suffix): void {
		$this->assertTrue(mapHasTokens($test));
		$this->assertEquals($expected, mapClean($test, $prefix, $suffix));
	}

	public static function data_mapClean(): array {
		return [
			['He wanted  [days]', 'He wanted {n} [days]', '{', '}', ],
			['He wanted {n} ', 'He wanted {n} [days]', '[', ']', ],
			['He wanted {n} [days]', 'He wanted {n} [days]', '[', '}', ],
			['He wanted ', 'He wanted {n} [days]', '{', ']', ],
			['except}', '{}{}{}{}{}{all}{of}{this}{is}{removed}except}{}', '{', '}', ],
		];
	}

	public function test_path_from_array(): void {
		$separator = '^';
		$mixed = [
			'^^one', '^two^', 'three^',
		];
		$result = path_from_array($separator, $mixed);
		$this->assertEquals('^one^two^three^', $result);
	}

	public function test_set(): void {
		$this->assertEquals(null, $this->application->configuration->getPath('a::b::c'));
		$this->application->configuration->setPath('a::b::c', 9876);
		$this->assertEquals(9876, $this->application->configuration->getPath('a::b::c'));
	}

	public function test_sharePath(): void {
		$this->application->addSharePath($this->test_sandbox(), 'dude');
	}

	public function test_sharePath_not(): void {
		$this->expectException(Exception_Directory_NotFound::class);
		$this->application->addSharePath(path($this->test_sandbox(), 'not-a-dir'), 'dude');
	}

	public function test_site_root(): void {
		$this->application->documentRoot($this->test_sandbox());
	}

	public function test_version(): void {
		$version = Version::release();
		$this->assertIsString($version);
	}

	public function test_documentRoot(): void {
		$this->application->documentRoot();
	}

	public static function data_document_root_prefix(): array {
		return [
			['/foobar', '/foobar'], ['foobar', 'foobar'], ['', ''], ['antidis', 'antidis/'], ['antidis', 'antidis////'],
		];
	}

	/**
	 * @param $set
	 * @return void
	 * @dataProvider data_document_root_prefix
	 */
	public function test_document_root_prefix($expected, $set): void {
		$this->application->setDocumentRootPrefix($set);
		$this->assertEquals($expected, $this->application->documentRootPrefix());
	}

	public function test_which(): void {
		$command = 'ls';
		$this->assertFileExists($this->application->paths->which($command));
	}

	public function test_which_fail(): void {
		$command = 'ls';
		$this->expectException(Exception_NotFound::class);
		$this->application->paths->which('notacommandtofind');
	}

	public function test_kernel_functions(): void {
		$start = microtime(true);
		$app = $this->application;
		$this->assertStringContainsString('Market Acumen', $app->copyrightHolder());

		$this->assertTrue($app->console());
		$app->setConsole(false);
		$this->assertFalse($app->console());
		$app->setConsole(true);

		$ff = $this->test_sandbox('inc.php');
		file_put_contents($ff, '<?' . "php\nreturn true;");
		$this->assertTrue($app->load($ff));

		/* Coverage */
		$configuration = $app->configuration;
		$configuration->setPath([Logger::class, 'utc_time'], true);
		foreach (['active', 'warning', 'bail'] as $assertSetting) {
			$configuration->setPath([$app::class, Application::OPTION_ASSERT], $assertSetting);
			$app->setConfiguration()->configure();
		}
		foreach ([
			Application::DEPRECATED_BACKTRACE, Application::DEPRECATED_IGNORE, Application::DEPRECATED_LOG,
		] as $deprecatedSetting) {
			$configuration->setPath([$app::class, 'deprecated'], $deprecatedSetting);
			$app->setConfiguration()->configure();
		}
		$configuration->setPath([$app::class, Application::OPTION_DEPRECATED], Application::DEPRECATED_LOG);
		$app->setConfiguration()->configure();
		// Should log (no Exception)
		$app->deprecated('Adios');

		// Back to normal
		$configuration->setPath([$app::class, Application::OPTION_DEPRECATED], Application::DEPRECATED_EXCEPTION);
		$app->setConfiguration()->configure();


		$configuration->setPath([$app::class, Application::OPTION_ASSERT], 'badsetting');

		$app->profileTimer(__METHOD__, microtime(true) - $start);
	}

	public function test_kernel_deprecated(): void {
		$kernel = Kernel::singleton();
	}
}

/**
 *
 * @author kent
 *
 */
class A extends Hookable {
}

/**
 *
 * @author kent
 *
 */
class B extends A {
}

/**
 *
 * @author kent
 *
 */
class C extends B {
}
