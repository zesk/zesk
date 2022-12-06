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
class TheCommand_Test extends UnitTest {
	public function test_main(): void {
		$dir = $this->test_sandbox();
		$f = $this->test_sandbox('test-file.txt');
		file_put_contents($f, 'test');

		$_SERVER['argv'] = $argv = [
			__CLASS__,
			'--file',
			$f,
		];

		$testx = new Command_Base_Test($this->application);

		$this->assertFalse($testx->hasErrors(), 'Has errors? ' . implode(';', $testx->errors()));

		$file = $testx->option('FILE');

		$this->assertEquals($file, $f, "File option mismatch ($file !== $f) found: " . $testx->__toString());
		$testx->argumentsRemaining();

		$testx->hasErrors();
		$testx->errors();

		$message = 'Help';
		$testx->error($message);
		$message = ['Help'];
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
			'file' => 'file',
			'dir' => 'dir',
		];
	}

	public function run(): void {
	}
}
