<?php
declare(strict_types=1);
/**
 * @sandbox true
 * @author  kent
 * @copyright &copy; 2025, Market Acumen, Inc.
 */

namespace testZesk;

use Stringable;
use zesk\Exception\ParameterException;

/**
 *
 * @author kent
 * @coversDefaultClass \zesk\Command
 */
class CommandTest extends TestCase
{
	/**
	 * @return void
	 */
	/**
	 * @return void
	 * @throws ParameterException
	 * @covers ::initialize ::run ::hasErrors ::parseArguments ::arguments ::argumentsRemaining ::error
	 */
	public function test_main(): void
	{
		$d = $this->test_sandbox();
		$f = $this->test_sandbox('test-file.txt');
		file_put_contents($f, 'test');

		$argv = [
			__CLASS__,
			'--no-ansi',
			'--file',
			$f,
			'--dir',
			$d,
			'--',
			'ignored',
			'--',
			'another',
		];

		$testObject = new SimpleTestCommand($this->application, [
			'stdout' => true,
			'no-ansi' => false,
		]);

		$testObject->parseArguments($argv);

		$this->assertTrue($testObject->optionBool('no-ansi'));


		$this->assertFalse($testObject->hasErrors(), 'Has errors? ' . implode(';', $testObject->errors()));

		$file = $testObject->option('file');

		$this->assertEquals($file, $f, "File option mismatch ($file !== $f) found: " . $testObject->__toString());
		$this->assertCount(1, $testObject->parseArguments($argv)->argumentsRemaining(true));
		$this->assertEquals(['ignored'], $testObject->parseArguments($argv)->argumentsRemaining(true));

		$this->assertCount(3, $testObject->parseArguments($argv)->argumentsRemaining(false));

		$this->assertEquals([
			'ignored',
			'--',
			'another',
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

			public function __construct(string $thing)
			{
				$this->thing = $thing;
			}

			public function __toString(): string
			{
				return $this->thing;
			}
		};
		$testObject->error($stringable);
		$this->expectOutputString("thisIsCaptured\nERROR: Help\nERROR: $random\n");
	}
}
