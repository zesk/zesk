<?php
declare(strict_types=1);
/**
 * @sandbox true
 * @author kent
 *
 */

namespace zesk;

use Stringable;
use zesk\Command\SimpleCommand;

/**
 *
 * @author kent
 *
 */
class TheCommand_Test extends UnitTest {
	/**
	 * @throws ParameterException
	 */
	public function test_main(): void {
		$d = $this->test_sandbox();
		$f = $this->test_sandbox('test-file.txt');
		file_put_contents($f, 'test');

		$argv = [
			__CLASS__, '--no-ansi', '--file', $f, '--dir', $d, '--', 'ignored', '--', 'another',
		];

		$testObject = new SimpleCommand_Test($this->application, ['stdout' => true, 'no-ansi' => false]);

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
		$random = $this->randomHex();
		$stringable = new class($random) implements Stringable {
			private string $thing;

			public function __construct(string $thing) {
				$this->thing = $thing;
			}

			public function __toString(): string {
				return $this->thing;
			}
		};
		$testObject->error($stringable);
		$this->expectOutputString("thisIsCaptured\nERROR: Help\nERROR: $random\n");
	}
}

/**
 *
 * @author kent
 *
 */
class SimpleCommand_Test extends SimpleCommand {
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
