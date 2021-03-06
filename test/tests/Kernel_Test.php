<?php
namespace zesk;

class Kernel_Test extends Test_Unit {
	public $order = 0;

	public static function _test_hook_order_1st(Kernel_Test $test) {
		$test->assert_equal($test->order, 0);
		$test->order++;
	}

	public static function _test_hook_order_2nd(Kernel_Test $test) {
		$test->assert_equal($test->order, 1);
		$test->order++;
	}

	public function test_hook_order() {
		$hooks = $this->application->hooks;
		// Nothing registered
		$this->order = 0;
		$hooks->call("test_hook_order", $this);
		$this->assert_equal($this->order, 0);

		// Add hooks
		$hooks->add("test_hook_order", __CLASS__ . "::_test_hook_order_1st");
		$hooks->add("test_hook_order", __CLASS__ . "::_test_hook_order_2nd");

		// Test ordering
		$this->order = 0;
		$hooks->call("test_hook_order", $this);
		$this->assert_equal($this->order, 2);

		// Test clearing
		$hooks->remove("test_hook_order");

		$this->order = 0;
		$hooks->call("test_hook_order", $this);
		$this->assert_equal($this->order, 0);

		// Test "first"
		$hooks->add("test_hook_order", __CLASS__ . "::_test_hook_order_2nd");
		$hooks->add("test_hook_order", __CLASS__ . "::_test_hook_order_1st", array(
			"first" => true,
		));

		// Test ordering
		$this->order = 0;
		$hooks->call("test_hook_order", $this);
		$this->assert_equal($this->order, 2);
	}

	public function test_setk() {
		$k = "a";
		$k1 = "b";
		$v = md5(microtime());
		$this->application->configuration->path_set(array(
			$k,
			"b",
		), $v);
		$this->application->configuration->path_set(array(
			$k,
			"c",
		), $v);
		$this->application->configuration->path_set(array(
			$k,
			"d",
		), $v);

		$this->assert_arrays_equal($this->application->configuration->path_get($k), array(
			"b" => $v,
			"c" => $v,
			"d" => $v,
		), "path_set/path_get", true, true);
	}

	public function test_class_hierarchy() {
		$app = $this->application;

		$mixed = null;
		$nsprefix = __NAMESPACE__ . "\\";
		$this->assert_arrays_equal($app->classes->hierarchy(__NAMESPACE__ . "\\A"), ArrayTools::prefix(to_list('A;Hookable;Options'), $nsprefix));
		$this->assert_arrays_equal($app->classes->hierarchy(__NAMESPACE__ . "\\B"), ArrayTools::prefix(to_list('B;A;Hookable;Options'), $nsprefix));
		$this->assert_arrays_equal($app->classes->hierarchy(__NAMESPACE__ . "\\C"), ArrayTools::prefix(to_list('C;B;A;Hookable;Options'), $nsprefix));
		$this->assert_arrays_equal($app->classes->hierarchy(__NAMESPACE__ . "\\" . "HTML"), to_list(__NAMESPACE__ . "\\" . "HTML"));
		$this->assert_arrays_equal($app->classes->hierarchy(new A($this->application)), ArrayTools::prefix(to_list('A;Hookable;Options'), __NAMESPACE__ . "\\"));
	}

	public function test_add_hook() {
		$hook = null;
		$function = null;
		$args = null;
		$this->application->hooks->add($hook, $function, $args);
	}

	public function test_application_class() {
		$this->assert_is_string($this->application->application_class());
		$this->assert_class_exists($this->application->application_class());
		$this->assert_instanceof($this->application, $this->application->application_class());
	}

	public function test_autoload_extension() {
		$this->application->autoloader->extension("dude");
	}

	public function test_autoload_path() {
		$add = null;
		$lower_class = true;
		$this->application->autoloader->path($this->test_sandbox("lower-prefix"), array(
			"lower" => true,
			"class_prefix" => "zesk\\Autoloader",
		));
	}

	public function test_autoload_search() {
		$autoloader = $this->application->autoloader;
		$class = "zesk\\Kernel";
		$extension = "php";
		$tried_path = null;
		$result = $autoloader->search($class, array(
			$extension,
		), $tried_path);
		$this->assert_equal($result, ZESK_ROOT . 'classes/Kernel.php');

		$class = "zesk\\Controller_Theme";

		$result = $autoloader->search($class, array(
			$extension,
			"sql",
		), $tried_path);
		$this->assert_equal($result, ZESK_ROOT . 'classes/Controller/Theme.php');

		$class = "zesk\\Class_User";
		$this->application->modules->load("orm");
		$result = $autoloader->search($class, array(
			"sql",
			"php",
		), $tried_path);
		$this->assert_equal($result, $this->application->modules->path("orm", 'classes/Class/User.sql'));

		$result = $autoloader->search($class, array(
			"other",
			"inc",
			"sql",
		), $tried_path);
		$this->assert_equal($result, $this->application->modules->path("orm", 'classes/Class/User.sql'));

		$result = $autoloader->search($class, array(
			"other",
			"none",
		), $tried_path);
		$this->assert_null($result);
	}

	public function provider_clean_function() {
		return array(
			array(
				"",
				"",
			),
			array(
				"  z e s k \\-O~b@j%e^c t!@#$%",
				"__z_e_s_k___O_b_j_e_c_t_____",
			),
			array(
				"bunch,of-random-chars",
				"bunch_of_random_chars",
			),
		);
	}

	/**
	 * @dataProvider provider_clean_function
	 *
	 * @param string $name
	 * @param string $expected
	 */
	public function test_clean_function($name, $expected) {
		$result = PHP::clean_function($name);
		$this->assert_equal($result, $expected, "PHP::clean_function");
	}

	public function provider_clean_class() {
		return array(
			array(
				"",
				"",
			),
			array(
				"  z e s k \\-O~b@j%e^c t!@#$%",
				"__z_e_s_k_\\_O_b_j_e_c_t_____",
			),
		);
	}

	/**
	 * @dataProvider provider_clean_class
	 *
	 * @param string $name
	 * @param string $expected
	 */
	public function test_clean_class($name, $expected) {
		$result = PHP::clean_class($name);
		$this->assert_equal($result, $expected, "PHP::clean_class($name) = $result !== $expected");
	}

	public function test_command_path() {
		$add = null;
		$this->application->command_path($this->test_sandbox());
		$bin = path($this->test_sandbox("mstest"));
		File::put($bin, "#!/bin/bash\necho file1; echo file2;");
		File::chmod($bin, 0755);
		$ls = $this->application->paths->which("mstest");
		$this->assert_equal($ls, $bin);
		$this->assertEquals($bin, $ls);
	}

	public function test_console() {
		$set = null;
		$this->application->console($set);
	}

	public function test_deprecated() {
		$set = null;
		$this->application->deprecated();
	}

	public function test_development() {
		$app = $this->application;
		$old_value = $app->development();
		$app->development(true);
		$this->assert_true($app->development());
		$app->development(false);
		$this->assert_false($app->development());
		$app->development($old_value);
	}

	public function test_factory() {
		$class = __NAMESPACE__ . "\\Model";
		$object = $this->application->objects->factory($class, $this->application, array(
			"a" => 123,
		));
		$this->assert_equal($object->a, 123);
		$this->assert_equal($object->B, null);
		$this->assert_equal($object->A, null);
	}

	public function test_find_directory() {
		$paths = array();
		$directory = null;
		Directory::find_all($paths, $directory);
	}

	public function test_find_file() {
		$paths = array();
		$file = null;
		File::find_first($paths, $file);
	}

	public function test_get() {
		$configuration = new Configuration();
		$configuration->a = "b";
		$result = $configuration->to_array();
		$this->assert($result['a'] === 'b');
	}

	public function test_has() {
		$key = null;
		$check_empty = true;
		$this->application->configuration->has($key);
	}

	public function test_has_hook() {
		$hook = null;
		$this->application->hooks->has($hook);
	}

	public function test_hook() {
		$hook = null;
		$this->application->hooks->call($hook);
	}

	public function test_hook_array() {
		$hook = "random";
		$arguments = array();
		$default = null;
		$this->application->hooks->call_arguments($hook, $arguments, $default);
	}

	public function test_load_globals() {
		$paths = array(
			$this->test_sandbox(),
		);
		$file = $this->test_sandbox("test.conf");
		$this->application->configuration->DUDE = "smooth";
		file_put_contents($file, "TEST_VAR=\"\$DUDE move\"\nVAR_2=\"Ha ha! \${TEST_VAR} ex-lax\"\nVAR_3=\"\${DUDE:-default value}\"\nVAR_4=\"\${DOOD:-default value}\"");
		$overwrite = false;
		$this->application->loader->load_one($file);

		$globals = array(
			'TEST_VAR' => "smooth move",
			'VAR_2' => "Ha ha! smooth move ex-lax",
			'VAR_3' => "smooth",
			'VAR_4' => "default value",
		);

		foreach ($globals as $v => $result) {
			$g = $this->application->configuration->$v;
			$this->assert_equal($g, $result, "$v: $g === $result");
		}
	}

	public function test_maintenance() {
		$this->application->maintenance();
	}

	public function test_module_path() {
		$this->application->module_path($this->test_sandbox());
	}

	public function test_path_from_array() {
		$separator = '^';
		$mixed = array(
			'^^one',
			'^two^',
			'three^',
		);
		$result = path_from_array($separator, $mixed);
		$this->assert($result === "^one^two^three^");
	}

	public function test_pid() {
		$this->application->process->id();
	}

	public function test_running() {
		$process = $this->application->process;
		$pid = $this->application->process->id();
		$this->assert_is_integer($pid);
		$this->assert_true($process->alive($pid));
		$this->assert_false($process->alive(32766));
	}

	public function test_set() {
		$this->assert_equal($this->application->configuration->path_get("a::b::c"), null);
		$this->application->configuration->path_set("a::b::c", 9876);
		$this->assert_equal($this->application->configuration->path_get("a::b::c"), 9876);
	}

	public function test_share_path() {
		$this->application->share_path($this->test_sandbox());
	}

	public function test_site_root() {
		$this->application->document_root($this->test_sandbox());
	}

	public function test_theme_path() {
		$add = null;
		$this->application->theme_path($add);
	}

	/**
	 */
	public function test_theme_fail() {
		$type = null;
		$this->assert_null($this->application->theme($type));
	}

	/**
	 */
	public function test_theme() {
		$type = "dude";
		$this->application->theme($type, array(
			"hello" => 1,
		));
	}

	public function test_version() {
		$version = Version::release();
		$this->assert_is_string($version);
	}

	public function test_document_root() {
		$set = null;
		$this->application->document_root($set);
	}

	public function test_document_root_prefix() {
		$set = null;
		$this->application->document_root_prefix($set);
		echo basename(__FILE__) . ": success\n";
	}

	public function test_which() {
		$command = "ls";
		$this->application->paths->which($command);
	}

	public function test_zesk_command_path() {
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
