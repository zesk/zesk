<?php
/**
 * @package zesk
 * @subpackage test
 * @author kent
 * @copyright &copy; 2023, Market Acumen, Inc.
 */
declare(strict_types=1);

namespace zesk;

use zesk\Application\Hooks;
use zesk\Exception\Deprecated;
use zesk\Exception\NotFoundException;
use zesk\PHPUnit\TestCase;

class SingletonSampler
{
	public static string $serialNo = '';

	public string $id;

	public function __construct(string $id)
	{
		$this->id = $id;
	}

	public static function singleton(): self
	{
		return new self(self::$serialNo);
	}
}

class KernelTest extends TestCase
{
	/**
	 * @var bool
	 */
	protected bool $configureApplication = true;

	/**
	 * @var int
	 */
	public int $order = 0;

	/**
	 * @var bool
	 */
	public bool $_hook_was_called = false;

	public static function _test_hook_order_1st(KernelTest $test): void
	{
		$test->assertEquals(0, $test->order);
		$test->order++;
	}

	public static function _test_hook_order_2nd(KernelTest $test): void
	{
		$test->assertEquals(1, $test->order);
		$test->order++;
	}

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

	public function test_setk(): void
	{
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

	public function test_class_hierarchy(): void
	{
		$app = $this->application;

		$nsprefix = __NAMESPACE__ . '\\';
		$this->assertEquals($app->classes->hierarchy(__NAMESPACE__ . '\\A'), ArrayTools::prefixValues(Types::toList('A;Hookable;Options'), $nsprefix));
		$this->assertEquals($app->classes->hierarchy(__NAMESPACE__ . '\\B'), ArrayTools::prefixValues(Types::toList('B;A;Hookable;Options'), $nsprefix));
		$this->assertEquals($app->classes->hierarchy(__NAMESPACE__ . '\\C'), ArrayTools::prefixValues(Types::toList('C;B;A;Hookable;Options'), $nsprefix));
		$this->assertEquals($app->classes->hierarchy(__NAMESPACE__ . '\\' . 'HTML'), Types::toList(__NAMESPACE__ . '\\' . 'HTML'));
		$this->assertEquals($app->classes->hierarchy(new A($this->application)), ArrayTools::prefixValues(Types::toList('A;Hookable;Options'), __NAMESPACE__ . '\\'));
	}

	public function add_hook_was_called($arg): void
	{
		$this->assertEquals(2, $arg);
		$this->assertInstanceOf(Application::class, $this->application);
		$this->application->setOption('hook_was_called', true);
	}

	public function hook_was_called(): bool
	{
		return $this->application->optionBool('hook_was_called');
	}

	public function test_application_class(): void
	{
		$this->assertIsString($this->application->applicationClass());
		$this->assertTrue(class_exists($this->application->applicationClass()));
		$this->assertInstanceOf($this->application->applicationClass(), $this->application);
	}

	public function test_autoload_extension(): void
	{
		$this->application->autoloader->addExtension('dude');
	}

	public function test_autoload_path(): void
	{
		$testDir = Directory::depend($this->test_sandbox('lower-prefix'));

		$this->application->autoloader->addPath($testDir, [
			Autoloader::OPTION_LOWER => true, Autoloader::OPTION_CLASS_PREFIX => 'zesk\\AutoloaderTest\\',
			Autoloader::OPTION_EXTENSIONS => ['txt'],
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
	public function test_autoloadSearch(null|string $expected, string $class, array $extensions, array $modules): void
	{
		if (count($modules)) {
			$this->application->modules->loadMultiple($modules);
		}
		$autoloader = $this->application->autoloader;
		$tried_path = [];
		$this->assertEquals($expected, $autoloader->search($class, $extensions, $tried_path));
	}

	public static function data_autoloadSearch(): array
	{
		return [
			//			[ZESK_ROOT . 'zesk/Kernel.php', Kernel::class, ['php'], []],
			//			[ZESK_ROOT . 'zesk/Controller/Theme.php', ThemeController::class, ['php', 'sql'], []],
			[
				ZESK_ROOT . 'modules/Doctrine/zesk/Doctrine/User.php', 'zesk\\Doctrine\\User', ['php', 'sql'],
				['Doctrine'],
			], [null, 'zesk\\Doctrine\\User', ['other', 'none', ], ['Doctrine']],
		];
	}

	public static function data_provider_clean_function(): array
	{
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
	 * @dataProvider data_provider_clean_function
	 *
	 * @param string $name
	 * @param string $expected
	 */
	public function test_clean_function(string $name, string $expected): void
	{
		$result = PHP::cleanFunction($name);
		$this->assertEquals($result, $expected, 'PHP::cleanFunction');
	}

	public static function data_provider_clean_class(): array
	{
		return [
			[
				'', '',
			], [
				'  z e s k \\-O~b@j%e^c t!@#$%', '__z_e_s_k_\\_O_b_j_e_c_t_____',
			],
		];
	}

	/**
	 * @dataProvider data_provider_clean_class
	 *
	 * @param string $name
	 * @param string $expected
	 */
	public function test_clean_class(string $name, string $expected): void
	{
		$result = PHP::cleanClass($name);
		$this->assertEquals($result, $expected, "PHP::clean_class($name) = $result !== $expected");
	}

	public function test_command_path(): void
	{
		$this->application->addCommandPath($this->test_sandbox());
		$bin = Directory::path($this->test_sandbox('mstest'));
		File::put($bin, "#!/bin/bash\necho file1; echo file2;");
		File::chmod($bin, 493 /* 0o755 */);
		$ls = $this->application->paths->which('mstest');
		$this->assertEquals($bin, $ls);
	}

	public function test_console(): void
	{
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
	public function test_deprecated(): void
	{
		$this->expectException(Deprecated::class);
		$this->application->deprecated();
	}

	/**
	 *
	 */
	public function test_disable_deprecated(): void
	{
		$old = $this->application->setDeprecated(Application::DEPRECATED_IGNORE);
		$this->application->deprecated();
		$this->application->setDeprecated($old);
	}

	public function test_development(): void
	{
		$app = $this->application;
		$old_value = $app->development();
		$app->setDevelopment(true);
		$this->assertTrue($app->development());
		$app->setDevelopment(false);
		$this->assertFalse($app->development());
		$app->setDevelopment($old_value);
	}

	public function test_factory(): void
	{
		$class = __NAMESPACE__ . '\\Model';
		$init = [
			'a' => 123,
		];
		$object = $this->application->objects->factory($class, $this->application, $init);
		/* @var $object Model */
		$this->assertInstanceOf(Model::class, $object);

		// 2023 Model no longer has initializer
		$this->assertEquals(['application'], array_keys(get_object_vars($object)));
	}

	public function test_find_directory(): void
	{
		$this->assertEquals(['/etc/apt'], Directory::findAll(['/', '/etc'], 'apt'));
	}

	public function test_find_file(): void
	{
		$this->assertEquals('/etc/group', File::findFirst(['/', '/etc'], 'group'));
	}

	public function test_get(): void
	{
		$configuration = new Configuration();
		$configuration->a = 'b';
		$result = $configuration->toArray();
		$this->assertEquals('b', $result['a']);
	}

	/**
	 *
	 */
	public static function data_has(): array
	{
		return [
			[true, Application::class], [true, [Application::class, 'deprecated']], [true, Kernel::class],
			[true, [Application::class, Application::OPTION_HOME_PATH], ], [true, Options::class], [false, md5('HOME')],
			[true, 'HOME'], [false, 'HoMe'], [false, '0192830128301283123'],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_has
	 */
	public function test_has($expected, $key): void
	{
		$this->assertEquals($expected, $this->application->configuration->pathExists($key), implode('::', Types::toList($key)));
	}

	public function test_load_globals(): void
	{
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

	public function test_maintenance(): void
	{
		$this->application->maintenance();
	}

	public function test_module_path(): void
	{
		$this->application->addModulePath($this->test_sandbox());
	}

	public function test_set(): void
	{
		$this->assertEquals(null, $this->application->configuration->getPath('a::b::c'));
		$this->application->configuration->setPath('a::b::c', 9876);
		$this->assertEquals(9876, $this->application->configuration->getPath('a::b::c'));
	}

	public function test_site_root(): void
	{
		$this->application->documentRoot($this->test_sandbox());
	}

	public function test_version(): void
	{
		$version = Version::release();
		$this->assertIsString($version);
	}

	public function test_documentRoot(): void
	{
		$this->application->documentRoot();
	}

	public static function data_document_root_prefix(): array
	{
		return [
			['/foobar', '/foobar'], ['foobar', 'foobar'], ['', ''], ['antidis', 'antidis/'], ['antidis', 'antidis////'],
		];
	}

	/**
	 * @param $set
	 * @return void
	 * @dataProvider data_document_root_prefix
	 */
	public function test_document_root_prefix($expected, $set): void
	{
		$this->application->setDocumentRootPrefix($set);
		$this->assertEquals($expected, $this->application->documentRootPrefix());
	}

	public function test_which(): void
	{
		$command = 'ls';
		$this->assertFileExists($this->application->paths->which($command));
	}

	public function test_which_fail(): void
	{
		$command = 'ls';
		$this->expectException(NotFoundException::class);
		$this->application->paths->which('notacommandtofind');
	}

	public function test_kernel_functions(): void
	{
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

	public function test_kernel_singleton(): void
	{
		$kernel = Kernel::singleton();
		$this->assertInstanceOf(Kernel::class, $kernel);
	}

	public function test_calling(): void
	{
		$this->assertEquals(__FILE__ . ' ' . __CLASS__ . '->test_calling:' . __LINE__, Kernel::callingFunction(0));
		$this->assertEquals([
			'file' => __FILE__, 'type' => '->', 'function' => __FUNCTION__, 'args' => [],
			'method' => __CLASS__ . '->' . __FUNCTION__, 'class' => __CLASS__, 'object' => $this,
			'fileMethod' => __FILE__ . ' ' . __CLASS__ . '->' . __FUNCTION__, 'lineSuffix' => ':' . (__LINE__ + 4),
			'methodLine' => __CLASS__ . '->' . __FUNCTION__ . ':' . (__LINE__ + 3),
			'fileMethodLine' => __FILE__ . ' ' . __CLASS__ . '->' . __FUNCTION__ . ':' . (__LINE__ + 2),
			'line' => __LINE__ + 1,
		], ArrayTools::filterKeys(Kernel::caller(0), null, ['callingLine', 'callingFile']));

		for ($i = 0; $i < 4; $i++) {
			$this->assertEquals(Kernel::callingFunction($i, true), Kernel::caller($i)['fileMethodLine']);
			$this->assertEquals(Kernel::callingFunction($i, false), Kernel::caller($i)['fileMethod']);
		}
	}

	public function test_calling_neg(): void
	{
		$this->assertEquals($this->application->zeskHome('src/zesk/Kernel.php') . ' zesk\Kernel::callingFunction', Kernel::callingFunction(-1, false));
	}
}

/**
 *
 * @author kent
 *
 */
class A extends Hookable
{
}

/**
 *
 * @author kent
 *
 */
class B extends A
{
}

/**
 *
 * @author kent
 *
 */
class C extends B
{
}
