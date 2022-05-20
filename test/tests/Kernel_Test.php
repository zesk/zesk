<?php
declare(strict_types=1);

namespace zesk;

class Kernel_Test extends Test_Unit {
	/**
	 * @var int
	 */
	public int $order = 0;

	/**
	 * @var bool
	 */
	public bool $_hook_was_called = false;

	public static function _test_hook_order_1st(Kernel_Test $test): void {
		$test->assert_equal($test->order, 0);
		$test->order++;
	}

	public static function _test_hook_order_2nd(Kernel_Test $test): void {
		$test->assert_equal($test->order, 1);
		$test->order++;
	}

	public function test_hook_order(): void {
		$hooks = $this->application->hooks;
		// Nothing registered
		$this->order = 0;
		$hooks->call('test_hook_order', $this);
		$this->assert_equal($this->order, 0);

		// Add hooks
		$hooks->add('test_hook_order', __CLASS__ . '::_test_hook_order_1st');
		$hooks->add('test_hook_order', __CLASS__ . '::_test_hook_order_2nd');

		// Test ordering
		$this->order = 0;
		$hooks->call('test_hook_order', $this);
		$this->assert_equal($this->order, 2);

		// Test clearing
		$hooks->remove('test_hook_order');

		$this->order = 0;
		$hooks->call('test_hook_order', $this);
		$this->assert_equal($this->order, 0);

		// Test "first"
		$hooks->add('test_hook_order', __CLASS__ . '::_test_hook_order_2nd');
		$hooks->add('test_hook_order', __CLASS__ . '::_test_hook_order_1st', [
			'first' => true,
		]);

		// Test ordering
		$this->order = 0;
		$hooks->call('test_hook_order', $this);
		$this->assert_equal($this->order, 2);
	}

	public function test_setk(): void {
		$k = 'a';
		$k1 = 'b';
		$v = md5(microtime());
		$this->application->configuration->path_set([
			$k,
			'b',
		], $v);
		$this->application->configuration->path_set([
			$k,
			'c',
		], $v);
		$this->application->configuration->path_set([
			$k,
			'd',
		], $v);

		$this->assert_arrays_equal($this->application->configuration->path_get($k), [
			'b' => $v,
			'c' => $v,
			'd' => $v,
		], 'path_set/path_get', true, true);
	}

	public function test_class_hierarchy(): void {
		$app = $this->application;

		$mixed = null;
		$nsprefix = __NAMESPACE__ . '\\';
		$this->assert_arrays_equal($app->classes->hierarchy(__NAMESPACE__ . '\\A'), ArrayTools::prefix(to_list('A;Hookable;Options'), $nsprefix));
		$this->assert_arrays_equal($app->classes->hierarchy(__NAMESPACE__ . '\\B'), ArrayTools::prefix(to_list('B;A;Hookable;Options'), $nsprefix));
		$this->assert_arrays_equal($app->classes->hierarchy(__NAMESPACE__ . '\\C'), ArrayTools::prefix(to_list('C;B;A;Hookable;Options'), $nsprefix));
		$this->assert_arrays_equal($app->classes->hierarchy(__NAMESPACE__ . '\\' . 'HTML'), to_list(__NAMESPACE__ . '\\' . 'HTML'));
		$this->assert_arrays_equal($app->classes->hierarchy(new A($this->application)), ArrayTools::prefix(to_list('A;Hookable;Options'), __NAMESPACE__ . '\\'));
	}

	public function add_hook_was_called($arg): void {
		$this->assertEquals(2, $arg);
		$this->_hook_was_called = true;
	}

	public function test_add_hook(): void {
		$hook = 'null';
		$function = function ($arg): void {
			$this->add_hook_was_called($arg);
		};
		$this->application->hooks->add($hook, $function);
	}

	public function test_application_class(): void {
		$this->assert_is_string($this->application->application_class());
		$this->assert_class_exists($this->application->application_class());
		$this->assert_instanceof($this->application, $this->application->application_class());
	}

	public function test_autoload_extension(): void {
		$this->application->autoloader->extension('dude');
	}

	public function test_autoload_path(): void {
		$add = null;
		$lower_class = true;
		$this->application->autoloader->path($this->test_sandbox('lower-prefix'), [
			'lower' => true,
			'class_prefix' => 'zesk\\Autoloader',
		]);
	}

	public function test_autoload_search(): void {
		$autoloader = $this->application->autoloader;
		$class = 'zesk\\Kernel';
		$extension = 'php';
		$tried_path = null;
		$result = $autoloader->search($class, [
			$extension,
		], $tried_path);
		$this->assert_equal($result, ZESK_ROOT . 'classes/Kernel.php');

		$class = 'zesk\\Controller_Theme';

		$result = $autoloader->search($class, [
			$extension,
			'sql',
		], $tried_path);
		$this->assert_equal($result, ZESK_ROOT . 'classes/Controller/Theme.php');

		$class = 'zesk\\Class_User';
		$this->application->modules->load('orm');
		$result = $autoloader->search($class, [
			'sql',
			'php',
		], $tried_path);
		$this->assert_equal($result, $this->application->modules->path('orm', 'classes/Class/User.sql'));

		$result = $autoloader->search($class, [
			'other',
			'inc',
			'sql',
		], $tried_path);
		$this->assert_equal($result, $this->application->modules->path('orm', 'classes/Class/User.sql'));

		$result = $autoloader->search($class, [
			'other',
			'none',
		], $tried_path);
		$this->assert_null($result);
	}

	public function provider_clean_function() {
		return [
			[
				'',
				'',
			],
			[
				'  z e s k \\-O~b@j%e^c t!@#$%',
				'__z_e_s_k___O_b_j_e_c_t_____',
			],
			[
				'bunch,of-random-chars',
				'bunch_of_random_chars',
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
		$result = PHP::clean_function($name);
		$this->assert_equal($result, $expected, 'PHP::clean_function');
	}

	public function provider_clean_class() {
		return [
			[
				'',
				'',
			],
			[
				'  z e s k \\-O~b@j%e^c t!@#$%',
				'__z_e_s_k_\\_O_b_j_e_c_t_____',
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
		$result = PHP::clean_class($name);
		$this->assert_equal($result, $expected, "PHP::clean_class($name) = $result !== $expected");
	}

	public function test_command_path(): void {
		$add = null;
		$this->application->command_path($this->test_sandbox());
		$bin = path($this->test_sandbox('mstest'));
		File::put($bin, "#!/bin/bash\necho file1; echo file2;");
		File::chmod($bin, 0o755);
		$ls = $this->application->paths->which('mstest');
		$this->assert_equal($ls, $bin);
		$this->assertEquals($bin, $ls);
	}

	public function test_console(): void {
		$set = null;
		$this->application->console($set);
	}

	public function test_deprecated(): void {
		$set = null;
		$this->application->deprecated();
	}

	public function test_development(): void {
		$app = $this->application;
		$old_value = $app->development();
		$app->development(true);
		$this->assert_true($app->development());
		$app->development(false);
		$this->assert_false($app->development());
		$app->development($old_value);
	}

	public function test_factory(): void {
		$class = __NAMESPACE__ . '\\Model';
		$object = $this->application->objects->factory($class, $this->application, [
			'a' => 123,
		]);
		$this->assert_equal($object->a, 123);
		$this->assert_equal($object->B, null);
		$this->assert_equal($object->A, null);
	}

	public function test_find_directory(): void {
		$paths = [];
		$directory = null;
		Directory::find_all($paths, $directory);
	}

	public function test_find_file(): void {
		$paths = [];
		$file = null;
		File::find_first($paths, $file);
	}

	public function test_get(): void {
		$configuration = new Configuration();
		$configuration->a = 'b';
		$result = $configuration->to_array();
		$this->assert($result['a'] === 'b');
	}

	/**
	 *
	 */
	public function data_has(): array {
		return [
			[true, Application::class],
			[true, [Application::class, 'version']],
			[true, [Application::class, 'modules']],
			[true, [Application::class, 'modules']],
			[true, 'init'],
			[true, 'home'],
			[true, Options::class],
			[false, md5('HOME')],
			[false, 'HoMe'],
			[false, '0192830128301283123'],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_has
	 */
	public function test_has($expected, $key): void {
		$this->assertEquals($expected, $this->application->configuration->path_exists($key));
	}

	/**
	 * @return array[]
	 */
	public function data_has_hook(): array {
		return [
			[true, Hooks::HOOK_EXIT],
			[true, Hooks::HOOK_CONFIGURED],
			[false, 'HOME'],
			[false, md5('HOME')],
			[false, '0192830128301283123'],
		];
	}

	/**
	 * @return void
	 * @dataProvider data_has_hook
	 */
	public function test_has_hook($expected, $hook): void {
		$this->assertEquals($expected, $this->application->hooks->has($hook));
	}

	/**
	 * @return void
	 * @depends test_add_hook
	 */
	public function test_hook(): void {
		$this->assertFalse($this->_hook_was_called);
		$this->application->hooks->call('null', 2);
		$this->assertTrue($this->_hook_was_called);
	}

	public function test_hook_array(): void {
		$hook = 'random';
		$arguments = [];
		$default = null;
		$this->application->hooks->call_arguments($hook, $arguments, $default);
	}

	public function test_load_globals(): void {
		$paths = [
			$this->test_sandbox(),
		];
		$file = $this->test_sandbox('test.conf');
		$this->application->configuration->DUDE = 'smooth';
		file_put_contents($file, "TEST_VAR=\"\$DUDE move\"\nVAR_2=\"Ha ha! \${TEST_VAR} ex-lax\"\nVAR_3=\"\${DUDE:-default value}\"\nVAR_4=\"\${DOOD:-default value}\"");
		$overwrite = false;
		$this->application->loader->load_one($file);

		$globals = [
			'TEST_VAR' => 'smooth move',
			'VAR_2' => 'Ha ha! smooth move ex-lax',
			'VAR_3' => 'smooth',
			'VAR_4' => 'default value',
		];

		foreach ($globals as $v => $result) {
			$g = $this->application->configuration->$v;
			$this->assert_equal($g, $result, "$v: $g === $result");
		}
	}

	public function test_maintenance(): void {
		$this->application->maintenance();
	}

	public function test_module_path(): void {
		$this->application->module_path($this->test_sandbox());
	}

	public function test_path_from_array(): void {
		$separator = '^';
		$mixed = [
			'^^one',
			'^two^',
			'three^',
		];
		$result = path_from_array($separator, $mixed);
		$this->assert($result === '^one^two^three^');
	}

	public function test_pid(): void {
		$this->application->process->id();
	}

	public function test_running(): void {
		$process = $this->application->process;
		$pid = $this->application->process->id();
		$this->assert_is_integer($pid);
		$this->assert_true($process->alive($pid));
		$this->assert_false($process->alive(32766));
	}

	public function test_set(): void {
		$this->assert_equal($this->application->configuration->path_get('a::b::c'), null);
		$this->application->configuration->path_set('a::b::c', 9876);
		$this->assert_equal($this->application->configuration->path_get('a::b::c'), 9876);
	}

	public function test_share_path(): void {
		$this->application->share_path($this->test_sandbox());
	}

	public function test_site_root(): void {
		$this->application->document_root($this->test_sandbox());
	}

	public function test_theme_path(): void {
		$add = null;
		$this->application->theme_path($add);
	}

	/**
	 */
	public function test_theme_fail(): void {
		$type = null;
		$this->assert_null($this->application->theme($type));
	}

	/**
	 */
	public function test_theme(): void {
		$type = 'dude';
		$this->application->theme($type, [
			'hello' => 1,
		]);
	}

	public function test_version(): void {
		$version = Version::release();
		$this->assert_is_string($version);
	}

	public function test_document_root(): void {
		$set = null;
		$this->application->document_root($set);
	}

	public function test_document_root_prefix(): void {
		$set = null;
		$this->application->document_root_prefix($set);
		echo basename(__FILE__) . ": success\n";
	}

	public function test_which(): void {
		$command = 'ls';
		$this->application->paths->which($command);
	}

	public function test_zesk_command_path(): void {
		$add = null;
		$this->application->zesk_command_path($add);
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
