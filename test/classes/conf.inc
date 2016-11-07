<?php
/**
 * @version $URL: https://code.marketacumen.com/zesk/trunk/test/classes/conf.inc $
 * @package zesk
 * @subpackage test
 * @author $Author: kent $
 * @copyright Copyright &copy; 2012, Market Acumen, Inc.
 */
use zesk\Text;

class test_conf extends Test_Unit {

	function test_edit() {
		$path = $this->test_sandbox(__FUNCTION__ . ".conf");
		file_put_contents($path, "MONKEY=MAN\nDoG=\"CANINE\"");
		$edits = array(
			"MONKEY" => "APE"
		);
		$options = array();
		conf::edit($path, $edits, $options);
		$result = conf::load($path, array(
			"lower" => false
		));
		$this->assert_arrays_equal($result, array(
			"MONKEY" => "APE",
			"DoG" => "CANINE"
		));
	}

	function test_globals() {
		$paths = null;
		$options = array();
		conf::globals($paths, $options);
	}

	/**
	 * @expected_exception Exception_File_NotFound
	 */
	function test_load_not_found() {
		$path = $this->test_sandbox(__FUNCTION__ . ".conf");
		$options = array();
		conf::load($path, $options);
	}

	function test_load_inherit() {
		$files = array(
			"one.conf",
			"two.conf"
		);
		$paths = array(
			$this->test_sandbox('foo'),
			$this->test_sandbox('bar')
		);
		$options = array();
		conf::load_inherit($files, $paths, $options);
	}

	function test_options_default() {
		$options = array();
		conf::options_default($options);
	}

	function test_parse() {
		$lines = array(
			'DEE=BER',
			'FOO=BAR',
			'DUDE=${FOO}/${DEE}',
			'DUDE=no-overwrite',
			'export BOO=${UNDEF:-boo-boo}'
		);
		$options = array(
			'lower' => false
		);
		$result = conf::parse($lines, $options);
		echo Text::format_array($result);
		$this->assert($result['DUDE'] === 'BAR/BER', $result['DUDE'] . ' === ' . 'BAR/BER');
		$this->assert($result['BOO'] === 'boo-boo', $result['BOO'] . ' === ' . 'boo-boo');
	}
}
