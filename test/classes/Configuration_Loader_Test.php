<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/test/classes/conf.inc $
 * @package zesk
 * @subpackage test
 * @author $Author: kent $
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
namespace zesk;

class Configuration_Loader_Test extends Test_Unit {
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
		$settings = new Adapter_Settings_Array($array);
		$this->assert_instanceof($setings, __NAMESPACE__ . "\\" . "Interface_Settings");
		$conf_name = "a.conf";
		$json_name = "b.json";
		$loader = new Configuration_Loader(array(
			$one,
			path($path, "nope"),
			$two,
			path($path, "double_nope"),
			$three
		), array(
			"a.conf",
			"b.json"
		), $settings);
		
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
			"zesk___User__class" => "User"
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
		
		$loader->load();
		
		$this->assert_arrays_equal($array, array(
			"name" => "ralph",
			"rank" => "admiral",
			"weight" => 140,
			"person" => array(
				"name" => "ralph-three-conf",
				"rank" => "ralph-three-conf",
				"weight" => "ralph-three-conf",
				"eye_color" => "ralph-three-conf"
			),
			"file_loaded" => array(
				"one_json" => 1,
				"two_conf" => 1,
				"three_conf" => 1,
				"three_json" => 1
			),
			"last" => "three-json",
			"zesk\\User" => array(
				"class" => "User"
			)
		));
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
	// 	 * @expected_exception Exception_File_NotFound
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
