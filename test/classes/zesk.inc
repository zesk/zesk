<?php
class test_zesk extends Test_Unit {
	public $order = 0;
	static function _test_hook_order_1st(Test_zesk $test) {
		$test->assert_equal($test->order, 0);
		$test->order++;
	}
	static function _test_hook_order_2nd(Test_zesk $test) {
		$test->assert_equal($test->order, 1);
		$test->order++;
	}
	function test_hook_order() {
		// Nothing registered
		$this->order = 0;
		zesk()->hooks->call("test_hook_order", $this);
		$this->assert_equal($this->order, 0);
		
		// Add hooks
		zesk()->hooks->add("test_hook_order", "test_zesk::_test_hook_order_1st");
		zesk()->hooks->add("test_hook_order", "test_zesk::_test_hook_order_2nd");
		
		// Test ordering
		$this->order = 0;
		zesk()->hooks->call("test_hook_order", $this);
		$this->assert_equal($this->order, 2);
		
		// Test clearing
		zesk::clear_hook("test_hook_order");
		$this->order = 0;
		zesk()->hooks->call("test_hook_order", $this);
		$this->assert_equal($this->order, 0);
		
		// Test "first"
		zesk()->hooks->add("test_hook_order", "test_zesk::_test_hook_order_2nd");
		zesk()->hooks->add("test_hook_order", "test_zesk::_test_hook_order_1st", null, null, true);
		
		// Test ordering
		$this->order = 0;
		zesk()->hooks->call("test_hook_order", $this);
		$this->assert_equal($this->order, 2);
	}
	function test_setk() {
		$k = "a";
		$k1 = "b";
		$v = "c";
		zesk::setk($k, "b", $v);
		zesk::setk($k, "c", $v);
		zesk::setk($k, "d", $v);
		
		$this->assert_arrays_equal(zesk::get($k), array(
			"b" => "c",
			"c" => "c",
			"d" => "c"
		), "_Sk", true, true);
	}
	function test_class_hierarchy() {
		global $zesk;
		
		$mixed = null;
		$this->assert_arrays_equal($zesk->classes->hierarchy("A"), to_list('A;Hookable;Options'));
		$this->assert_arrays_equal($zesk->classes->hierarchy("B"), to_list('B;A;Hookable;Options'));
		$this->assert_arrays_equal($zesk->classes->hierarchy("C"), to_list('C;B;A;Hookable;Options'));
		$this->assert_arrays_equal($zesk->classes->hierarchy("zesk"), to_list('zesk'));
		$this->assert_arrays_equal($zesk->classes->hierarchy("http"), to_list('http'));
		$this->assert_arrays_equal($zesk->classes->hierarchy(new A()), to_list('A;Hookable;Options'));
	}
	function test_add() {
		$k = null;
		$v = null;
		zesk::add($k, $v);
		echo basename(__FILE__) . ": success\n";
	}
	function test_add_hook() {
		$hook = null;
		$function = null;
		$args = null;
		zesk()->hooks->add($hook, $function, $args);
		echo basename(__FILE__) . ": success\n";
	}
	function test_application_class() {
		$set = null;
		zesk::application_class($set);
		echo basename(__FILE__) . ": success\n";
	}
	function test_autoload_extension() {
		$add = null;
		zesk::autoload_extension($add);
		echo basename(__FILE__) . ": success\n";
	}
	function test_autoload_path() {
		$add = null;
		$lower_class = true;
		zesk::autoload_path($add, $lower_class);
		echo basename(__FILE__) . ": success\n";
	}
	function test_autoload_search() {
		$class = null;
		$extensions = null;
		$tried_path = null;
		$result = zesk::autoload_search("User", array(
			"inc"
		), $tried_path);
		$this->assert($result === ZESK_ROOT . 'classes/user.inc');
		
		$result = zesk::autoload_search("User", array(
			"inc",
			"sql"
		), $tried_path);
		$this->assert($result === ZESK_ROOT . 'classes/user.inc');
		
		$result = zesk::autoload_search("User", array(
			"sql"
		), $tried_path);
		$this->assert($result === ZESK_ROOT . 'classes/user.sql');
		
		$result = zesk::autoload_search("User", array(
			"other",
			"sql"
		), $tried_path);
		$this->assert($result === ZESK_ROOT . 'classes/user.sql');
		
		$result = zesk::autoload_search("User", array(
			"other",
			"none"
		), $tried_path);
		$this->assert($result === NULL);
	}
	function test_autoloader() {
		$class = null;
		zesk::autoloader("zesk");
	}
	function test_bootstrap() {
		$this->assert_false(zesk::bootstrap(), 'zesk::bootstrap()');
	}
	function test_clean_function() {
		$func = null;
		zesk::clean_function($func);
	}
	function test_clean_path() {
		$path = null;
		zesk::clean_path($path);
	}
	function test_command_path() {
		$add = null;
		zesk::command_path($add);
	}
	function test_configure() {
		$group = null;
		zesk::configure($group);
	}
	function test_console() {
		$set = null;
		zesk::console($set);
	}
	function test_debug_schema() {
		$set = null;
		zesk::debug_schema($set);
	}
	function test_defaults() {
		zesk::defaults();
	}
	function test_define_globals() {
		$keys_defaults = null;
		$defined_error = true;
		$hash = md5(microtime());
		$this->assert(!defined(__FUNCTION__));
		zesk::define_globals(array(
			__FUNCTION__ => $hash
		), $defined_error);
		$this->assert(defined(__FUNCTION__));
		
		$this->assert(!defined('constant_thing'));
		zesk::define_globals(array(
			'constant_thing' => $hash
		), $defined_error);
		$this->assert(defined('constant_thing'));
		$this->assert_equal(constant_thing, $hash);
	}
	function test_deprecated() {
		$set = null;
		zesk::deprecated($set);
	}
	function test_development() {
		$old_value = Application::instance()->development();
		Application::instance()->development(true);
		Application::instance()->development(false);
		Application::instance()->development($old_value);
	}
	function test_factory() {
		$class = "Model";
		$object = zesk::factory($class, array(
			"a" => 123
		));
		$this->assert_equal($object->a, 123);
		$this->assert_equal($object->B, null);
		$this->assert_equal($object->A, null);
	}
	function test_file_search() {
		$dir = dirname(__FILE__);
		
		$up_dir = dirname($dir);
		
		$paths = array(
			$dir => true
		);
		$file_prefix = "file-search";
		$extension = "txt";
		$extensions = array(
			$extension
		);
		$tried_path = null;
		$result = zesk::file_search($paths, $file_prefix, $extensions, $tried_path);
		
		$this->assert_equal($result, path($dir, $file_prefix . '.' . $extension));
		
		$result = zesk::file_search(array(
			$up_dir => true
		), "test_$file_prefix", $extensions, $tried_path);
		
		$this->assert_equal($result, path($dir, $file_prefix . '.' . $extension));
	}
	function test_find_directory() {
		$paths = array();
		$directory = null;
		zesk::find_directory($paths, $directory);
	}
	function test_find_file() {
		$paths = array();
		$file = null;
		zesk::find_file($paths, $file);
	}
	function test_get() {
		zesk::set("a", "b");
		$result = zesk::get();
		$this->assert($result['a'] === 'b');
	}
	function test_geta() {
		zesk::set("geta_num", 1);
		zesk::set("geta_string", "geta");
		zesk::set("geta_object", new stdClass());
		$key = null;
		$default = array();
		$this->assert_equal(zesk::geta($key, $default), $default);
		
		$this->assert_equal(zesk::geta($key, null), null);
		
		$this->assert_equal(zesk::geta("geta_num"), array(
			1
		));
		$this->assert_equal(zesk::geta("geta_string"), array(
			"geta"
		));
		$this->assert_equal(zesk::geta("geta_object"), array());
		$this->assert_equal(zesk::geta("geta_object", null), null);
	}
	function test_getb() {
		$key = null;
		$default = false;
		zesk::getb($key, $default);
	}
	function test_geti() {
		$key = null;
		$default = null;
		zesk::geti($key, $default);
	}
	function test_getl() {
		$key = null;
		$default = false;
		$delimiter = ';';
		zesk::getl($key, $default, $delimiter);
	}
	function test_load_globals_lines() {
		$lines = array(
			"A=1",
			"FOO=Tset",
			"Global::value='\$FOO'"
		);
		$overwrite = false;
		zesk::set(conf::parse($lines, $overwrite));
		$this->assert(zesk::get("A") == "1");
		$this->assert(zesk::get("Global::value") == '$FOO');
		$this->assert(zesk::get("Global__value") == '$FOO');
		$this->assert(zesk::get("Global--value") == '$FOO');
	}
	function test_has() {
		$key = null;
		$check_empty = true;
		zesk::has($key, $check_empty);
	}
	function test_has_hook() {
		$hook = null;
		zesk()->hooks->has($hook);
	}
	function test_hook() {
		$hook = null;
		zesk()->hooks->call($hook);
	}
	function test_hook_arguments() {
		$object = new zTestObject();
		$arguments = array(
			$object,
			"string",
			423,
			"Hike"
		);
		
		$arguments_definition = array();
		
		$arguments_definition = array(
			0,
			1,
			2,
			3
		);
		$this->assert_equal(zesk()->hooks->call_arguments($arguments, $arguments_definition), $arguments);
		
		$arguments_definition = array(
			"Foo",
			"Bar",
			2,
			3
		);
		$this->assert_equal(zesk()->hooks->call_arguments($arguments, $arguments_definition), array(
			"Foo" => $object,
			"Bar" => "string",
			2 => 423,
			3 => "Hike"
		));
		
		$arguments_definition = array(
			"Foo" => 1,
			"Bar" => 0,
			2,
			3
		);
		$result = zesk()->hooks->call_arguments($arguments, $arguments_definition);
		$this->assert_equal($result, array(
			"Foo" => "string",
			"Bar" => $object,
			423,
			"Hike"
		));
		
		$arguments_definition = array(
			3,
			2,
			1,
			3
		);
		$result = zesk()->hooks->call_arguments($arguments, $arguments_definition);
		$this->assert_equal($result, array(
			"Hike",
			423,
			"string",
			"Hike"
		));
		
		$arguments_definition = array(
			0,
			1,
			"test" => array(
				"one",
				"two",
				"three" => 3
			)
		);
		$result = zesk()->hooks->call_arguments($arguments, $arguments_definition);
		$this->assert_equal($result, array(
			$object,
			"string",
			"test" => array(
				"one" => $object,
				"two" => "string",
				"three" => 'Hike'
			)
		));
	}
	function test_hook_array() {
		$hook = null;
		$arguments = array();
		$default = null;
		zesk()->hooks->call_arguments($hook, $arguments, $default);
		echo basename(__FILE__) . ": success\n";
	}
	function test_href() {
		$path = null;
		zesk::href($path);
	}
	function test_initialize() {
		$extra_paths = false;
		zesk::initialize($extra_paths);
	}
	function test_load() {
		$file = $this->test_sandbox("foo.inc");
		file_put_contents($file, "<?php\n\n\$wont_change= 42;\$goo=1;global \$this_may_change; \$this_may_change = 2;zesk::set(\"load-test\",412);\nreturn 534523;");
		$goo = 2;
		global $wont_change;
		global $this_may_change;
		$this_may_change = 1;
		$wont_change = 3;
		$this->assert(zesk::load($file) === 534523);
		$this->assert($this_may_change === 2);
		$this->assert($wont_change === 3);
		$this->assert(zesk::get("load-test") === 412);
	}
	function test_load_globals() {
		$paths = array(
			$this->test_sandbox()
		);
		$file = $this->test_sandbox("test.conf");
		zesk::set("DUDE", "smooth");
		file_put_contents($file, "TEST_VAR=\"\$DUDE move\"\nVAR_2=\"Ha ha! \${TEST_VAR} ex-lax\"\nVAR_3=\"\${DUDE:-default value}\"\nVAR_4=\"\${DOOD:-default value}\"");
		$overwrite = false;
		$result = zesk::initialize($paths, array(
			'files' => array(
				"test.conf"
			),
			'overwrite' => false
		));
		debug::output($result);
		
		$globals = array(
			'TEST_VAR' => "smooth move",
			'VAR_2' => "Ha ha! smooth move ex-lax",
			'VAR_3' => "smooth",
			'VAR_4' => "default value"
		);
		
		foreach ($globals as $v => $result) {
			$g = zesk::get($v);
			$this->assert($g === $result, "$v: $g === $result");
		}
	}
	function test_load_globals1() {
		$paths = array(
			$this->test_sandbox()
		);
		$file = $this->test_sandbox("env.sh");
		file_put_contents($file, "A=1\nTEST=YES");
		
		$overwrite = false;
		$result = zesk::initialize($paths, array(
			'files' => array(
				"env.sh"
			),
			'overwrite' => false,
			'autotype' => true
		));
		$this->assert_equal(zesk::get("TEST"), true);
		$this->assert_equal(zesk::get("A"), 1);
		$result = zesk::initialize($paths, array(
			'files' => array(
				"env.sh"
			),
			'overwrite' => true,
			'autotype' => false
		));
		$this->assert_equal(zesk::get("TEST"), "YES");
		$this->assert_equal(zesk::get("A"), "1");
	}
	function test_load_globals_lines1() {
		$lines = array(
			"FOO=/foo/foo",
			"BAR=/bar/bar",
			"B_R=red",
			"UNQ0=\"quotes0\"",
			"UNQ1='quotes1'",
			'FOOTEST0=${FOO:-123}',
			'FOOTEST1=${UNDEF:-123}',
			'FOOTEST2=${FOO:-123}${BAR:-456}',
			'FOOTEST3=${UNDEF:-123}${BAR:-456}',
			'FOOTEST4=some${FOO:-123}some${UNDEF:-456}some',
			'FOOTEST5=goo${UNDEF:-123}goo${BAR:-456}goo',
			'FOOTEST6=goo${FOO}goo${BAR}goo',
			'FOOTEST7=goo${UNDEF}goo${U_NDEF2}goo',
			'FOOTEST8=${B_R}$B_R$B_R',
			'export FOOTEST9=${B_R}$B_R$B_R',
			"export\tFOOTEST10=\${B_R}\$B_R\$B_R",
			"export\t\t\tFOOTEST10=\${B_R}\$B_R\$B_R",
			'FOOTEST11="$B_Rthing"',
			'FOOTEST12=true',
			'FOOTEST13=false'
		);
		
		$results = array(
			'FOO' => '/foo/foo',
			'BAR' => '/bar/bar',
			'B_R' => 'red',
			'UNQ0' => 'quotes0',
			'UNQ1' => 'quotes1',
			'FOOTEST0' => '/foo/foo',
			'FOOTEST1' => 123,
			'FOOTEST2' => '/foo/foo/bar/bar',
			'FOOTEST3' => '123/bar/bar',
			'FOOTEST4' => 'some/foo/foosome456some',
			'FOOTEST5' => 'goo123goo/bar/bargoo',
			'FOOTEST6' => 'goo/foo/foogoo/bar/bargoo',
			'FOOTEST7' => 'googoogoo',
			'FOOTEST8' => 'redredred',
			'FOOTEST9' => 'redredred',
			'FOOTEST10' => 'redredred',
			'FOOTEST11' => '',
			'FOOTEST12' => true,
			'FOOTEST13' => false
		);
		$result = conf::parse($lines, array(
			'overwrite' => false,
			'lower' => false
		));
		foreach ($result as $k => $set) {
			$this->assert_equal($set, $results[$k]);
			unset($result[$k]);
		}
		$this->assert(count($result) === 0);
	}
	function test_maintenance() {
		zesk::maintenance();
	}
	function test_module_path() {
		$add = null;
		zesk::module_path($add);
	}
	function test_path() {
		$separator = '^';
		$mixed = array(
			'^^one',
			'^two^',
			'three^'
		);
		$result = zesk::path($separator, $mixed);
		$this->assert($result === "^one^two^three^");
	}
	function test_pid() {
		zesk::pid();
	}
	function test_production() {
		zesk::production();
	}
	function test_running() {
		zesk::running();
		$this->assert_equal(zesk::running(), zesk::pid());
		$this->assert_true(zesk::running(zesk::pid()));
		$this->assert_false(zesk::running(32766));
	}
	function test_set() {
		$key = null;
		$value = null;
		$overwrite = true;
		zesk::set($key, $value, $overwrite);
	}
	function test_share_path() {
		$add = null;
		$name = null;
		zesk::share_path($add, $name);
	}
	function test_site_root() {
		zesk::site_root();
	}
	function test_theme_path() {
		$add = null;
		zesk::theme_path($add);
	}
	
	/**
	 * @expected_exception Exception_Parameter
	 */
	function test_theme_fail() {
		$type = null;
		zesk::theme($type);
	}
	
	/**
	 */
	function test_theme() {
		zesk::theme("test");
	}
	function test_theme_template_arguments() {
		$type = null;
		$set = null;
		zesk::theme_arguments($type, $set);
	}
	function test_version() {
		zesk\Version::release();
		echo basename(__FILE__) . ": success\n";
	}
	function test_web_init() {
		$web_root_prefix = null;
		zesk::web_init($web_root_prefix);
		echo basename(__FILE__) . ": success\n";
	}
	function test_web_root() {
		$set = null;
		zesk::web_root($set);
		echo basename(__FILE__) . ": success\n";
	}
	function test_web_root_prefix() {
		$set = null;
		zesk::web_root_prefix($set);
		echo basename(__FILE__) . ": success\n";
	}
	function test_which() {
		$command = null;
		zesk::which($command);
		echo basename(__FILE__) . ": success\n";
	}
	function test_zesk_command_path() {
		$add = null;
		zesk::zesk_command_path($add);
		echo basename(__FILE__) . ": success\n";
	}
}
class A extends Hookable {}
class B extends A {}
class C extends B {}
class zTestObject {}
