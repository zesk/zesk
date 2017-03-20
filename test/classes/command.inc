<?php

/**
 * @sandbox true
 * @author kent
 *
 */
class Test_Command extends Test_Unit {

	function test_main() {
		$dir = $this->test_sandbox();
		$f = $this->test_sandbox("test-file.txt");
		file_put_contents($f, "test");

		$optFormat = false;
		$optDefaults = false;
		$optionHelp = false;

		$_SERVER['argv'] = $argv = array(
			__CLASS__,
			"--file",
			$f
		);

		$testx = new Command_Base_Test();

		$this->assert($testx->has_errors() === false, "Has errors? " . implode(";", $testx->errors()));

		$file = $testx->option("FILE");

		$this->assert_equal($file, $f, "File option mismatch ($file !== $f) found: " . _dump($testx));

		$message = null;
		$testx->usage($message);

		$testx->arguments_remaining();

		$testx->has_errors();
		$testx->errors();

		$message = null;
		$testx->error($message);
	}
}

class Command_Base_Test extends zesk\Command_Base {
	public function initialize() {
		parent::initialize();
		$this->option_types += array(
			"file" => "file",
			"dir" => "dir"
		);
	}

	function run() {

	}
}
