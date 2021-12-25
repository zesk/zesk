<?php declare(strict_types=1);

/**
 * @sandbox true
 * @author kent
 *
 */
namespace zesk;

/**
 *
 * @author kent
 *
 */
class TheCommand_Test extends Test_Unit {
	public function test_main(): void {
		$dir = $this->test_sandbox();
		$f = $this->test_sandbox("test-file.txt");
		file_put_contents($f, "test");

		$_SERVER['argv'] = $argv = [
			__CLASS__,
			"--file",
			$f,
		];

		$testx = new Command_Base_Test($this->application);

		$this->assert($testx->has_errors() === false, "Has errors? " . implode(";", $testx->errors()));

		$file = $testx->option("FILE");

		$this->assert_equal($file, $f, "File option mismatch ($file !== $f) found: " . $testx->__toString());
		$testx->arguments_remaining();

		$testx->has_errors();
		$testx->errors();

		$message = null;
		$testx->error($message);
	}
}

/**
 *
 * @author kent
 *
 */
class Command_Base_Test extends Command_Base {
	public function initialize(): void {
		parent::initialize();
		$this->option_types += [
			"file" => "file",
			"dir" => "dir",
		];
	}

	public function run(): void {
	}
}
