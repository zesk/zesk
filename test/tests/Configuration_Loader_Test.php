<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/test/classes/conf.inc $
 * @package zesk
 * @subpackage test
 * @author $Author: kent $
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class Configuration_Loader_Test extends Test_Unit {
	function test_implements() {
		$config = new Configuration();
		$settings = new Adapter_Settings_Configuration($config);
		$this->assert_instanceof($settings, __NAMESPACE__ . "\\" . "Adapter_Settings_Configuration");
		$this->assert_instanceof($settings, __NAMESPACE__ . "\\" . "Interface_Settings");
		$this->assert_implements($settings, __NAMESPACE__ . "\\" . "Interface_Settings");
	}
	function test_new() {
		$path = $this->test_sandbox();
		Directory::depend($one = path($path, "one"));
		Directory::depend($two = path($path, "two"));
		Directory::depend($three = path($path, "three"));

		$array = array(
			"name" => "ralph",
			"rank" => "admiral",
			"weight" => 140,
			"eye_color" => "brown"
		);
		$config = new Configuration($array);
		$settings = new Adapter_Settings_Configuration($config);
		$conf_name = "a.conf";
		$json_name = "b.json";
		// Four files

		$one_json = array(
			"Person" => array(
				"name" => "\${name}-one-json",
				"rank" => "\${rank}-one-json",
				"weight" => "\${weight}-one-json",
				"eye_color" => "\${eye_color}-one-json"
			),
			"LAST" => "one-json",
			"FILE_LOADED" => array(
				"ONE_JSON" => 1
			)
		);
		$two_conf = array(
			"# Comment",
			"",
			"Person::name=\$name-two-conf",
			"Person::eye_color=\"\${eye_color:=red}-two-conf\"",
			"Person::hair_color=\"\${hair_color:=red}-two-conf\"",
			"LAST=two-conf",
			"FILE_LOADED__TWO_CONF=1",
			"zesk___User__class=User"
		);
		$three_json = array(
			"Person::name" => "\${name}-three-json",
			"Person::rank" => "\${rank}-three-json",
			"LAST" => "three-json",
			"FILE_LOADED" => array(
				"THREE_JSON" => 1
			)
		);
		$three_conf = array(
			"# Comment",
			"",
			"Person::name=\$name-three-conf",
			"Person::weight=\"\$weight-three-conf\"",
			"LAST=three-conf",
			"FILE_LOADED::THREE_CONF=1"
		);

		file_put_contents(path($one, $json_name), JSON::encode_pretty($one_json));
		file_put_contents(path($two, $conf_name), implode("\n", $two_conf));
		file_put_contents(path($three, $json_name), JSON::encode_pretty($three_json));
		file_put_contents(path($three, $conf_name), implode("\n", $three_conf));

		$files = array();
		foreach (array(
			$one,
			path($path, "nope"),
			$two,
			path($path, "double_nope"),
			$three
		) as $dir) {
			foreach (array(
				"a.conf",
				"b.json"
			) as $f) {
				$files[] = path($dir, $f);
			}
		}
		$loader = new Configuration_Loader($files, $settings);

		$loader->load();

		$variables = $loader->variables();
		$this->assert_equal($variables['processed'], array(
			"$path/one/$json_name",
			"$path/two/$conf_name",
			"$path/three/$conf_name",
			"$path/three/$json_name"
		));

		$this->assert_arrays_equal(to_array($config), array(
			"name" => "ralph",
			"rank" => "admiral",
			"weight" => 140,
			"eye_color" => "brown",
			"person" => array(
				"name" => "ralph-three-json",
				"rank" => "admiral-three-json",
				"weight" => "140-three-conf",
				"eye_color" => "brown-two-conf",
				"hair_color" => "red-two-conf"
			),
			"last" => "three-json",
			"file_loaded" => array(
				"one_json" => 1,
				"two_conf" => 1,
				"three_conf" => 1,
				"three_json" => 1
			),
			"zesk\\user" => array(
				"class" => "User"
			)
		));
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
		$options = array(
			'overwrite' => false,
			'lower' => false
		);
		$actual = array();
		$settings = new Adapter_Settings_Array($actual);
		$parser = new Configuration_Parser_CONF(implode("\n", $lines), $settings, $options);
		$parser->process();

		foreach ($actual as $k => $set) {
			$this->assert_equal($set, $results[$k], "Key $k did not match");
			unset($actual[$k]);
		}
		$this->assert(count($actual) === 0);
	}
	function provider_test_no_dependencies() {
		$dir = dirname(__DIR__) . '/test-data/';
		return array(
			array(
				array(
					$dir . 'Configuration_Loader_Test_dependencies0.conf'
				)
			),
			array(
				array(
					$dir . 'Configuration_Loader_Test_dependencies0.conf',
					$dir . 'Configuration_Loader_Test_dependencies1.conf'
				)
			)
		);
	}

	/**
	 * @dataProvider provider_test_no_dependencies
	 * @param unknown $files
	 */
	function test_no_dependencies(array $files) {
		$dir = dirname(__DIR__) . '/test-data/';
		$result = array();
		$settings = new Adapter_Settings_Array($result);
		$loader = new Configuration_Loader($files, $settings);
		$loader->load();
		$this->assertEquals(array(), $loader->externals());
	}

	// 	function test_edit() {
	// 		$path = $this->test_sandbox(__FUNCTION__ . ".conf");
	// 		file_put_contents($path, "MONKEY=MAN\nDoG=\"CANINE\"");
	// 		$edits = array(
	// 			"MONKEY" => "APE"
	// 		);
	// 		$options = array();
	// 		conf::edit($path, $edits, $options);
	// 		$result = conf::load($path, array(
	// 			"lower" => false
	// 		));
	// 		$this->assert_arrays_equal($result, array(
	// 			"MONKEY" => "APE",
	// 			"DoG" => "CANINE"
	// 		));
	// 	}
	// 	function test_globals() {
	// 		$paths = null;
	// 		$options = array();
	// 		conf::globals($paths, $options);
	// 	}

	// 	/**
	// 	 * @expectedException Exception_File_NotFound
	// 	 */
	// 	function test_load_not_found() {
	// 		$path = $this->test_sandbox(__FUNCTION__ . ".conf");
	// 		$options = array();
	// 		conf::load($path, $options);
	// 	}
	// 	function test_load_inherit() {
	// 		$files = array(
	// 			"one.conf",
	// 			"two.conf"
	// 		);
	// 		$paths = array(
	// 			$this->test_sandbox('foo'),
	// 			$this->test_sandbox('bar')
	// 		);
	// 		$options = array();
	// 		conf::load_inherit($files, $paths, $options);
	// 	}
	// 	function test_options_default() {
	// 		$options = array();
	// 		conf::options_default($options);
	// 	}
	// 	function test_parse() {
	// 		$lines = array(
	// 			'DEE=BER',
	// 			'FOO=BAR',
	// 			'DUDE=${FOO}/${DEE}',
	// 			'DUDE=no-overwrite',
	// 			'export BOO=${UNDEF:-boo-boo}'
	// 		);
	// 		$options = array(
	// 			'lower' => false
	// 		);
	// 		$result = conf::parse($lines, $options);
	// 		echo Text::format_array($result);
	// 		$this->assert($result['DUDE'] === 'BAR/BER', $result['DUDE'] . ' === ' . 'BAR/BER');
	// 		$this->assert($result['BOO'] === 'boo-boo', $result['BOO'] . ' === ' . 'boo-boo');
	// 	}
}
