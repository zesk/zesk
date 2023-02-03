<?php
declare(strict_types=1);

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
	/**
	 * @throws Exception_Parameter
	 */
	public function test_main(): void {
		$d = $this->test_sandbox();
		$f = $this->test_sandbox('test-file.txt');
		file_put_contents($f, 'test');

		$argv = [
			__CLASS__, '--no-ansi', '--file', $f, '--dir', $d, '--', 'ignored', '--', 'another',
		];

		$testObject = new Command_Base_Test($this->application, ['stdout' => true, 'no-ansi' => false]);

		$testObject->parseArguments($argv);

		$this->assertTrue($testObject->optionBool('no-ansi'));


		$this->assertFalse($testObject->hasErrors(), 'Has errors? ' . implode(';', $testObject->errors()));

		$file = $testObject->option('file');

		$this->assertEquals($file, $f, "File option mismatch ($file !== $f) found: " . $testObject->__toString());
		$this->assertCount(1, $testObject->parseArguments($argv)->argumentsRemaining(true));
		$this->assertEquals(['ignored'], $args = $testObject->parseArguments($argv)->argumentsRemaining(true));

		$this->assertCount(3, $testObject->parseArguments($argv)->argumentsRemaining(false));

		$this->assertEquals([
			'ignored', '--', 'another',
		], $testObject->parseArguments($argv)->argumentsRemaining(false));

		$this->assertFalse($testObject->hasErrors());
		$this->assertCount(0, $testObject->errors());

		$this->assertTrue($testObject->optionBool('stdout'));

		$this->streamCapture(STDOUT);
		fprintf(STDOUT, "thisIsCaptured\n");
		$message = 'Help';
		$testObject->error($message);
		$message = ['Help'];
		$testObject->error($message);
		$this->expectOutputString("thisIsCaptured\nERROR: Help\nERROR: Help\n");
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
			'file' => 'file', 'dir' => 'dir',
		];
	}

	public function run(): int {
		$class = $this->optionString('testThrow');
		if ($class) {
			throw new $class();
		}
		return $this->optionInt('testExitCode');
	}
}
